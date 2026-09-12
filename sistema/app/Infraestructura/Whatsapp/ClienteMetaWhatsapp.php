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

        $token = (string) config('services.whatsapp.token_acceso');
        $idNumeroTelefono = (string) config('services.whatsapp.id_numero_telefono');
        $version = (string) config('services.whatsapp.version_api');
        $urlBase = rtrim((string) config('services.whatsapp.url_base'), '/');

        if ($token === '' || $idNumeroTelefono === '' || $version === '' || $urlBase === '') {
            throw ValidationException::withMessages([
                'whatsapp' => 'Falta completar la configuración para enviar mensajes por WhatsApp.',
            ]);
        }

        $respuesta = $this->solicitud($token)->post(
            "{$urlBase}/{$version}/{$idNumeroTelefono}/messages",
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
