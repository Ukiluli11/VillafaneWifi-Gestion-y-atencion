<?php

namespace App\Dominio;

use App\Enums\EstadoConversacion;
use App\Enums\EstadoEnvioMensaje;
use App\Enums\EstadoFlujoWhatsapp;
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
                'intentos_intencion' => 0,
            ]);
        });
    }

    /**
     * Recupera el alta en curso de un teléfono desconocido o inicia una nueva.
     */
    public function obtenerOIniciarNoIdentificada(string $numeroWhatsapp): Conversacion
    {
        $numeroNormalizado = preg_replace('/\D+/', '', $numeroWhatsapp) ?? '';
        if ($numeroNormalizado === '') {
            throw ValidationException::withMessages(['numero_whatsapp' => 'El número de WhatsApp es obligatorio.']);
        }

        return DB::transaction(function () use ($numeroNormalizado): Conversacion {
            $conversacion = Conversacion::query()
                ->whereNull('id_cliente')
                ->where('numero_whatsapp', $numeroNormalizado)
                ->whereIn('estado', [EstadoConversacion::Abierta->value, EstadoConversacion::Escalada->value])
                ->latest('fecha_hora_inicio')
                ->lockForUpdate()
                ->first();

            return $conversacion ?? Conversacion::create([
                'id_cliente' => null,
                'numero_whatsapp' => $numeroNormalizado,
                'fecha_hora_inicio' => now(),
                'estado' => EstadoConversacion::Abierta,
                'modo_atencion' => ModoAtencion::Bot,
                'estado_flujo' => EstadoFlujoWhatsapp::EsperandoDocumento,
                'intentos_intencion' => 0,
                'datos_registro' => null,
            ]);
        });
    }

    /** Inicia una conversación independiente para una notificación automática. */
    public function iniciarAvisoAutomatico(Cliente $cliente): Conversacion
    {
        $numeroWhatsapp = preg_replace('/\D+/', '', (string) $cliente->telefono_whatsapp) ?? '';
        if ($numeroWhatsapp === '') {
            throw ValidationException::withMessages([
                'numero_whatsapp' => 'El cliente no tiene un número de WhatsApp registrado.',
            ]);
        }

        return Conversacion::create([
            'id_cliente' => $cliente->id_cliente,
            'numero_whatsapp' => $numeroWhatsapp,
            'fecha_hora_inicio' => now(),
            'estado' => EstadoConversacion::Abierta,
            'modo_atencion' => ModoAtencion::Bot,
            'estado_flujo' => EstadoFlujoWhatsapp::Menu,
            'intentos_intencion' => 0,
        ]);
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
        return $this->tomarParaAtencion($conversacion, $usuario)->conversacion;
    }

    /**
     * Detiene al bot y deja la conversación en la cola de atención humana.
     */
    public function derivarAColaAtencion(Conversacion $conversacion): Conversacion
    {
        return DB::transaction(function () use ($conversacion): Conversacion {
            $conversacionBloqueada = Conversacion::query()
                ->lockForUpdate()
                ->findOrFail($conversacion->id_conversacion);

            if ($conversacionBloqueada->estado === EstadoConversacion::Cerrada) {
                throw ValidationException::withMessages([
                    'conversacion' => 'No se puede derivar una conversación cerrada.',
                ]);
            }

            $conversacionBloqueada->update([
                'estado' => EstadoConversacion::Escalada,
                'modo_atencion' => ModoAtencion::UsuarioInterno,
                'estado_flujo' => EstadoFlujoWhatsapp::Menu,
                'intentos_intencion' => 0,
            ]);

            return $conversacionBloqueada->refresh();
        });
    }

    /** Incrementa el contador con bloqueo para no perder mensajes simultáneos. */
    public function registrarIntentoIntencionNoReconocida(Conversacion $conversacion): int
    {
        return DB::transaction(function () use ($conversacion): int {
            $conversacionBloqueada = Conversacion::query()
                ->lockForUpdate()
                ->findOrFail($conversacion->id_conversacion);
            $intentos = min(2, $conversacionBloqueada->intentos_intencion + 1);
            $conversacionBloqueada->update(['intentos_intencion' => $intentos]);

            return $intentos;
        });
    }

    /** Reinicia los intentos después de interpretar correctamente al cliente. */
    public function reiniciarIntentosIntencion(Conversacion $conversacion): void
    {
        if ($conversacion->intentos_intencion !== 0) {
            $conversacion->update(['intentos_intencion' => 0]);
        }
    }

    /**
     * Toma una conversación con bloqueo de fila para impedir que dos usuarios
     * se la asignen al mismo tiempo.
     */
    public function tomarParaAtencion(
        Conversacion $conversacion,
        Usuario $usuario,
    ): ResultadoTomaConversacion {
        $usuario->loadMissing(['empleado', 'administrador']);
        if ($usuario->empleado === null && $usuario->administrador === null) {
            throw ValidationException::withMessages([
                'conversacion' => 'La atención requiere un empleado o administrador.',
            ]);
        }

        return DB::transaction(function () use ($conversacion, $usuario): ResultadoTomaConversacion {
            $conversacionBloqueada = Conversacion::query()
                ->lockForUpdate()
                ->findOrFail($conversacion->id_conversacion);

            if ($conversacionBloqueada->estado === EstadoConversacion::Cerrada) {
                throw ValidationException::withMessages([
                    'conversacion' => 'No se puede tomar una conversación cerrada.',
                ]);
            }

            if ($conversacionBloqueada->id_usuario_atencion !== null
                && $conversacionBloqueada->id_usuario_atencion !== $usuario->id_usuario) {
                throw ValidationException::withMessages([
                    'conversacion' => 'La conversación ya fue tomada por otro usuario.',
                ]);
            }

            $nuevaAsignacion = $conversacionBloqueada->id_usuario_atencion === null;
            if ($nuevaAsignacion) {
                $conversacionBloqueada->update([
                    'id_usuario_atencion' => $usuario->id_usuario,
                    'estado' => EstadoConversacion::Escalada,
                    'modo_atencion' => ModoAtencion::UsuarioInterno,
                    'estado_flujo' => EstadoFlujoWhatsapp::Menu,
                    'intentos_intencion' => 0,
                    'inicio_atencion' => now(),
                    'fin_atencion' => null,
                ]);
            }

            return new ResultadoTomaConversacion(
                $conversacionBloqueada->refresh(),
                $nuevaAsignacion,
            );
        });
    }

    public function cerrar(Conversacion $conversacion): Conversacion
    {
        return DB::transaction(function () use ($conversacion): Conversacion {
            $conversacionBloqueada = Conversacion::query()
                ->lockForUpdate()
                ->findOrFail($conversacion->id_conversacion);

            if ($conversacionBloqueada->estado === EstadoConversacion::Cerrada) {
                throw ValidationException::withMessages([
                    'conversacion' => 'La conversación ya se encuentra cerrada.',
                ]);
            }

            $ahora = now();
            $conversacionBloqueada->update([
                'estado' => EstadoConversacion::Cerrada,
                'estado_flujo' => EstadoFlujoWhatsapp::Menu,
                'intentos_intencion' => 0,
                'fecha_hora_cierre' => $ahora,
                'fin_atencion' => $conversacionBloqueada->modo_atencion === ModoAtencion::UsuarioInterno
                    ? $ahora
                    : null,
            ]);

            return $conversacionBloqueada->refresh();
        });
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
