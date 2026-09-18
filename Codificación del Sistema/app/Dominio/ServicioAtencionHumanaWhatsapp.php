<?php

namespace App\Dominio;

use App\Enums\EstadoConversacion;
use App\Enums\ModoAtencion;
use App\Models\Conversacion;
use App\Models\Mensaje;
use App\Models\Usuario;
use Illuminate\Validation\ValidationException;

/**
 * Coordina la toma y las respuestas humanas de una conversación de WhatsApp.
 */
class ServicioAtencionHumanaWhatsapp
{
    public function __construct(
        private readonly ServicioConversaciones $conversaciones,
        private readonly ServicioEnvioWhatsapp $envioWhatsapp,
    ) {}

    /**
     * Asigna la conversación al usuario y lo presenta ante el cliente.
     */
    public function tomar(Conversacion $conversacion, Usuario $usuario): ResultadoTomaConversacion
    {
        $resultado = $this->conversaciones->tomarParaAtencion($conversacion, $usuario);

        if ($resultado->nuevaAsignacion) {
            $this->envioWhatsapp->enviarTextoDeUsuario(
                $resultado->conversacion,
                $usuario,
                "Hola, soy {$usuario->nombre_usuario}, del equipo de Villafañe Wifi. "
                .'Desde este momento voy a continuar personalmente con tu atención.',
            );
        }

        return $resultado;
    }

    /**
     * Envía una respuesta únicamente si el usuario conserva la atención.
     */
    public function responder(Conversacion $conversacion, Usuario $usuario, string $contenido): Mensaje
    {
        $conversacion->refresh();

        if ($conversacion->estado === EstadoConversacion::Cerrada) {
            throw ValidationException::withMessages([
                'contenido' => 'La conversación está cerrada y no admite nuevas respuestas.',
            ]);
        }

        if ($conversacion->modo_atencion !== ModoAtencion::UsuarioInterno
            || $conversacion->id_usuario_atencion !== $usuario->id_usuario) {
            throw ValidationException::withMessages([
                'contenido' => 'Solo el usuario responsable puede responder esta conversación.',
            ]);
        }

        return $this->envioWhatsapp->enviarTextoDeUsuario($conversacion, $usuario, $contenido);
    }

    /**
     * Finaliza la atención y avisa al cliente antes de bloquear nuevos mensajes.
     */
    public function cerrar(Conversacion $conversacion, Usuario $usuario): Conversacion
    {
        $conversacion->refresh();
        $usuario->loadMissing('administrador');

        if ($conversacion->estado === EstadoConversacion::Cerrada) {
            throw ValidationException::withMessages([
                'conversacion' => 'La conversación ya se encuentra cerrada.',
            ]);
        }

        $esResponsable = $conversacion->id_usuario_atencion === $usuario->id_usuario;
        $esAdministrador = $usuario->administrador !== null;
        if ($conversacion->modo_atencion === ModoAtencion::UsuarioInterno
            && ! $esResponsable
            && ! $esAdministrador) {
            throw ValidationException::withMessages([
                'conversacion' => 'Solo el usuario responsable o un administrador puede cerrar esta conversación.',
            ]);
        }

        $this->envioWhatsapp->enviarTextoDeUsuario(
            $conversacion,
            $usuario,
            "Hola. Soy {$usuario->nombre_usuario}, de Villafañe Wifi. "
                .'Damos por finalizada esta conversación. Si necesitás otra gestión, escribinos nuevamente.',
        );

        return $this->conversaciones->cerrar($conversacion->refresh());
    }
}
