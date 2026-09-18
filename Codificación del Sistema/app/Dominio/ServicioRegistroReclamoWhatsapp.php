<?php

namespace App\Dominio;

use App\Enums\EstadoFlujoWhatsapp;
use App\Enums\EstadoServicio;
use App\Enums\TipoMensaje;
use App\Enums\TipoTicket;
use App\Models\Cliente;
use App\Models\Conversacion;
use App\Models\Mensaje;
use App\Models\Servicio;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Gestiona el diálogo necesario para transformar un reclamo en un ticket.
 */
class ServicioRegistroReclamoWhatsapp
{
    public function __construct(
        private readonly ServicioEnvioWhatsapp $envioWhatsapp,
        private readonly ServicioConversaciones $conversaciones,
    ) {}

    /**
     * Inicia el reclamo y explica cómo identificar el servicio afectado.
     */
    public function solicitarDescripcion(Conversacion $conversacion, Cliente $cliente): Mensaje
    {
        $servicios = $this->serviciosActivos($cliente);
        if ($servicios->isEmpty()) {
            $conversacion->update(['estado_flujo' => EstadoFlujoWhatsapp::Menu]);

            return $this->envioWhatsapp->enviarTextoDelBot(
                $conversacion,
                'No encontramos un servicio activo asociado a tu cuenta. '
                .'Solicitá atención humana para que podamos revisar tus datos.',
            );
        }

        $conversacion->update([
            'estado_flujo' => EstadoFlujoWhatsapp::EsperandoDescripcionReclamo,
        ]);

        $instruccion = $servicios->count() === 1
            ? 'Describí el problema con el mayor detalle posible.'
            : "Tenés más de un servicio. Respondé con el número del servicio y luego describí el problema:\n"
                .$this->crearListaServicios($servicios);

        return $this->envioWhatsapp->enviarTextoDelBot(
            $conversacion,
            "Vamos a registrar tu reclamo.\n{$instruccion}",
        );
    }

    /**
     * Valida la descripción, crea el ticket y comunica su número al cliente.
     */
    public function registrar(
        Conversacion $conversacion,
        Cliente $cliente,
        Mensaje $mensaje,
    ): Mensaje {
        if ($mensaje->tipo !== TipoMensaje::Texto || blank($mensaje->contenido)) {
            return $this->envioWhatsapp->enviarTextoDelBot(
                $conversacion,
                'Escribí una descripción del problema para poder generar el ticket.',
            );
        }

        $servicios = $this->serviciosActivos($cliente);
        [$servicio, $descripcion] = $this->resolverServicioYDescripcion($servicios, $mensaje->contenido);

        if ($servicio === null || blank($descripcion)) {
            return $this->envioWhatsapp->enviarTextoDelBot(
                $conversacion,
                "No pudimos identificar el servicio. Respondé con su número seguido de la descripción:\n"
                .$this->crearListaServicios($servicios),
            );
        }

        $ticket = DB::transaction(function () use ($conversacion, $servicio, $descripcion): Ticket {
            $ticket = Ticket::create([
                'id_conversacion' => $conversacion->id_conversacion,
                'id_servicio' => $servicio->id_servicio,
                'fecha_creacion' => now(),
                'tipo' => $this->clasificarTipo($descripcion),
                'descripcion' => $descripcion,
            ]);

            $conversacion->update(['estado_flujo' => EstadoFlujoWhatsapp::Menu]);

            return $ticket;
        });

        $direccion = trim("{$servicio->calle_instalacion} {$servicio->numero_instalacion}");

        $respuesta = $this->envioWhatsapp->enviarTextoDelBot(
            $conversacion,
            "Registramos correctamente tu reclamo con el ticket #{$ticket->id_ticket}.\n"
            ."Servicio afectado: {$direccion}, {$servicio->localidad_instalacion}.\n"
            .'Será atendido por orden de llegada. Esta conversación queda finalizada; '
            .'si necesitás otra gestión, escribinos nuevamente.',
        );

        $this->conversaciones->cerrar($conversacion->refresh());

        return $respuesta;
    }

    /** Obtiene solamente los servicios vigentes que pueden originar reclamos. */
    private function serviciosActivos(Cliente $cliente): Collection
    {
        return $cliente->servicios()
            ->whereIn('estado', [EstadoServicio::Activo->value, EstadoServicio::Suspendido->value])
            ->orderBy('id_servicio')
            ->get();
    }

    /**
     * Selecciona automáticamente el único servicio o interpreta el número
     * indicado al comienzo del mensaje cuando el cliente posee varios.
     *
     * @param  Collection<int, Servicio>  $servicios
     * @return array{0: ?Servicio, 1: string}
     */
    private function resolverServicioYDescripcion(Collection $servicios, string $contenido): array
    {
        $contenido = trim($contenido);
        if ($servicios->count() === 1) {
            return [$servicios->first(), $contenido];
        }

        if (! preg_match('/^(?:servicio\s*)?#?(\d+)\s*[-:,.]?\s+(.+)$/iu', $contenido, $coincidencias)) {
            return [null, $contenido];
        }

        $servicio = $servicios->firstWhere('id_servicio', (int) $coincidencias[1]);

        return [$servicio, trim($coincidencias[2])];
    }

    /**
     * Presenta los servicios mediante su identificador y dirección.
     *
     * @param  Collection<int, Servicio>  $servicios
     */
    private function crearListaServicios(Collection $servicios): string
    {
        return $servicios
            ->map(fn (Servicio $servicio): string => "#{$servicio->id_servicio} - "
                .trim("{$servicio->calle_instalacion} {$servicio->numero_instalacion}")
                .", {$servicio->localidad_instalacion}")
            ->implode("\n");
    }

    /** Aplica una clasificación inicial que luego podrá mejorar el motor de IA. */
    private function clasificarTipo(string $descripcion): TipoTicket
    {
        $texto = Str::of($descripcion)->ascii()->lower()->toString();
        $esAdministrativo = collect(['pago', 'cuota', 'factura', 'cobro', 'deuda'])
            ->contains(fn (string $palabra): bool => str_contains($texto, $palabra));

        return $esAdministrativo ? TipoTicket::Administrativo : TipoTicket::Tecnico;
    }
}
