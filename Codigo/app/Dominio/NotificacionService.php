<?php

namespace App\Dominio;

use App\Models\Cliente;
use App\Models\Comprobante;
use App\Models\Pago;
use App\Models\Ticket;
use Illuminate\Support\Facades\Log;

/**
 * Servicio NotificacionService
 * Gestiona el envío de notificaciones automáticas de comprobantes (aprobados o rechazados).
 */
class NotificacionService
{
    /**
     * Emite la notificación de comprobante aprobado y pago acreditado al cliente.
     * Caso de Uso: Notificar acreditación de pago por WhatsApp
     * Clase UML: NotificacionService | Método UML: + notificarPagoAprobado(...) : void
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
     * Caso de Uso: Notificar rechazo con motivo por WhatsApp
     * Clase UML: NotificacionService | Método UML: + notificarPagoRechazado(...) : void
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

    /**
     * Emite la notificación de novedad o resolución de ticket al cliente por WhatsApp.
     * Caso de Uso: Notificar novedad por WhatsApp
     * Clase UML: NotificacionService | Método UML: + notificarNovedad(ticket: Ticket, detalle: String) : void
     */
    public function notificarNovedad(Ticket $ticket, string $detalle = ''): array
    {
        $cliente = $ticket->servicio->cliente;
        $telefono = $cliente->telefono_whatsapp ?? 'Sin teléfono';
        $estadoLabel = match ($ticket->estado->value) {
            'resuelto' => 'ha sido resuelto',
            'en_proceso' => 'está siendo atendido por un técnico',
            'cerrado' => 'ha sido cerrado satisfactoriamente',
            'reabierto' => 'ha sido reabierto para revisión técnica',
            default => 'ha sido actualizado',
        };

        $mensaje = sprintf(
            "Hola %s. Te informamos que tu ticket #%d sobre \"%s\" %s.%s Gracias por comunicarte con Villafañe Wifi.",
            $cliente->nombre_razon_social,
            $ticket->id_ticket,
            $ticket->descripcion,
            $estadoLabel,
            $detalle ? " Detalle: {$detalle}." : ''
        );

        Log::info('[NotificacionService] Novedad de ticket notificada al cliente', [
            'id_ticket' => $ticket->id_ticket,
            'id_cliente' => $cliente->id_cliente,
            'telefono' => $telefono,
            'estado' => $ticket->estado->value,
            'mensaje' => $mensaje,
        ]);

        return [
            'destinatario' => $telefono,
            'mensaje' => $mensaje,
            'estado' => 'enviado',
        ];
    }
}
