<?php

namespace App\Dominio;

use App\Enums\EstadoConversacion;
use App\Enums\EstadoEnvioMensaje;
use App\Enums\ModoAtencion;
use App\Enums\TipoEmisorMensaje;
use App\Enums\TipoMensaje;
use App\Models\Cliente;
use App\Models\Conversacion;
use App\Models\Mensaje;
use App\Models\Usuario;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ServicioConversaciones
{
    public function obtenerOIniciar(Cliente $cliente, string $numeroWhatsapp): Conversacion
    {
        $numeroNormalizado = preg_replace('/\D+/', '', $numeroWhatsapp) ?? '';
        if ($numeroNormalizado === '') {
            throw ValidationException::withMessages(['numero_whatsapp' => 'El número de WhatsApp es obligatorio.']);
        }

        return DB::transaction(function () use ($cliente, $numeroNormalizado): Conversacion {
            $conversacion = Conversacion::query()
                ->whereBelongsTo($cliente)
                ->whereIn('estado', [EstadoConversacion::Abierta->value, EstadoConversacion::Escalada->value])
                ->latest('fecha_hora_inicio')
                ->lockForUpdate()
                ->first();

            return $conversacion ?? Conversacion::create([
                'id_cliente' => $cliente->id_cliente,
                'numero_whatsapp' => $numeroNormalizado,
                'fecha_hora_inicio' => now(),
                'estado' => EstadoConversacion::Abierta,
                'modo_atencion' => ModoAtencion::Bot,
            ]);
        });
    }

    public function registrarEntrante(
        Conversacion $conversacion,
        TipoMensaje $tipo,
        ?string $contenido = null,
        ?string $archivoAdjunto = null,
        ?string $identificadorExterno = null,
        ?DateTimeInterface $fechaHora = null,
    ): Mensaje {
        return $this->registrar(
            $conversacion,
            TipoEmisorMensaje::Cliente,
            EstadoEnvioMensaje::Recibido,
            $tipo,
            $contenido,
            $archivoAdjunto,
            $identificadorExterno,
            fechaHora: $fechaHora,
        );
    }

    public function registrarSaliente(
        Conversacion $conversacion,
        TipoEmisorMensaje $emisor,
        TipoMensaje $tipo,
        ?string $contenido = null,
        ?string $archivoAdjunto = null,
        ?Usuario $usuario = null,
        ?DateTimeInterface $fechaHora = null,
    ): Mensaje {
        if ($emisor === TipoEmisorMensaje::Cliente) {
            throw ValidationException::withMessages(['tipo_emisor' => 'Un mensaje saliente no puede pertenecer al cliente.']);
        }
        if ($emisor === TipoEmisorMensaje::UsuarioInterno && $usuario === null) {
            throw ValidationException::withMessages(['id_usuario' => 'Debe indicar quién envió el mensaje.']);
        }

        return $this->registrar(
            $conversacion,
            $emisor,
            EstadoEnvioMensaje::Pendiente,
            $tipo,
            $contenido,
            $archivoAdjunto,
            fechaHora: $fechaHora,
            usuario: $usuario,
        );
    }

    public function derivarAUsuario(Conversacion $conversacion, Usuario $usuario): Conversacion
    {
        if ($usuario->empleado === null && $usuario->administrador === null) {
            throw ValidationException::withMessages(['id_usuario' => 'La atención requiere un empleado o administrador.']);
        }

        $conversacion->update([
            'id_usuario_atencion' => $usuario->id_usuario,
            'estado' => EstadoConversacion::Escalada,
            'modo_atencion' => ModoAtencion::UsuarioInterno,
            'inicio_atencion' => now(),
            'fin_atencion' => null,
        ]);

        return $conversacion->refresh();
    }

    public function cerrar(Conversacion $conversacion): Conversacion
    {
        $ahora = now();
        $conversacion->update([
            'estado' => EstadoConversacion::Cerrada,
            'fecha_hora_cierre' => $ahora,
            'fin_atencion' => $conversacion->modo_atencion === ModoAtencion::UsuarioInterno ? $ahora : null,
        ]);

        return $conversacion->refresh();
    }

    private function registrar(
        Conversacion $conversacion,
        TipoEmisorMensaje $emisor,
        EstadoEnvioMensaje $estado,
        TipoMensaje $tipo,
        ?string $contenido,
        ?string $archivoAdjunto,
        ?string $identificadorExterno = null,
        ?Usuario $usuario = null,
        ?DateTimeInterface $fechaHora = null,
    ): Mensaje {
        if (! $conversacion->estaActiva()) {
            throw ValidationException::withMessages(['id_conversacion' => 'No se pueden agregar mensajes a una conversación cerrada.']);
        }
        if (blank($contenido) && blank($archivoAdjunto)) {
            throw ValidationException::withMessages(['contenido' => 'El mensaje debe contener texto o un archivo.']);
        }

        return DB::transaction(function () use (
            $conversacion,
            $usuario,
            $identificadorExterno,
            $tipo,
            $contenido,
            $archivoAdjunto,
            $emisor,
            $estado,
            $fechaHora,
        ): Mensaje {
            $datos = [
                'id_conversacion' => $conversacion->id_conversacion,
                'id_usuario' => $usuario?->id_usuario,
                'fecha_hora' => $fechaHora ?? now(),
                'tipo' => $tipo,
                'contenido' => $contenido,
                'archivo_adjunto' => $archivoAdjunto,
                'tipo_emisor' => $emisor,
                'estado_envio' => $estado,
            ];

            if ($identificadorExterno === null) {
                return Mensaje::create($datos);
            }

            return Mensaje::firstOrCreate(
                ['id_mensaje_externo' => $identificadorExterno],
                $datos,
            );
        });
    }
}
