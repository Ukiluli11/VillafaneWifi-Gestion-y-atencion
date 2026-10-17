<?php

namespace App\Dominio;

use App\Models\Cliente;
use App\Models\Comprobante;
use App\Models\Pago;
use Illuminate\Support\Facades\Log;

class NotificacionService
{
    /**
     * Emite la notificación de comprobante aprobado y pago acreditado al cliente.
     */
    public function notificarPagoAprobado(Cliente $cliente, Pago $pago, ?Comprobante $comprobante = null): array
    {
        $telefono = $cliente->telefono_whatsapp ?? 'Sin teléfono';
        $mensaje = sprintf(
            "¡Hola %s! Tu pago de $%s ha sido acreditado exitosamente con fecha %s. Tus cuotas han sido actualizadas. Gracias por confiar en Villafañe Wifi.",
            $cliente->nombre_razon_social,
            number_format((float) $pago->monto_total, 2, ',', '.'),
            $pago->fecha->format('d/m/Y')
        );

        Log::info('[NotificacionService] Pago aprobado notificado al cliente', [
            'id_cliente' => $cliente->id_cliente,
            'telefono' => $telefono,
            'id_pago' => $pago->id_pago,
            'id_comprobante' => $comprobante?->id_comprobante,
            'mensaje' => $mensaje,
        ]);

        return [
            'destinatario' => $telefono,
            'mensaje' => $mensaje,
            'estado' => 'enviado',
        ];
    }

    /**
     * Emite la notificación de comprobante rechazado al cliente con el motivo correspondiente.
     */
    public function notificarPagoRechazado(Cliente $cliente, Comprobante $comprobante, string $motivo): array
    {
        $telefono = $cliente->telefono_whatsapp ?? 'Sin teléfono';
        $mensaje = sprintf(
            "Hola %s. Te informamos que el comprobante enviado (Op: %s) no pudo ser validado. Motivo: %s. Por favor, comunícate con nosotros o reenvía un comprobante legible.",
            $cliente->nombre_razon_social,
            $comprobante->numero_operacion ?? 'S/N',
            $motivo
        );

        Log::warning('[NotificacionService] Pago rechazado notificado al cliente', [
            'id_cliente' => $cliente->id_cliente,
            'telefono' => $telefono,
            'id_comprobante' => $comprobante->id_comprobante,
            'motivo' => $motivo,
            'mensaje' => $mensaje,
        ]);

        return [
            'destinatario' => $telefono,
            'mensaje' => $mensaje,
            'estado' => 'enviado',
        ];
    }
}
