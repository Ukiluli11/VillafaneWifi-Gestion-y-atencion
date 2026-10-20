<?php

namespace App\Dominio;

use App\Enums\EstadoServicio;
use App\Enums\EstadoTicket;
use App\Enums\EstadoUsuario;
use App\Models\Cliente;
use App\Models\Conversacion;
use App\Models\NotaInterna;
use App\Models\Servicio;
use App\Models\Ticket;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * ServicioTickets: Coordina la generación, asignación, ciclo de vida y notas de tickets de soporte técnico.
 * Casos de Uso: Registrar reclamo técnico, Generar ticket de soporte, Asignar ticket a técnico,
 * Gestionar estado de ticket, Reabrir ticket y Agregar nota interna.
 */
class ServicioTickets
{
    /**
     * Caso de Uso: Registrar reclamo técnico
     */
    public function crearDesdeConversacion(
        Conversacion $conversacion,
        Cliente $cliente,
        string $descripcion,
    ): ?Ticket {
        $servicio = $cliente->servicios()
            ->where('estado', '!=', EstadoServicio::Baja->value)
            ->orderByRaw('CASE WHEN estado = ? THEN 0 ELSE 1 END', [EstadoServicio::Activo->value])
            ->orderBy('id_servicio')
            ->first();

        if ($servicio === null) {
            return null;
        }

        return DB::transaction(fn (): Ticket => Ticket::create([
            'id_conversacion' => $conversacion->id_conversacion,
            'id_servicio' => $servicio->id_servicio,
            'fecha_creacion' => now(),
            'tipo' => 'tecnico',
            'descripcion' => trim($descripcion),
            'estado' => EstadoTicket::Pendiente,
        ]));
    }

    /**
     * Caso de Uso: Generar ticket de soporte
     */
    public function crearManual(array $datos, Usuario $creador): Ticket
    {
        return DB::transaction(function () use ($datos, $creador): Ticket {
            $servicio = Servicio::findOrFail($datos['id_servicio']);
            if ($servicio->estado === EstadoServicio::Baja) {
                throw ValidationException::withMessages(['id_servicio' => 'Elegí un servicio que no esté dado de baja.']);
            }
            if (! empty($datos['id_conversacion']) && ! Conversacion::whereKey($datos['id_conversacion'])
                ->where('id_cliente', $servicio->id_cliente)->exists()) {
                throw ValidationException::withMessages(['id_conversacion' => 'La conversación debe pertenecer al cliente del servicio.']);
            }
            if (! empty($datos['id_usuario_asignado'])) {
                $this->validarResponsable(Usuario::findOrFail($datos['id_usuario_asignado']), $creador);
            }
            $ticket = Ticket::create([
                'id_conversacion' => $datos['id_conversacion'] ?? null,
                'id_servicio' => $datos['id_servicio'],
                'id_usuario_asignado' => $datos['id_usuario_asignado'] ?? null,
                'fecha_creacion' => now(),
                'tipo' => $datos['tipo'] ?? 'tecnico',
                'descripcion' => trim($datos['descripcion']),
                'estado' => ! empty($datos['id_usuario_asignado']) ? EstadoTicket::Asignado : EstadoTicket::Pendiente,
                'fecha_asignacion' => ! empty($datos['id_usuario_asignado']) ? now() : null,
            ]);

            if (! empty($datos['nota_inicial'])) {
                $ticket->agregarNota(trim($datos['nota_inicial']), $creador);
            }

            return $ticket;
        });
    }

    /**
     * Caso de Uso: Asignar ticket a técnico
     */
    public function asignarTicket(Ticket $ticket, Usuario $tecnico, Usuario $autor, string $campo = 'id_usuario_asignado'): void
    {
        DB::transaction(function () use ($ticket, $tecnico, $autor, $campo): void {
            $actual = Ticket::query()->lockForUpdate()->findOrFail($ticket->id_ticket);
            $this->validarResponsable($tecnico, $autor, $campo);
            if (! $autor->administrador()->exists() && ! in_array($actual->id_usuario_asignado, [null, $autor->id_usuario], true)) {
                throw ValidationException::withMessages([$campo => 'El ticket ya está asignado a otro responsable.']);
            }
            if (in_array($actual->estado, [EstadoTicket::Resuelto, EstadoTicket::Cerrado], true)) {
                throw ValidationException::withMessages(['ticket' => 'Reabrí el ticket antes de asignarlo.']);
            }
            $actual->asignar($tecnico);
            $actual->agregarNota("Ticket asignado al técnico {$tecnico->nombre_usuario}.", $autor);
        });
    }

