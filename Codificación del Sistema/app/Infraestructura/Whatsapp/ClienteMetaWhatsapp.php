<?php

namespace App\Infraestructura\Whatsapp;

use App\Contratos\PuertaEnlaceWhatsapp;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Implementa el envío de mensajes mediante WhatsApp Cloud API de Meta.
 */
class ClienteMetaWhatsapp implements PuertaEnlaceWhatsapp
{
    /**
     * Consulta el número configurado para validar token, permisos y activo.
     *
     * @return array{id: string, display_phone_number: string, verified_name: string, quality_rating: string|null}
     */
    public function consultarNumeroConfigurado(): array
    {
        $configuracion = $this->configuracion();
        $respuesta = $this->solicitud($configuracion['token'])->get(
            "{$configuracion['url']}/{$configuracion['version']}/{$configuracion['id_numero']}",
            ['fields' => 'id,display_phone_number,verified_name,quality_rating'],
        );
        $respuesta->throw();

        return [
            'id' => (string) $respuesta->json('id'),
            'display_phone_number' => (string) $respuesta->json('display_phone_number'),
            'verified_name' => (string) $respuesta->json('verified_name'),
            'quality_rating' => $respuesta->json('quality_rating'),
        ];
    }

    /**
     * Envía texto plano al número indicado y devuelve el ID externo del mensaje.
     */
    public function enviarTexto(string $numeroDestino, string $contenido): string
    {
        $numeroNormalizado = preg_replace('/\D+/', '', $numeroDestino) ?? '';
        if ($numeroNormalizado === '' || blank($contenido)) {
            throw ValidationException::withMessages([
                'mensaje' => 'El destino y el contenido del mensaje son obligatorios.',
            ]);
        }

        $configuracion = $this->configuracion();

        $respuesta = $this->solicitud($configuracion['token'])->post(
            "{$configuracion['url']}/{$configuracion['version']}/{$configuracion['id_numero']}/messages",
            [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $numeroNormalizado,
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $contenido,
                ],
            ],
        );

        $respuesta->throw();
        $identificador = $respuesta->json('messages.0.id');

        if (! is_string($identificador) || $identificador === '') {
            throw new RuntimeException('Meta no devolvió el identificador del mensaje enviado.');
        }

        return $identificador;
    }

    /** @return array{token: string, id_numero: string, version: string, url: string} */
    private function configuracion(): array
    {
        $token = (string) config('services.whatsapp.token_acceso');
        $idNumero = (string) config('services.whatsapp.id_numero_telefono');
        $version = (string) config('services.whatsapp.version_api');
        $url = rtrim((string) config('services.whatsapp.url_base'), '/');

        if ($token === '' || $idNumero === '' || $version === '' || $url === '') {
            throw ValidationException::withMessages([
                'whatsapp' => 'Falta completar la configuración para conectar con WhatsApp.',
            ]);
        }

        return [
            'token' => $token,
            'id_numero' => $idNumero,
            'version' => $version,
            'url' => $url,
        ];
    }

    /**
     * Construye el cliente HTTP autenticado utilizado por WhatsApp Cloud API.
     */
    private function solicitud(string $token): PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->withToken($token)
            ->timeout(15)
            ->retry(2, 200);
    }
}
