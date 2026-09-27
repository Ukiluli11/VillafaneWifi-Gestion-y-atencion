<?php

namespace App\Dominio;

use App\Enums\EstadoConversacion;
use App\Enums\EstadoEnvioMensaje;
use App\Enums\ModoAtencion;
use App\Enums\TipoEmisorMensaje;
use App\Enums\TipoMensaje;
use App\Models\Conversacion;
use App\Models\Mensaje;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ServicioConversaciones
{
    public function obtenerOCrearConversacion(string $numeroWhatsapp): Conversacion
    {
        $numeroNormalizado = $this->normalizarNumeroWhatsapp($numeroWhatsapp);

        return DB::transaction(function () use ($numeroNormalizado): Conversacion {
            $conversacion = Conversacion::query()
                ->activas()
                ->where('numero_whatsapp', $numeroNormalizado)
                ->latest('id_conversacion')
                ->lockForUpdate()
                ->first();

            if ($conversacion !== null) {
                return $conversacion;
            }

            return Conversacion::create([
                'numero_whatsapp' => $numeroNormalizado,
                'fecha_hora_inicio' => now(),
                'estado' => EstadoConversacion::Abierta,
                'modo_atencion' => ModoAtencion::Bot,
            ]);
        });
    }

    /**
     * @param  array{id_externo: string, tipo: string, contenido: ?string, referencia_archivo: ?string, fecha_hora: mixed}  $datos
     */
    public function registrarMensajeEntrante(Conversacion $conversacion, array $datos): Mensaje
    {
        $tipo = TipoMensaje::tryFrom($datos['tipo']) ?? TipoMensaje::Desconocido;

        return Mensaje::firstOrCreate(
            ['id_mensaje_externo' => $datos['id_externo']],
            [
                'id_conversacion' => $conversacion->id_conversacion,
                'fecha_hora' => $datos['fecha_hora'],
                'tipo' => $tipo,
                'contenido' => $datos['contenido'],
                'archivo_adjunto' => $datos['referencia_archivo'],
                'tipo_emisor' => TipoEmisorMensaje::Cliente,
                'estado_envio' => EstadoEnvioMensaje::Recibido,
            ],
        );
    }

    public function registrarMensajeSaliente(
        Conversacion $conversacion,
        string $contenido,
        ?string $idExterno,
        EstadoEnvioMensaje $estado,
        TipoEmisorMensaje $emisor = TipoEmisorMensaje::Bot,
        ?Usuario $usuario = null,
    ): Mensaje {
        return Mensaje::create([
            'id_conversacion' => $conversacion->id_conversacion,
            'id_usuario' => $usuario?->id_usuario,
            'id_mensaje_externo' => $idExterno,
            'fecha_hora' => now(),
            'tipo' => TipoMensaje::Texto,
            'contenido' => $contenido,
            'tipo_emisor' => $emisor,
            'estado_envio' => $estado,
        ]);
    }

    public function actualizarEstadoExterno(string $idExterno, EstadoEnvioMensaje $estado): void
    {
        DB::transaction(function () use ($idExterno, $estado): void {
            $mensaje = Mensaje::query()
                ->where('id_mensaje_externo', $idExterno)
                ->lockForUpdate()
                ->first();

            if ($mensaje !== null && $mensaje->estado_envio->puedeAvanzarA($estado)) {
                $mensaje->update(['estado_envio' => $estado]);
            }
        });
    }

    public function reservarNotificacion(
        Conversacion $conversacion,
        string $clave,
        string $contenido,
    ): ?Mensaje {
        $mensaje = Mensaje::firstOrCreate(
            ['clave_idempotencia' => $clave],
            [
                'id_conversacion' => $conversacion->id_conversacion,
                'fecha_hora' => now(),
                'tipo' => TipoMensaje::Notificacion,
                'contenido' => $contenido,
                'tipo_emisor' => TipoEmisorMensaje::Sistema,
                'estado_envio' => EstadoEnvioMensaje::Pendiente,
            ],
        );

        if ($mensaje->wasRecentlyCreated) {
            return $mensaje;
        }

        if ($mensaje->estado_envio === EstadoEnvioMensaje::Fallido) {
            $mensaje->update(['estado_envio' => EstadoEnvioMensaje::Pendiente, 'fecha_hora' => now()]);

            return $mensaje;
        }

        return null;
    }

    public function normalizarNumeroWhatsapp(string $numero): string
    {
        $normalizado = preg_replace('/\D+/', '', $numero) ?? '';
        if ($normalizado === '') {
            throw new InvalidArgumentException('El número de WhatsApp no puede estar vacío.');
        }

        return $normalizado;
    }
}
