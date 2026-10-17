<?php

namespace App\Dominio;

use App\Enums\EstadoCuota;
use App\Enums\EstadoServicio;
use App\Models\Cuota;
use App\Models\Mensaje;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

class ServicioNotificacionesVencimiento
{
    public function __construct(
        private readonly WhatsAppService $whatsAppService,
        private readonly ServicioConversaciones $servicioConversaciones,
    ) {}

    /** @return array{enviadas: int, omitidas: int, fallidas: int} */
    public function enviar(): array
    {
        $hoy = CarbonImmutable::today();
        $limite = $hoy->addDays(3);
        $resultado = ['enviadas' => 0, 'omitidas' => 0, 'fallidas' => 0];

        Cuota::query()
            ->with(['servicio.cliente'])
            ->whereNull('id_pago')
            ->whereIn('estado', [EstadoCuota::Pendiente->value, EstadoCuota::Vencida->value])
            ->whereDate('fecha_vencimiento', '<=', $limite->toDateString())
            ->whereHas('servicio', function (Builder $consulta): void {
                $consulta->where('estado', EstadoServicio::Activo->value)
                    ->whereHas('cliente', fn (Builder $cliente) => $cliente
                        ->whereNotNull('telefono_whatsapp')
                        ->where('telefono_whatsapp', '!=', ''));
            })
            ->orderBy('id_cuota')
            ->chunkById(100, function ($cuotas) use ($hoy, &$resultado): void {
                foreach ($cuotas as $cuota) {
                    $estadoAviso = $cuota->fecha_vencimiento->lessThan($hoy) ? 'vencida' : 'proxima';
                    $clave = "vencimiento:{$estadoAviso}:{$cuota->id_cuota}";
                    $cliente = $cuota->servicio->cliente;
                    $conversacion = $this->servicioConversaciones->obtenerOCrearConversacion($cliente->telefono_whatsapp);
                    $contenido = $this->contenido($cuota, $estadoAviso);
                    $mensaje = $this->servicioConversaciones->reservarNotificacion($conversacion, $clave, $contenido);

                    if ($mensaje === null) {
                        $resultado['omitidas']++;

                        continue;
                    }

                    if ($this->enviarMensaje($mensaje, $cliente->telefono_whatsapp, $cliente->nombre_razon_social, $cuota)) {
                        $resultado['enviadas']++;
                    } else {
                        $resultado['fallidas']++;
                    }
                }
            }, 'id_cuota');

        return $resultado;
    }

    private function enviarMensaje(Mensaje $mensaje, string $telefono, string $cliente, Cuota $cuota): bool
    {
        try {
            $idExterno = $this->whatsAppService->enviarPlantilla(
                $telefono,
                (string) config('services.whatsapp.plantilla_vencimiento'),
                (string) config('services.whatsapp.idioma_plantilla', 'es_AR'),
                [
                    $cliente,
                    $cuota->fecha_vencimiento->format('d/m/Y'),
                    '$'.number_format((float) $cuota->monto, 2, ',', '.'),
                ],
            );
            $mensaje->marcarEnviado($idExterno);

            return true;
        } catch (Throwable $error) {
            report($error);
            $mensaje->marcarFallido();

            return false;
        }
    }

    private function contenido(Cuota $cuota, string $estadoAviso): string
    {
        $fecha = $cuota->fecha_vencimiento->format('d/m/Y');
        $monto = number_format((float) $cuota->monto, 2, ',', '.');

        return $estadoAviso === 'vencida'
            ? "Recordatorio: la cuota #{$cuota->id_cuota} venció el {$fecha}. Importe pendiente: \${$monto}."
            : "Recordatorio: la cuota #{$cuota->id_cuota} vence el {$fecha}. Importe: \${$monto}.";
    }
}