    /**
     * Caso de Uso: Gestionar estado de ticket
     */
    public function cambiarEstado(Ticket $ticket, EstadoTicket $nuevoEstado, ?Usuario $operador = null, ?string $observacion = null): void
    {
        DB::transaction(function () use ($ticket, $nuevoEstado, $operador, $observacion): void {
            $ticket = Ticket::query()->lockForUpdate()->findOrFail($ticket->id_ticket);
            abort_if($operador === null, 403);
            $this->verificarGestion($ticket, $operador);
            $permitidos = match ($ticket->estado) {
                EstadoTicket::Pendiente => [],
                EstadoTicket::Asignado => [EstadoTicket::EnProceso, EstadoTicket::Resuelto],
                EstadoTicket::EnProceso => [EstadoTicket::Resuelto],
                EstadoTicket::Resuelto => [EstadoTicket::Cerrado, EstadoTicket::Reabierto],
                EstadoTicket::Cerrado => [EstadoTicket::Reabierto],
                EstadoTicket::Reabierto => [EstadoTicket::EnProceso, EstadoTicket::Resuelto],
            };
            if (! in_array($nuevoEstado, $permitidos, true)) {
                throw ValidationException::withMessages(['estado' => 'La transición de estado no es válida. Asigná el ticket antes de atenderlo.']);
            }
            match ($nuevoEstado) {
                EstadoTicket::Asignado => $operador ? $ticket->asignar($operador) : $ticket->update(['estado' => EstadoTicket::Asignado]),
                EstadoTicket::EnProceso => $ticket->ponerEnProceso(),
                EstadoTicket::Resuelto => $ticket->resolver(),
                EstadoTicket::Cerrado => $ticket->cerrar(),
                EstadoTicket::Reabierto => $ticket->reabrir(),
                default => $ticket->update(['estado' => $nuevoEstado]),
            };

            if ($observacion && $operador) {
                $ticket->agregarNota($observacion, $operador);
            }
        });
    }

    /**
     * Caso de Uso: Reabrir ticket
     */
    public function reabrirTicket(Ticket $ticket, Usuario $operador, ?string $motivo = null): void
    {
        DB::transaction(function () use ($ticket, $operador, $motivo): void {
            $this->cambiarEstado($ticket, EstadoTicket::Reabierto, $operador);
            $mensaje = 'Ticket reabierto.';
            if ($motivo) {
                $mensaje .= ' Motivo: '.trim($motivo);
            }
            $ticket->agregarNota($mensaje, $operador);
        });
    }

    /**
     * Caso de Uso: Agregar nota interna
     */
    public function agregarNotaInterna(Ticket $ticket, Usuario $autor, string $contenido): NotaInterna
    {
        return DB::transaction(function () use ($ticket, $autor, $contenido): NotaInterna {
            $ticket = Ticket::query()->lockForUpdate()->findOrFail($ticket->id_ticket);
            $this->verificarGestion($ticket, $autor);

            return $ticket->agregarNota(trim($contenido), $autor);
        });
    }

    private function verificarGestion(Ticket $ticket, Usuario $autor): void
    {
        abort_unless($autor->administrador()->exists() || ($autor->empleado()->where('area', 'soporte')->exists()
            && $ticket->id_usuario_asignado === $autor->id_usuario), 403);
    }

    private function validarResponsable(Usuario $tecnico, Usuario $autor, string $campo = 'id_usuario_asignado'): void
    {
        $habilitado = $tecnico->estado === EstadoUsuario::Activo
            && ($tecnico->administrador()->exists() || $tecnico->empleado()->where('area', 'soporte')->exists());
        if (! $habilitado || (! $autor->administrador()->exists() && $tecnico->id_usuario !== $autor->id_usuario)) {
            throw ValidationException::withMessages([$campo => 'Elegí un responsable de soporte habilitado.']);
        }
    }
}
