<?php

namespace Tests\Feature;

use App\Infraestructura\Whatsapp\DescargadorArchivosMetaWhatsapp;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/** Verifica la descarga privada de comprobantes desde WhatsApp Cloud API. */
class DescargaArchivosWhatsappTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.whatsapp.url_base', 'https://graph.facebook.com');
        config()->set('services.whatsapp.version_api', 'v23.0');
        config()->set('services.whatsapp.token_acceso', 'token-de-prueba');
        config()->set('services.whatsapp.tamano_maximo_archivo_kb', 1024);
        Storage::fake('local');
    }

    public function test_descarga_y_guarda_una_imagen_recibida_desde_meta(): void
    {
        Http::fake([
            'https://graph.facebook.com/v23.0/media-123' => Http::response([
                'url' => 'https://lookaside.fbsbx.com/archivo-123',
                'mime_type' => 'image/jpeg',
                'file_size' => 15,
            ]),
            'https://lookaside.fbsbx.com/archivo-123' => Http::response('imagen-binaria'),
        ]);

        $ruta = app(DescargadorArchivosMetaWhatsapp::class)->descargar('media-123');

        $this->assertStringStartsWith('whatsapp/comprobantes/', $ruta);
        $this->assertStringEndsWith('.jpg', $ruta);
        Storage::disk('local')->assertExists($ruta);
        $this->assertSame('imagen-binaria', Storage::disk('local')->get($ruta));
        Http::assertSentCount(2);
        Http::assertSent(fn ($solicitud): bool => $solicitud->hasHeader('Authorization', 'Bearer token-de-prueba'));
    }

    public function test_rechaza_un_archivo_que_supera_el_tamano_permitido(): void
    {
        config()->set('services.whatsapp.tamano_maximo_archivo_kb', 1);
        Http::fake([
            'https://graph.facebook.com/v23.0/media-grande' => Http::response([
                'url' => 'https://lookaside.fbsbx.com/archivo-grande',
                'mime_type' => 'application/pdf',
                'file_size' => 2048,
            ]),
        ]);

        $this->expectException(ValidationException::class);

        app(DescargadorArchivosMetaWhatsapp::class)->descargar('media-grande');
    }
}
