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
                    if (! $this->whatsAppService->destinatarioPermitido($cliente->telefono_whatsapp)) {
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
            ? sprintf('Villafañe Wifi (demostración): Hola %s, la cuota de prueba venció el %s y su importe simulado pendiente es $%s. No realices pagos; este aviso forma parte de una prueba.', $cliente, $fecha, $monto)
            : sprintf('Villafañe Wifi (demostración): Hola %s, te recordamos que la cuota de prueba vence el %s y su importe simulado es $%s. No realices pagos; este aviso forma parte de una prueba.', $cliente, $fecha, $monto);
    }
}
