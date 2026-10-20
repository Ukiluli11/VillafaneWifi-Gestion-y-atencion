<?php

namespace App\Dominio;

use App\Enums\EstadoCuota;
use App\Enums\EstadoServicio;
use App\Models\Cuota;
use App\Models\Mensaje;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

/**
 * Servicio ServicioNotificacionesVencimiento
 * Proceso batch/scheduler para enviar avisos preventivos de vencimiento de cuotas por WhatsApp.
 */
class ServicioNotificacionesVencimiento
{
    public function __construct(
        private readonly WhatsAppService $whatsAppService,
        private readonly ServicioConversaciones $servicioConversaciones,
    ) {}

    /**
     * Procesa y envía recordatorios de vencimiento a clientes con cuotas próximas a vencer o vencidas.
     * Caso de Uso: Notificar vencimientos por WhatsApp (Módulo 2)
     * Clase UML: ServicioNotificacionesVencimiento | Método UML: + enviar() : array
     *
     * @return array{enviadas: int, omitidas: int, fallidas: int}
     */
    public function enviar(?int $idCuota = null): array
    {
        $hoy = CarbonImmutable::today('America/Argentina/Buenos_Aires');
        $limite = $hoy->addDays(3);
        $resultado = ['enviadas' => 0, 'omitidas' => 0, 'fallidas' => 0];

        if (! config('services.whatsapp.recordatorios_habilitados', false)) {
            return $resultado;
        }

        $consulta = Cuota::query()
            ->with(['servicio.cliente'])
            ->whereNull('id_pago')
            ->whereIn('estado', [EstadoCuota::Pendiente->value, EstadoCuota::Vencida->value])
            ->whereDate('fecha_vencimiento', '<=', $limite->toDateString())
            ->whereHas('servicio', function (Builder $consulta): void {
                $consulta->where('estado', EstadoServicio::Activo->value)
                    ->whereHas('cliente', fn (Builder $cliente) => $cliente
                        ->whereNotNull('telefono_whatsapp')
                        ->where('telefono_whatsapp', '!=', ''));
            });

        if ($idCuota !== null) {
            $consulta->whereKey($idCuota);
        }

        $consulta->orderBy('id_cuota')
            ->chunkById(100, function ($cuotas) use ($hoy, &$resultado): void {
                foreach ($cuotas as $cuota) {
                    $estadoAviso = $cuota->fecha_vencimiento->toDateString() < $hoy->toDateString() ? 'vencida' : 'proxima';
                    $clave = "vencimiento:{$estadoAviso}:{$cuota->id_cuota}";
                    $cliente = $cuota->servicio->cliente;
                    if (! $this->destinatarioDeRecordatorios($cliente->telefono_whatsapp)) {
                        $resultado['omitidas']++;

                        continue;
                    }

                    $conversacion = $this->servicioConversaciones->obtenerOCrearConversacion($cliente->telefono_whatsapp);
                    $contenido = $this->contenido($cuota, $estadoAviso, $cliente->nombre_razon_social);
                    $mensaje = $this->servicioConversaciones->reservarNotificacion($conversacion, $clave, $contenido);

                    if ($mensaje === null) {
                        $resultado['omitidas']++;

                        continue;
                    }

                    if ($this->enviarMensaje($mensaje, $cliente->telefono_whatsapp, $cliente->nombre_razon_social, $cuota, $estadoAviso)) {
                        $resultado['enviadas']++;
                    } else {
                        $resultado['fallidas']++;
                    }
                }
            }, 'id_cuota');

        return $resultado;
    }

    private function destinatarioDeRecordatorios(string $telefono): bool
    {
        if (! $this->whatsAppService->destinatarioPermitido($telefono)) {
            return false;
        }
        if (config('services.whatsapp.modo_simulacion')) {
            return true;
        }
        $normalizar = static function (string $numero): string {
            $numero = preg_replace('/\D/', '', $numero) ?? '';

            return str_starts_with($numero, '549') ? '54'.substr($numero, 3) : $numero;
        };
        $permitidos = array_filter(array_map('trim', explode(',', (string) config('services.whatsapp.recordatorios_destinatarios'))));

        return in_array($normalizar($telefono), array_map($normalizar, $permitidos), true);
    }

    private function enviarMensaje(Mensaje $mensaje, string $telefono, string $cliente, Cuota $cuota, string $estadoAviso): bool
    {
        try {
            $idExterno = $this->whatsAppService->enviarPlantilla(
                $telefono,
                (string) config($estadoAviso === 'vencida'
                    ? 'services.whatsapp.plantilla_vencida'
                    : 'services.whatsapp.plantilla_vencimiento'),
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

    private function contenido(Cuota $cuota, string $estadoAviso, string $cliente): string
    {
        $fecha = $cuota->fecha_vencimiento->format('d/m/Y');
        $monto = number_format((float) $cuota->monto, 2, ',', '.');

        return $estadoAviso === 'vencida'
            ? sprintf('Villafañe Wifi (demostración académica). Hola %s, tu cuota con vencimiento %s por $%s figura pendiente. Si ya enviaste un comprobante, aguardá su revisión antes de volver a pagar.', $cliente, $fecha, $monto)
            : sprintf('Villafañe Wifi (demostración académica). Hola %s, te recordamos que tu cuota vence el %s por $%s. Consultá el detalle en el sistema antes de realizar cualquier pago.', $cliente, $fecha, $monto);
    }
}
