<?php

namespace App\Dominio;

use App\Enums\EstadoEnvioMensaje;
use App\Enums\TipoMensaje;
use App\Models\Cliente;
use App\Models\Conversacion;
use App\Models\Mensaje;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Throwable;

/**
 * Traduce los eventos externos de WhatsApp a operaciones del dominio.
 *
 * Valida la autenticidad del evento, identifica al cliente, persiste su
 * historial y activa la respuesta inicial cuando corresponde.
 */
class ServicioRecepcionWhatsapp
{
    public function __construct(
        private readonly ServicioConversaciones $conversaciones,
        private readonly ServicioAtencionInicialWhatsapp $atencionInicial,
    ) {}

    /**
     * Compara el token recibido durante el alta del webhook con el configurado.
     */
    public function verificarSuscripcion(?string $modo, ?string $token): bool
    {
        $tokenConfigurado = (string) config('services.whatsapp.token_verificacion');

        return $modo === 'subscribe'
            && $tokenConfigurado !== ''
            && is_string($token)
            && hash_equals($tokenConfigurado, $token);
    }

    /**
     * Verifica que el cuerpo haya sido firmado con el secreto de la aplicación.
     */
    public function verificarFirma(string $contenido, ?string $firma): bool
    {
        $secreto = (string) config('services.whatsapp.secreto_aplicacion');
        if ($secreto === '' || ! is_string($firma) || ! str_starts_with($firma, 'sha256=')) {
            return false;
        }

        $firmaEsperada = 'sha256='.hash_hmac('sha256', $contenido, $secreto);

        return hash_equals($firmaEsperada, $firma);
    }

    /**
     * Procesa mensajes entrantes y cambios de estado incluidos en un webhook.
     *
     * @param  array<string, mixed>  $evento
     * @return array{mensajes_procesados: int, clientes_no_identificados: int, respuestas_enviadas: int, respuestas_fallidas: int, estados_actualizados: int}
     */
    public function procesar(array $evento): array
    {
        $resultado = [
            'mensajes_procesados' => 0,
            'clientes_no_identificados' => 0,
            'respuestas_enviadas' => 0,
            'respuestas_fallidas' => 0,
            'estados_actualizados' => 0,
        ];

        if (Arr::get($evento, 'object') !== 'whatsapp_business_account') {
            return $resultado;
        }

        foreach (Arr::get($evento, 'entry', []) as $entrada) {
            foreach (Arr::get($entrada, 'changes', []) as $cambio) {
                if (Arr::get($cambio, 'field') !== 'messages') {
                    continue;
                }

                $valor = Arr::get($cambio, 'value', []);
                $this->procesarMensajes(is_array($valor) ? $valor : [], $resultado);
                $this->procesarEstados(is_array($valor) ? $valor : [], $resultado);
            }
        }

        return $resultado;
    }

    /**
     * Identifica al cliente y registra cada mensaje admitido por el sistema.
     *
     * @param  array<string, mixed>  $valor
     * @param  array{mensajes_procesados: int, clientes_no_identificados: int, respuestas_enviadas: int, respuestas_fallidas: int, estados_actualizados: int}  $resultado
     */
    private function procesarMensajes(array $valor, array &$resultado): void
    {
        foreach (Arr::get($valor, 'messages', []) as $datosMensaje) {
            if (! is_array($datosMensaje)) {
                continue;
            }

            $numeroWhatsapp = $this->normalizarTelefono((string) Arr::get($datosMensaje, 'from', ''));
            $cliente = Cliente::query()->where('telefono_whatsapp', $numeroWhatsapp)->first();
            if ($cliente === null) {
                $resultado['clientes_no_identificados']++;
                $this->responderNoIdentificado($numeroWhatsapp, $resultado);

                continue;
            }

            $tipo = $this->obtenerTipoMensaje((string) Arr::get($datosMensaje, 'type', ''));
            if ($tipo === null) {
                continue;
            }

            $conversacion = $this->conversaciones->obtenerOIniciar($cliente, $numeroWhatsapp);
            $esPrimerMensaje = $conversacion->mensajes()->doesntExist();
            $mensaje = $this->conversaciones->registrarEntrante(
                $conversacion,
                $tipo,
                $this->obtenerContenido($datosMensaje, $tipo),
                $this->obtenerReferenciaArchivo($datosMensaje, $tipo),
                Arr::get($datosMensaje, 'id'),
                $this->obtenerFechaHora(Arr::get($datosMensaje, 'timestamp')),
            );
            if ($mensaje->wasRecentlyCreated) {
                $resultado['mensajes_procesados']++;
            }
            if ($esPrimerMensaje && $mensaje->wasRecentlyCreated) {
                $this->saludarIdentificado($conversacion, $cliente, $resultado);
            }
        }
    }

