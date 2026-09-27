<?php

namespace App\Dominio;

use App\Enums\EstadoConversacion;
use App\Enums\EstadoEnvioMensaje;
use App\Enums\TipoEmisorMensaje;
use App\Models\Conversacion;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class ServicioAtencionHumana
{
    public function __construct(
        private readonly WhatsAppService $whatsAppService,
        private readonly ServicioConversaciones $servicioConversaciones,
    ) {}

    public function tomar(Conversacion $conversacion, Usuario $usuario): Conversacion
    {
        $conversacion = DB::transaction(function () use ($conversacion, $usuario): Conversacion {
            $actual = Conversacion::query()->lockForUpdate()->findOrFail($conversacion->id_conversacion);
            $this->verificarAbierta($actual);

            if ($actual->id_usuario_atencion !== null && $actual->id_usuario_atencion !== $usuario->id_usuario) {
                throw ValidationException::withMessages([
                    'conversacion' => 'La conversación ya fue tomada por otro usuario.',
                ]);
            }

            $actual->derivarAUsuario($usuario);
            $ticket = $actual->tickets()
                ->whereNull('id_usuario_asignado')
                ->latest('id_ticket')
                ->first();
            $ticket?->asignar($usuario);

            return $actual->refresh();
        });

        $contenido = "Hola, soy {$usuario->nombre_usuario}. Desde ahora voy a continuar personalmente con tu consulta.";
        try {
            $idExterno = $this->whatsAppService->enviarMensaje($conversacion->numero_whatsapp, $contenido);
            $estado = EstadoEnvioMensaje::Enviado;
        } catch (Throwable $error) {
            $idExterno = null;
            $estado = EstadoEnvioMensaje::Fallido;
        }

        $this->servicioConversaciones->registrarMensajeSaliente(
            $conversacion,
            $contenido,
            $idExterno,
            $estado,
            TipoEmisorMensaje::UsuarioInterno,
            $usuario,
        );

        if (isset($error)) {
            throw $error;
        }

        return $conversacion->refresh();
    }

    public function responder(Conversacion $conversacion, Usuario $usuario, string $contenido): void
    {
        $conversacion->refresh();
        $this->verificarAsignacion($conversacion, $usuario);
        $contenido = trim($contenido);

        try {
            $idExterno = $this->whatsAppService->enviarMensaje($conversacion->numero_whatsapp, $contenido);
            $estado = EstadoEnvioMensaje::Enviado;
        } catch (Throwable $error) {
            $idExterno = null;
            $estado = EstadoEnvioMensaje::Fallido;
        }

        $this->servicioConversaciones->registrarMensajeSaliente(
            $conversacion,
            $contenido,
            $idExterno,
            $estado,
            TipoEmisorMensaje::UsuarioInterno,
            $usuario,
        );

        if (isset($error)) {
            throw $error;
        }
    }

    public function cerrar(Conversacion $conversacion, Usuario $usuario): void
    {
        DB::transaction(function () use ($conversacion, $usuario): void {
            $actual = Conversacion::query()->lockForUpdate()->findOrFail($conversacion->id_conversacion);
            $this->verificarAbierta($actual);

            $esResponsable = $actual->id_usuario_atencion === $usuario->id_usuario;
            $esAdministrador = $usuario->administrador()->exists();
            if (! $esResponsable && ! $esAdministrador) {
                throw ValidationException::withMessages([
                    'conversacion' => 'Solamente el responsable o un administrador pueden cerrar la conversación.',
                ]);
            }

            $actual->cerrar();
        });
    }

    private function verificarAsignacion(Conversacion $conversacion, Usuario $usuario): void
    {
        $this->verificarAbierta($conversacion);

        if ($conversacion->id_usuario_atencion !== $usuario->id_usuario) {
            throw ValidationException::withMessages([
                'conversacion' => 'Primero debés tomar la conversación para poder gestionarla.',
            ]);
        }
    }

    private function verificarAbierta(Conversacion $conversacion): void
    {
        if ($conversacion->estado === EstadoConversacion::Cerrada) {
            throw ValidationException::withMessages([
                'conversacion' => 'La conversación ya está cerrada.',
            ]);
        }
    }
}
