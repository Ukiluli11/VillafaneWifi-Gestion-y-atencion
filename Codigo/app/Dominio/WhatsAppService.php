<?php

namespace App\Dominio;

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Servicio WhatsAppService
 * Integración con la API oficial de WhatsApp Cloud API (webhooks, recepción y envío de mensajes).
 */
class WhatsAppService
{
    /**
     * Valida el token de verificación en la suscripción del webhook de WhatsApp.
     */
    public function verificarSuscripcion(?string $modo, ?string $token, ?string $reto): ?string
    {
        $tokenEsperado = (string) config('services.whatsapp.verify_token');

        if ($modo !== 'subscribe' || $reto === null || $tokenEsperado === '' || $token === null) {
            return null;
        }

        return hash_equals($tokenEsperado, $token) ? $reto : null;
    }

    /**
     * Valida la firma HMAC SHA-256 recibida en la cabecera de la solicitud de Meta.
     */
    public function validarFirma(string $contenido, ?string $firma): bool
    {
        $validar = filter_var(config('services.whatsapp.validar_firma', true), FILTER_VALIDATE_BOOL);
        if (! $validar) {
            return true;
        }

        $secreto = (string) config('services.whatsapp.app_secret');
        if ($secreto === '' || $firma === null || ! str_starts_with($firma, 'sha256=')) {
            return false;
        }

        $firmaEsperada = 'sha256='.hash_hmac('sha256', $contenido, $secreto);

        return hash_equals($firmaEsperada, $firma);
    }

    /**
     * Extrae y normaliza los mensajes entrantes contenidos en el payload del webhook.
     *
     * @param  array<string, mixed>  $payload
     * @return list<array{id_externo: string, telefono: string, tipo: string, contenido: ?string, referencia_archivo: ?string, fecha_hora: CarbonImmutable}>
     */
    public function extraerMensajes(array $payload): array
    {
        $resultado = [];

        foreach ((array) ($payload['entry'] ?? []) as $entrada) {
            foreach ((array) ($entrada['changes'] ?? []) as $cambio) {
                $valor = (array) ($cambio['value'] ?? []);
                foreach ((array) ($valor['messages'] ?? []) as $mensaje) {
                    if (! is_array($mensaje) || empty($mensaje['id']) || empty($mensaje['from'])) {
                        continue;
                    }

                    $tipo = (string) ($mensaje['type'] ?? 'desconocido');
                    $idMedia = $this->obtenerIdMedia($mensaje, $tipo);
                    $resultado[] = [
                        'id_externo' => (string) $mensaje['id'],
                        'telefono' => (string) $mensaje['from'],
                        'tipo' => $this->normalizarTipo($tipo),
                        'contenido' => $this->obtenerContenido($mensaje, $tipo),
                        'referencia_archivo' => $idMedia === null ? null : 'meta://'.$idMedia,
                        'fecha_hora' => isset($mensaje['timestamp'])
                            ? CarbonImmutable::createFromTimestampUTC((int) $mensaje['timestamp'])
                            : CarbonImmutable::now(),
                    ];
                }
            }
        }

        return $resultado;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array{id_externo: string, estado: string}>
     */
    public function extraerEstados(array $payload): array
    {
        $resultado = [];
        $equivalencias = [
            'sent' => 'enviado',
            'delivered' => 'entregado',
            'read' => 'leido',
            'failed' => 'fallido',
        ];

        foreach ((array) ($payload['entry'] ?? []) as $entrada) {
            foreach ((array) ($entrada['changes'] ?? []) as $cambio) {
                foreach ((array) data_get($cambio, 'value.statuses', []) as $estado) {
                    if (! is_array($estado) || empty($estado['id'])) {
                        continue;
                    }
                    $normalizado = $equivalencias[(string) ($estado['status'] ?? '')] ?? null;
                    if ($normalizado !== null) {
                        $resultado[] = ['id_externo' => (string) $estado['id'], 'estado' => $normalizado];
                    }
                }
            }
        }

        return $resultado;
    }

    /** @return array{contenido: string, mime_type: string, tamanio_bytes: int} */
    public function descargarArchivo(string $referencia): array
    {
        if ($this->modoSimulacion() && str_starts_with($referencia, 'meta://simulado-')) {
            $esPdf = str_contains($referencia, '-documento-');
            $contenido = $esPdf
                ? "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF"
                : base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);

            return [
                'contenido' => (string) $contenido,
                'mime_type' => $esPdf ? 'application/pdf' : 'image/png',
                'tamanio_bytes' => strlen((string) $contenido),
            ];
        }

        $idMedia = str_starts_with($referencia, 'meta://') ? substr($referencia, 7) : '';
        $token = (string) config('services.whatsapp.access_token');
        $version = (string) config('services.whatsapp.graph_api_version');
        $maximo = (int) config('services.whatsapp.media_max_kb', 10240) * 1024;

        if ($idMedia === '' || $token === '' || $version === '') {
            throw new RuntimeException('No se pudo resolver la referencia del archivo de WhatsApp.');
        }

        $metadatos = $this->solicitudMeta($token)
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(15)
            ->get("https://graph.facebook.com/{$version}/{$idMedia}");
        $metadatos->throw();

        $url = $metadatos->json('url');
        $mime = mb_strtolower(trim((string) $metadatos->json('mime_type', 'application/octet-stream')));
        $tamanioDeclarado = (int) $metadatos->json('file_size', 0);
        if (! is_string($url) || $url === '' || ($tamanioDeclarado > 0 && $tamanioDeclarado > $maximo)) {
            throw new RuntimeException('El archivo supera el tamaño máximo permitido o no está disponible.');
        }

        $archivo = $this->solicitudMeta($token)->connectTimeout(5)->timeout(30)->get($url);
        $archivo->throw();
        $contenido = $archivo->body();
        $tamanio = strlen($contenido);
        if ($tamanio === 0 || $tamanio > $maximo) {
            throw new RuntimeException('El archivo está vacío o supera el tamaño máximo permitido.');
        }

        return ['contenido' => $contenido, 'mime_type' => $mime, 'tamanio_bytes' => $tamanio];
    }

