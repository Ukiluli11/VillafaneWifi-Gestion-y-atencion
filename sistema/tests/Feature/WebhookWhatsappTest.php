<?php

namespace Tests\Feature;

use App\Enums\EstadoEnvioMensaje;
use App\Models\Cliente;
use App\Models\Conversacion;
use App\Models\Mensaje;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Verifica el contrato HTTP del webhook sin depender de los servidores de Meta.
 */
class WebhookWhatsappTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const SECRETO = 'secreto-de-prueba';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.whatsapp.token_verificacion', 'token-de-prueba');
        config()->set('services.whatsapp.secreto_aplicacion', self::SECRETO);
        config()->set('services.whatsapp.url_base', 'https://graph.facebook.com');
        config()->set('services.whatsapp.version_api', 'v23.0');
        config()->set('services.whatsapp.id_numero_telefono', '123456789');
        config()->set('services.whatsapp.token_acceso', 'token-de-prueba');
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.respuesta-bot']],
            ]),
        ]);
    }

    public function test_meta_puede_verificar_el_webhook_con_el_token_correcto(): void
    {
        $respuesta = $this->get('/api/webhooks/whatsapp?hub_mode=subscribe&hub_verify_token=token-de-prueba&hub_challenge=123456');

        $respuesta->assertOk()->assertSeeText('123456');
    }

    public function test_rechaza_la_verificacion_con_un_token_incorrecto(): void
    {
        $this->get('/api/webhooks/whatsapp?hub_mode=subscribe&hub_verify_token=otro&hub_challenge=123456')
            ->assertForbidden();
    }

    public function test_recibe_identifica_y_guarda_un_mensaje_de_texto(): void
    {
        Cliente::factory()->create([
            'nombre_razon_social' => 'María González',
            'telefono_whatsapp' => '5493704123456',
        ]);
        $evento = $this->eventoConMensaje();

        $respuesta = $this->enviarEventoFirmado($evento);

        $respuesta->assertOk()
            ->assertJsonPath('recibido', true)
            ->assertJsonPath('mensajes_procesados', 1)
            ->assertJsonPath('clientes_no_identificados', 0)
            ->assertJsonPath('respuestas_enviadas', 1)
            ->assertJsonPath('respuestas_fallidas', 0);
        $this->assertSame(1, Conversacion::count());
        $this->assertDatabaseHas('mensaje', [
            'id_mensaje_externo' => 'wamid.mensaje-1',
            'contenido' => 'Quiero consultar mi deuda',
            'tipo_emisor' => 'cliente',
            'estado_envio' => 'recibido',
            'fecha_hora' => '2026-09-10 15:00:00',
        ]);
        $this->assertDatabaseHas('mensaje', [
            'tipo_emisor' => 'bot',
            'estado_envio' => 'enviado',
            'id_mensaje_externo' => 'wamid.respuesta-bot',
        ]);

        $mensajes = Mensaje::query()->orderBy('id_mensaje')->get();
        $this->assertCount(2, $mensajes);
        $this->assertSame($mensajes[0]->id_conversacion, $mensajes[1]->id_conversacion);
        $this->assertSame('cliente', $mensajes[0]->tipo_emisor->value);
        $this->assertSame('bot', $mensajes[1]->tipo_emisor->value);
        $this->assertStringContainsString('Hola, María González.', $mensajes[1]->contenido);
        $this->assertStringContainsString('1. Consultar estado de cuenta', $mensajes[1]->contenido);
        $this->assertStringContainsString('2. Informar un pago', $mensajes[1]->contenido);
        $this->assertStringContainsString('3. Registrar un reclamo', $mensajes[1]->contenido);
        $this->assertStringContainsString('4. Solicitar atención humana', $mensajes[1]->contenido);

        Http::assertSent(fn (Request $solicitud): bool => $solicitud['to'] === '5493704123456'
            && str_contains($solicitud['text']['body'], '1. Consultar estado de cuenta')
            && str_contains($solicitud['text']['body'], '4. Solicitar atención humana'));
    }

    public function test_un_evento_repetido_no_duplica_el_mensaje(): void
    {
        Cliente::factory()->create(['telefono_whatsapp' => '5493704123456']);
        $evento = $this->eventoConMensaje();

        $this->enviarEventoFirmado($evento)->assertOk();
        $this->enviarEventoFirmado($evento)->assertOk();

        $this->assertSame(1, Conversacion::count());
        $this->assertSame(2, Mensaje::count());
        Http::assertSentCount(1);
    }

    public function test_rechaza_un_evento_con_firma_invalida(): void
    {
        $this->call(
            'POST',
            '/api/webhooks/whatsapp',
            server: ['CONTENT_TYPE' => 'application/json', 'HTTP_X_HUB_SIGNATURE_256' => 'sha256=invalida'],
            content: json_encode($this->eventoConMensaje(), JSON_THROW_ON_ERROR),
        )->assertUnauthorized();

        $this->assertSame(0, Mensaje::count());
    }

    public function test_informa_un_cliente_no_identificado_sin_crear_datos_falsos(): void
    {
        $this->enviarEventoFirmado($this->eventoConMensaje())
            ->assertOk()
            ->assertJsonPath('clientes_no_identificados', 1)
            ->assertJsonPath('mensajes_procesados', 0)
            ->assertJsonPath('respuestas_enviadas', 1);

        $this->assertSame(0, Conversacion::count());
        $this->assertSame(0, Mensaje::count());
        Http::assertSent(fn (Request $solicitud): bool => $solicitud['to'] === '5493704123456'
            && str_contains($solicitud['text']['body'], 'No pudimos identificarte como cliente'));
    }

    public function test_actualiza_el_estado_informado_por_meta(): void
    {
        $mensaje = Mensaje::factory()->create([
            'id_mensaje_externo' => 'wamid.salida-1',
            'tipo_emisor' => 'bot',
            'estado_envio' => EstadoEnvioMensaje::Enviado,
        ]);
        $evento = [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'changes' => [[
                    'field' => 'messages',
                    'value' => ['statuses' => [['id' => 'wamid.salida-1', 'status' => 'read']]],
                ]],
            ]],
        ];

        $this->enviarEventoFirmado($evento)
            ->assertOk()
            ->assertJsonPath('estados_actualizados', 1);

        $this->assertSame(EstadoEnvioMensaje::Leido, $mensaje->refresh()->estado_envio);
    }

    /**
     * Construye una notificación equivalente a la publicada por Meta.
     *
     * @return array<string, mixed>
     */
    private function eventoConMensaje(): array
    {
        return [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'changes' => [[
                    'field' => 'messages',
                    'value' => [
                        'messaging_product' => 'whatsapp',
                        'messages' => [[
                            'from' => '5493704123456',
                            'id' => 'wamid.mensaje-1',
                            'timestamp' => '1789052400',
                            'type' => 'text',
                            'text' => ['body' => 'Quiero consultar mi deuda'],
                        ]],
                    ],
                ]],
            ]],
        ];
    }

    /**
     * Firma exactamente el JSON enviado para reproducir la validación real.
     *
     * @param  array<string, mixed>  $evento
     */
    private function enviarEventoFirmado(array $evento): TestResponse
    {
        $contenido = json_encode($evento, JSON_THROW_ON_ERROR);
        $firma = 'sha256='.hash_hmac('sha256', $contenido, self::SECRETO);

        return $this->call(
            'POST',
            '/api/webhooks/whatsapp',
            server: ['CONTENT_TYPE' => 'application/json', 'HTTP_X_HUB_SIGNATURE_256' => $firma],
            content: $contenido,
        );
    }
}
