<?php

namespace App\Dominio;

use App\Contratos\PuertaEnlaceWhatsapp;
use App\Enums\EstadoEnvioMensaje;
use App\Enums\TipoEmisorMensaje;
use App\Enums\TipoMensaje;
use App\Models\Conversacion;
use App\Models\Mensaje;
use App\Models\Usuario;
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
        $contenido = $this->agregarSaludoEIdentidad($contenido);

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

    /**
     * Registra y envía una respuesta escrita por el usuario responsable.
     */
    public function enviarTextoDeUsuario(
        Conversacion $conversacion,
        Usuario $usuario,
        string $contenido,
    ): Mensaje {
        $mensaje = $this->conversaciones->registrarSaliente(
            $conversacion,
            TipoEmisorMensaje::UsuarioInterno,
            TipoMensaje::Texto,
            $contenido,
            usuario: $usuario,
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

    /**
     * Garantiza que cada respuesta se presente como el servicio de la empresa.
     * Conserva los mensajes que ya incluyen ambos elementos para no duplicarlos.
     */
    private function agregarSaludoEIdentidad(string $contenido): string
    {
        $textoNormalizado = mb_strtolower(trim($contenido));
        $tieneSaludo = str_starts_with($textoNormalizado, 'hola')
            || str_starts_with($textoNormalizado, 'buen día')
            || str_starts_with($textoNormalizado, 'buen dia')
            || str_starts_with($textoNormalizado, 'buenas tardes')
            || str_starts_with($textoNormalizado, 'buenas noches');
        $identificaEmpresa = str_contains($textoNormalizado, 'villafañe wifi');

        if ($tieneSaludo && $identificaEmpresa) {
            return $contenido;
        }

        return "Hola. Te atiende el servicio virtual de Villafañe Wifi.\n\n".$contenido;
    }
}
