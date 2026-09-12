<?php

namespace App\Dominio;

use App\Contratos\PuertaEnlaceWhatsapp;
use App\Enums\EstadoEnvioMensaje;
use App\Enums\TipoEmisorMensaje;
use App\Enums\TipoMensaje;
use App\Models\Conversacion;
use App\Models\Mensaje;
use Throwable;

/**
 * Coordina el registro local y el envío externo de respuestas de WhatsApp.
 */
class ServicioEnvioWhatsapp
{
    public function __construct(
        private readonly PuertaEnlaceWhatsapp $puertaEnlace,
        private readonly ServicioConversaciones $conversaciones,
    ) {}

    /**
     * Registra y envía una respuesta de texto producida por el bot.
     *
     * El mensaje queda como pendiente antes de contactar a Meta. Si la API
     * responde correctamente, guarda su ID externo; si falla, conserva el
     * registro con estado fallido para poder auditarlo o reintentarlo.
     */
    public function enviarTextoDelBot(Conversacion $conversacion, string $contenido): Mensaje
    {
        $mensaje = $this->conversaciones->registrarSaliente(
            $conversacion,
            TipoEmisorMensaje::Bot,
            TipoMensaje::Texto,
            $contenido,
        );

        try {
            $identificadorExterno = $this->puertaEnlace->enviarTexto(
                $conversacion->numero_whatsapp,
                $contenido,
            );

            $mensaje->update([
                'id_mensaje_externo' => $identificadorExterno,
                'estado_envio' => EstadoEnvioMensaje::Enviado,
            ]);
        } catch (Throwable $error) {
            $mensaje->marcarComoFallido();

            throw $error;
        }

        return $mensaje->refresh();
    }
}
