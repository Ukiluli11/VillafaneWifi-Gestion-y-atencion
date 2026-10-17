<?php

namespace App\Dominio;

use App\Enums\EstadoServicio;
use App\Enums\EstadoTicket;
use App\Models\Cliente;
use App\Models\Conversacion;
use App\Models\Ticket;
use App\Models\NotaInterna;
use App\Models\Servicio;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

/**
 * ServicioTickets: Coordina la generación, asignación, ciclo de vida y notas de tickets de soporte técnico.
 * Casos de Uso: Registrar reclamo técnico, Generar ticket de soporte, Asignar ticket a técnico,
 * Gestionar estado de ticket, Reabrir ticket, Agregar nota interna (Módulo 4).
 */
class ServicioTickets
{
    /**
     * Caso de Uso: Registrar reclamo técnico / Generar ticket de soporte (desde WhatsApp)
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
     * Caso de Uso: Generar ticket de soporte (manualmente desde panel web) (RF-27)
     */
    public function crearManual(array $datos, Usuario $creador): Ticket
    {
        return DB::transaction(function () use ($datos, $creador): Ticket {
            $ticket = Ticket::create([
                'id_conversacion'     => $datos['id_conversacion'] ?? null,
                'id_servicio'         => $datos['id_servicio'],
                'id_usuario_asignado' => $datos['id_usuario_asignado'] ?? null,
                'fecha_creacion'      => now(),
                'tipo'                => $datos['tipo'] ?? 'tecnico',
                'descripcion'         => trim($datos['descripcion']),
                'estado'              => !empty($datos['id_usuario_asignado']) ? EstadoTicket::Asignado : EstadoTicket::Pendiente,
                'fecha_asignacion'    => !empty($datos['id_usuario_asignado']) ? now() : null,
            ]);

            if (!empty($datos['nota_inicial'])) {
                $ticket->agregarNota(trim($datos['nota_inicial']), $creador);
            }

            return $ticket;
        });
    }

    /**
     * Caso de Uso: Asignar ticket a técnico (RF-29)
     */
    public function asignarTicket(Ticket $ticket, Usuario $tecnico): void
    {
        DB::transaction(function () use ($ticket, $tecnico): void {
            $ticket->asignar($tecnico);
            $ticket->agregarNota("Ticket asignado al técnico {$tecnico->nombre_usuario}.", $tecnico);
        });
    }

    /**
     * Caso de Uso: Gestionar estado de ticket - Resolver / Cerrar / En proceso (RF-30)
     */
    public function cambiarEstado(Ticket $ticket, EstadoTicket $nuevoEstado, ?Usuario $operador = null, ?string $observacion = null): void
    {
        DB::transaction(function () use ($ticket, $nuevoEstado, $operador, $observacion): void {
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
     * Caso de Uso: Reabrir ticket de soporte (RF-31)
     */
    public function reabrirTicket(Ticket $ticket, Usuario $operador, ?string $motivo = null): void
    {
        DB::transaction(function () use ($ticket, $operador, $motivo): void {
            $ticket->reabrir();
            $mensaje = 'Ticket reabierto.';
            if ($motivo) {
                $mensaje .= ' Motivo: ' . trim($motivo);
            }
            $ticket->agregarNota($mensaje, $operador);
        });
    }

    /**
     * Caso de Uso: Agregar nota interna (RF-32)
     */
    public function agregarNotaInterna(Ticket $ticket, Usuario $autor, string $contenido): NotaInterna
    {
        return $ticket->agregarNota($contenido, $autor);
    }
}
