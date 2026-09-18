<?php

namespace App\Dominio;

use App\Enums\EstadoAvisoVencimiento;
use App\Enums\EstadoCliente;
use App\Enums\EstadoCuota;
use App\Enums\EstadoServicio;
use App\Enums\TipoAvisoVencimiento;
use App\Models\AvisoVencimiento;
use App\Models\Conversacion;
use App\Models\Cuota;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

/**
 * Detecta cuotas que requieren notificación y envía avisos auditables.
 */
class ServicioAvisosVencimiento
{
    public function __construct(
        private readonly ServicioFacturacion $facturacion,
        private readonly ServicioConversaciones $conversaciones,
        private readonly ServicioEnvioWhatsapp $envio,
    ) {}

    /**
     * @return array{candidatos: int, enviados: int, omitidos: int, fallidos: int}
     */
    public function procesar(): array
    {
        $this->facturacion->actualizarEstadosVencidos();
        $resultado = ['candidatos' => 0, 'enviados' => 0, 'omitidos' => 0, 'fallidos' => 0];
        $hoy = CarbonImmutable::today();
        $diasAnticipacion = max(0, (int) config('villafane.avisos_vencimiento.dias_anticipacion', 3));

        $proximas = $this->consultaNotificable()
            ->where('estado', EstadoCuota::Pendiente->value)
            ->whereDate('fecha_vencimiento', '>=', $hoy)
            ->whereDate('fecha_vencimiento', '<=', $hoy->addDays($diasAnticipacion))
            ->get();
        $vencidas = $this->consultaNotificable()
            ->where('estado', EstadoCuota::Vencida->value)
            ->whereDate('fecha_vencimiento', '<', $hoy)
            ->get();

        foreach ($proximas as $cuota) {
            $this->procesarCuota($cuota, TipoAvisoVencimiento::Proximo, $resultado);
        }
        foreach ($vencidas as $cuota) {
            $this->procesarCuota($cuota, TipoAvisoVencimiento::Vencido, $resultado);
        }

        return $resultado;
    }

    /** Incluye solamente servicios y clientes activos con teléfono registrado. */
    private function consultaNotificable(): Builder
    {
        return Cuota::query()
            ->with(['servicio.cliente', 'servicio.plan'])
            ->whereNull('id_pago')
            ->whereHas('servicio', fn (Builder $consulta): Builder => $consulta
                ->where('estado', EstadoServicio::Activo->value))
            ->whereHas('servicio.cliente', fn (Builder $consulta): Builder => $consulta
                ->where('estado', EstadoCliente::Activo->value)
                ->whereNotNull('telefono_whatsapp')
                ->where('telefono_whatsapp', '!=', ''));
    }

    /**
     * @param  array{candidatos: int, enviados: int, omitidos: int, fallidos: int}  $resultado
     */
    private function procesarCuota(
        Cuota $cuota,
        TipoAvisoVencimiento $tipo,
        array &$resultado,
    ): void {
        $resultado['candidatos']++;
        $aviso = AvisoVencimiento::firstOrCreate(
            ['id_cuota' => $cuota->id_cuota, 'tipo' => $tipo->value],
            ['estado' => EstadoAvisoVencimiento::Pendiente],
        );

        if ($aviso->estado === EstadoAvisoVencimiento::Enviado) {
            $resultado['omitidos']++;

            return;
        }

        $aviso->update([
            'estado' => EstadoAvisoVencimiento::Pendiente,
            'fecha_hora_ultimo_intento' => now(),
            'detalle_error' => null,
        ]);

        $conversacion = null;
        try {
            $conversacion = $this->conversaciones->iniciarAvisoAutomatico($cuota->servicio->cliente);
            $aviso->update(['id_conversacion' => $conversacion->id_conversacion]);
            $mensaje = $this->envio->enviarTextoDelBot(
                $conversacion,
                $this->crearContenido($cuota, $tipo),
            );
            $this->conversaciones->cerrar($conversacion);
            $aviso->update([
                'id_mensaje' => $mensaje->id_mensaje,
                'estado' => EstadoAvisoVencimiento::Enviado,
                'fecha_hora_envio' => now(),
            ]);
            $resultado['enviados']++;
        } catch (Throwable $error) {
            $this->cerrarConversacionFallida($conversacion);
            $aviso->update([
                'estado' => EstadoAvisoVencimiento::Fallido,
                'detalle_error' => mb_substr($error->getMessage(), 0, 2000),
            ]);
            report($error);
            $resultado['fallidos']++;
        }
    }

    private function crearContenido(Cuota $cuota, TipoAvisoVencimiento $tipo): string
    {
        $fecha = $cuota->fecha_vencimiento->format('d/m/Y');
        $monto = '$'.number_format((float) $cuota->monto, 2, ',', '.');
        $servicio = $cuota->servicio;
        $ubicacion = "{$servicio->calle_instalacion} {$servicio->numero_instalacion}, {$servicio->localidad_instalacion}";

        $detalle = $tipo === TipoAvisoVencimiento::Proximo
            ? "te recordamos que la cuota {$cuota->periodo} por {$monto} vence el {$fecha}"
            : "la cuota {$cuota->periodo} por {$monto} venció el {$fecha} y continúa pendiente";

        return ucfirst($detalle).".\nServicio: {$ubicacion}.\nSi ya realizaste el pago, podés informarlo desde el menú del bot.";
    }

    private function cerrarConversacionFallida(?Conversacion $conversacion): void
    {
        if ($conversacion === null || ! $conversacion->estaActiva()) {
            return;
        }

        try {
            $this->conversaciones->cerrar($conversacion);
        } catch (Throwable) {
            // Se conserva el error original del envío como causa principal.
        }
    }
}