    /**
     * Envía un mensaje de texto plano al número de WhatsApp del cliente.
     * Caso de Uso: Procesar mensaje entrante / Enviar respuesta (Módulo 2)
     * Clase UML: WhatsAppService | Método UML: + enviarMensaje(numero, contenido) : String
     */
    public function enviarMensaje(string $numero, string $contenido): string
    {
        if ($this->modoSimulacion()) {
            return 'wamid.simulado.'.Str::uuid();
        }

        $token = (string) config('services.whatsapp.access_token');
        $idTelefono = (string) config('services.whatsapp.phone_number_id');
        $version = (string) config('services.whatsapp.graph_api_version');

        if ($token === '' || $idTelefono === '' || $version === '') {
            throw new RuntimeException('La integración de WhatsApp Cloud API no está configurada.');
        }

        $respuesta = $this->publicarMensaje($token, $version, $idTelefono, $numero, [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $contenido,
            ],
        ]);

        $respuesta->throw();
        $idMensaje = data_get($respuesta->json(), 'messages.0.id');

        if (! is_string($idMensaje) || $idMensaje === '') {
            throw new RuntimeException('WhatsApp Cloud API no devolvió el identificador del mensaje enviado.');
        }

        return $idMensaje;
    }

    /** @param list<string> $parametros */
    public function enviarPlantilla(string $numero, string $nombre, string $idioma, array $parametros): string
    {
        if ($this->modoSimulacion()) {
            return 'wamid.simulado.plantilla.'.Str::uuid();
        }

        $token = (string) config('services.whatsapp.access_token');
        $idTelefono = (string) config('services.whatsapp.phone_number_id');
        $version = (string) config('services.whatsapp.graph_api_version');

        if ($token === '' || $idTelefono === '' || $version === '' || $nombre === '') {
            throw new RuntimeException('La plantilla de WhatsApp no está configurada.');
        }

        $respuesta = $this->publicarMensaje($token, $version, $idTelefono, $numero, [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'type' => 'template',
            'template' => [
                'name' => $nombre,
                'language' => ['code' => $idioma],
                'components' => [[
                    'type' => 'body',
                    'parameters' => array_map(
                        fn (string $valor): array => ['type' => 'text', 'text' => $valor],
                        $parametros,
                    ),
                ]],
            ],
        ]);

        $respuesta->throw();
        $idMensaje = data_get($respuesta->json(), 'messages.0.id');
        if (! is_string($idMensaje) || $idMensaje === '') {
            throw new RuntimeException('WhatsApp Cloud API no devolvió el identificador de la notificación.');
        }

        return $idMensaje;
    }

    /** @param array<string, mixed> $payload */
    private function publicarMensaje(string $token, string $version, string $idTelefono, string $numero, array $payload): Response
    {
        $destino = preg_replace('/\D+/', '', $numero) ?? $numero;
        if (! $this->destinatarioPermitido($destino)) {
            throw new RuntimeException('El destinatario no está autorizado para los envíos de prueba de WhatsApp.');
        }

        app(ServicioConsumoWhatsapp::class)->reservar(
            ($payload['type'] ?? null) === 'template', $destino, data_get($payload, 'template.name'),
        );

        $url = "https://graph.facebook.com/{$version}/{$idTelefono}/messages";
        $enviar = function (string $telefono) use ($token, $url, $payload): Response {
            return $this->solicitudMeta($token)
                ->acceptJson()
                ->asJson()
                ->connectTimeout(5)
                ->timeout(15)
                ->post($url, array_merge($payload, ['to' => $telefono]));
        };

        $respuesta = $enviar($destino);

        // Meta puede devolver el remitente argentino con 549 aunque el número de prueba esté autorizado con 54.
        if ($respuesta->status() === 400 && (int) $respuesta->json('error.code') === 131030 && str_starts_with($destino, '549')) {
            return $enviar('54'.substr($destino, 3));
        }

        return $respuesta;
    }

    public function destinatarioPermitido(string $numero): bool
    {
        if ($this->modoSimulacion()) {
            return true;
        }

        $permitidos = array_filter(array_map('trim', explode(',', (string) config('services.whatsapp.destinatarios_permitidos'))));

        return $permitidos === [] || in_array(
            $this->normalizarDestinoArgentino($numero),
            array_map($this->normalizarDestinoArgentino(...), $permitidos),
            true,
        );
    }

    private function normalizarDestinoArgentino(string $numero): string
    {
        $digitos = preg_replace('/\D+/', '', $numero) ?? '';

        return str_starts_with($digitos, '549') ? '54'.substr($digitos, 3) : $digitos;
    }

    private function solicitudMeta(string $token): PendingRequest
    {
        $solicitud = Http::withToken($token);
        $certificados = trim((string) config('services.whatsapp.ca_bundle'));

        if ($certificados !== '') {
            if (! is_file($certificados)) {
                throw new RuntimeException('El archivo de certificados de WhatsApp no está disponible.');
            }

            $solicitud->withOptions(['verify' => $certificados]);
        }

        return $solicitud;
    }

    /** @param array<string, mixed> $mensaje */
    private function obtenerContenido(array $mensaje, string $tipo): ?string
    {
        return match ($tipo) {
            'text' => data_get($mensaje, 'text.body'),
            'image' => data_get($mensaje, 'image.caption'),
            'document' => data_get($mensaje, 'document.caption') ?? data_get($mensaje, 'document.filename'),
            'button' => data_get($mensaje, 'button.text'),
            'interactive' => data_get($mensaje, 'interactive.button_reply.title')
                ?? data_get($mensaje, 'interactive.list_reply.title'),
            'location' => $this->formatearUbicacion((array) ($mensaje['location'] ?? [])),
            default => null,
        };
    }

    /** @param array<string, mixed> $mensaje */
    private function obtenerIdMedia(array $mensaje, string $tipo): ?string
    {
        if (! in_array($tipo, ['image', 'document', 'audio'], true)) {
            return null;
        }

        $id = data_get($mensaje, "{$tipo}.id");

        return is_string($id) && $id !== '' ? $id : null;
    }

    private function normalizarTipo(string $tipo): string
    {
        return match ($tipo) {
            'text' => 'texto',
            'image' => 'imagen',
            'document' => 'documento',
            'audio' => 'audio',
            'location' => 'ubicacion',
            'contacts' => 'contacto',
            'interactive', 'button' => 'interactivo',
            default => 'desconocido',
        };
    }

    private function modoSimulacion(): bool
    {
        return filter_var(config('services.whatsapp.modo_simulacion', false), FILTER_VALIDATE_BOOL);
    }

    /** @param array<string, mixed> $ubicacion */
    private function formatearUbicacion(array $ubicacion): ?string
    {
        if (! isset($ubicacion['latitude'], $ubicacion['longitude'])) {
            return null;
        }

        return sprintf('%s,%s', $ubicacion['latitude'], $ubicacion['longitude']);
    }
}
