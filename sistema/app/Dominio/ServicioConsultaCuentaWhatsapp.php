<?php

namespace App\Dominio;

use App\Enums\EstadoServicio;
use App\Models\Cliente;
use App\Models\Conversacion;
use App\Models\Cuota;
use App\Models\Mensaje;
use App\Models\Servicio;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

/**
 * Prepara y envía al cliente el estado de cuenta consultado por WhatsApp.
 */
class ServicioConsultaCuentaWhatsapp
{
    public function __construct(
        private readonly ServicioFacturacion $facturacion,
        private readonly ServicioCuentaCorriente $cuentaCorriente,
        private readonly ServicioEnvioWhatsapp $envio,
    ) {}

    /** Actualiza la deuda y conserva la respuesta dentro de la conversación. */
    public function responder(Conversacion $conversacion, Cliente $cliente): Mensaje
    {
        $this->facturacion->actualizarEstadosVencidos();

        return $this->envio->enviarTextoDelBot(
            $conversacion,
            $this->crearRespuesta($cliente),
        );
    }

    /** Construye un resumen legible de servicios, cuotas y vencimientos. */
    public function crearRespuesta(Cliente $cliente): string
    {
        $servicios = $cliente->servicios()
            ->where('estado', '!=', EstadoServicio::Baja->value)
            ->with('plan')
            ->orderBy('id_servicio')
            ->get();
        $cuotas = $this->cuentaCorriente->cuotasDelCliente($cliente)
            ->whereNull('id_pago')
            ->values();
        $resumen = $this->cuentaCorriente->resumir($cliente);

        $secciones = [
            "Estado de cuenta de {$cliente->nombre_razon_social}",
            $this->describirServicios($servicios),
            $this->describirCuotas($cuotas),
            "Total pendiente: {$this->formatearImporte($resumen['total_pendiente'])}\n"
                ."Total vencido: {$this->formatearImporte($resumen['total_vencido'])}\n"
                .'Próximo vencimiento: '.$this->formatearFecha($resumen['proximo_vencimiento']),
        ];

        return implode("\n\n", $secciones)
            ."\n\nPodés volver al menú escribiendo la palabra *menú*.";
    }

    /** Enumera los servicios vigentes junto con el plan contratado. */
    private function describirServicios(Collection $servicios): string
    {
        if ($servicios->isEmpty()) {
            return 'Servicios: no tenés servicios vigentes registrados.';
        }

        $lineas = $servicios->map(function (Servicio $servicio): string {
            $estado = ucfirst($servicio->estado->value);

            return "• Servicio #{$servicio->id_servicio}: {$servicio->plan->nombre} "
                ."({$servicio->plan->velocidad}) · {$estado}";
        });

        return "Servicios:\n".$lineas->implode("\n");
    }

    /** Detalla hasta doce cuotas impagas para mantener breve el mensaje. */
    private function describirCuotas(Collection $cuotas): string
    {
        if ($cuotas->isEmpty()) {
            return 'Cuotas: no tenés cuotas pendientes. Tu cuenta está al día.';
        }

        $lineas = $cuotas->take(12)->map(function (Cuota $cuota): string {
            $periodo = CarbonImmutable::createFromFormat('!Y-m', $cuota->periodo)?->format('m/Y') ?? $cuota->periodo;
            $estado = ucfirst($cuota->estado->value);

            return "• {$periodo} · {$cuota->servicio->plan->nombre} · "
                .$this->formatearImporte($cuota->monto)
                ." · vence {$cuota->fecha_vencimiento->format('d/m/Y')} · {$estado}";
        });

        if ($cuotas->count() > 12) {
            $lineas->push('• Y '.($cuotas->count() - 12).' cuota(s) pendiente(s) más.');
        }

        return "Cuotas pendientes:\n".$lineas->implode("\n");
    }

    /** Presenta los importes con el formato monetario utilizado en Argentina. */
    private function formatearImporte(string $importe): string
    {
        return '$'.number_format((float) $importe, 2, ',', '.');
    }

    /** Convierte la fecha interna a una forma clara para el cliente. */
    private function formatearFecha(?string $fecha): string
    {
        return $fecha === null ? 'sin próximo vencimiento' : CarbonImmutable::parse($fecha)->format('d/m/Y');
    }
}