    /**
     * Envía el saludo personalizado sin impedir que Meta reciba confirmación
     * del webhook si el servicio de salida no está disponible.
     *
     * @param  array{mensajes_procesados: int, clientes_no_identificados: int, respuestas_enviadas: int, respuestas_fallidas: int, estados_actualizados: int}  $resultado
     */
    private function saludarIdentificado(Conversacion $conversacion, Cliente $cliente, array &$resultado): void
    {
        try {
            $this->atencionInicial->saludarCliente($conversacion, $cliente);
            $resultado['respuestas_enviadas']++;
        } catch (Throwable) {
            $resultado['respuestas_fallidas']++;
        }
    }

    /**
     * Responde al número desconocido sin inventar un registro de cliente.
     *
     * @param  array{mensajes_procesados: int, clientes_no_identificados: int, respuestas_enviadas: int, respuestas_fallidas: int, estados_actualizados: int}  $resultado
     */
    private function responderNoIdentificado(string $numeroWhatsapp, array &$resultado): void
    {
        try {
            $this->atencionInicial->informarClienteNoIdentificado($numeroWhatsapp);
            $resultado['respuestas_enviadas']++;
        } catch (Throwable) {
            $resultado['respuestas_fallidas']++;
        }
    }

    /**
     * Actualiza el seguimiento de los mensajes que fueron enviados por el sistema.
     *
     * @param  array<string, mixed>  $valor
     * @param  array{mensajes_procesados: int, clientes_no_identificados: int, respuestas_enviadas: int, respuestas_fallidas: int, estados_actualizados: int}  $resultado
     */
    private function procesarEstados(array $valor, array &$resultado): void
    {
        foreach (Arr::get($valor, 'statuses', []) as $datosEstado) {
            if (! is_array($datosEstado)) {
                continue;
            }

            $estado = match (Arr::get($datosEstado, 'status')) {
                'sent' => EstadoEnvioMensaje::Enviado,
                'delivered' => EstadoEnvioMensaje::Entregado,
                'read' => EstadoEnvioMensaje::Leido,
                'failed' => EstadoEnvioMensaje::Fallido,
                default => null,
            };
            if ($estado === null) {
                continue;
            }

            $actualizados = Mensaje::query()
                ->where('id_mensaje_externo', Arr::get($datosEstado, 'id'))
                ->update(['estado_envio' => $estado->value]);
            $resultado['estados_actualizados'] += $actualizados;
        }
    }

    /**
     * Convierte el nombre utilizado por Meta al enumerado del dominio.
     */
    private function obtenerTipoMensaje(string $tipo): ?TipoMensaje
    {
        return match ($tipo) {
            'text' => TipoMensaje::Texto,
            'image' => TipoMensaje::Imagen,
            'audio' => TipoMensaje::Audio,
            'document' => TipoMensaje::Documento,
            default => null,
        };
    }

    /**
     * Obtiene el texto o descripción asociado al tipo de mensaje recibido.
     *
     * @param  array<string, mixed>  $mensaje
     */
    private function obtenerContenido(array $mensaje, TipoMensaje $tipo): ?string
    {
        return match ($tipo) {
            TipoMensaje::Texto => Arr::get($mensaje, 'text.body'),
            TipoMensaje::Imagen => Arr::get($mensaje, 'image.caption'),
            TipoMensaje::Documento => Arr::get($mensaje, 'document.caption') ?? Arr::get($mensaje, 'document.filename'),
            TipoMensaje::Audio => null,
        };
    }

    /**
     * Conserva temporalmente el identificador de Meta hasta descargar el archivo.
     *
     * @param  array<string, mixed>  $mensaje
     */
    private function obtenerReferenciaArchivo(array $mensaje, TipoMensaje $tipo): ?string
    {
        if ($tipo === TipoMensaje::Texto) {
            return null;
        }

        $identificador = Arr::get($mensaje, $tipo->value.'.id');

        return is_string($identificador) && $identificador !== '' ? 'meta-media:'.$identificador : null;
    }

    /**
     * Usa la fecha informada por Meta o la hora actual si no está disponible.
     */
    private function obtenerFechaHora(mixed $marcaTemporal): CarbonImmutable
    {
        return is_numeric($marcaTemporal)
            ? CarbonImmutable::createFromTimestampUTC((int) $marcaTemporal)
            : CarbonImmutable::now();
    }

    /**
     * Unifica el teléfono con el formato numérico empleado por Cliente.
     */
    private function normalizarTelefono(string $telefono): string
    {
        return preg_replace('/\D+/', '', $telefono) ?? '';
    }
}
