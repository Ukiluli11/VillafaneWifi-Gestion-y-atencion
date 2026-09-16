<?php

namespace Tests\Feature;

use App\Dominio\ServicioEnvioWhatsapp;
use App\Enums\EstadoEnvioMensaje;
use App\Models\Conversacion;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Verifica el envío sin contactar realmente a los servidores de Meta.
 */
class EnvioWhatsappTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.whatsapp.url_base', 'https://graph.facebook.com');
        config()->set('services.whatsapp.version_api', 'v23.0');
        config()->set('services.whatsapp.id_numero_telefono', '123456789');
        config()->set('services.whatsapp.token_acceso', 'token-de-prueba');
    }

    public function test_envia_y_registra_un_mensaje_de_texto_del_bot(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.respuesta-1']],
            ]),
        ]);
        $conversacion = Conversacion::factory()->create([
            'numero_whatsapp' => '+54 9 3704 123456',
        ]);

        $mensaje = app(ServicioEnvioWhatsapp::class)->enviarTextoDelBot(
            $conversacion,
            'Hola, soy el asistente virtual de Villafañe Wifi.',
        );

        $this->assertSame(EstadoEnvioMensaje::Enviado, $mensaje->estado_envio);
        $this->assertSame('wamid.respuesta-1', $mensaje->id_mensaje_externo);
        $this->assertDatabaseHas('mensaje', [
            'id_mensaje' => $mensaje->id_mensaje,
            'tipo_emisor' => 'bot',
            'tipo' => 'texto',
            'estado_envio' => 'enviado',
        ]);
        Http::assertSent(function (Request $solicitud): bool {
            return $solicitud->url() === 'https://graph.facebook.com/v23.0/123456789/messages'
                && $solicitud->hasHeader('Authorization', 'Bearer token-de-prueba')
                && $solicitud['to'] === '5493704123456'
                && $solicitud['text']['body'] === 'Hola, soy el asistente virtual de Villafañe Wifi.';
        });
    }

    public function test_conserva_como_fallido_un_mensaje_rechazado_por_meta(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'rechazado']], 400)]);
        $conversacion = Conversacion::factory()->create();

        try {
            app(ServicioEnvioWhatsapp::class)->enviarTextoDelBot($conversacion, 'Mensaje de prueba');
            $this->fail('Se esperaba una excepción HTTP.');
        } catch (RequestException) {
            $this->assertDatabaseHas('mensaje', [
                'id_conversacion' => $conversacion->id_conversacion,
                'contenido' => "Hola. Te atiende el servicio virtual de Villafañe Wifi.\n\nMensaje de prueba",
                'estado_envio' => 'fallido',
            ]);
        }
    }

    public function test_informa_si_faltan_las_credenciales_de_meta(): void
    {
        config()->set('services.whatsapp.token_acceso', null);
        $conversacion = Conversacion::factory()->create();

        $this->expectException(ValidationException::class);

        app(ServicioEnvioWhatsapp::class)->enviarTextoDelBot($conversacion, 'Mensaje de prueba');
    }
}
