<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Comprueba el comando operativo utilizado al conectar el número real. */
class ConexionWhatsappMetaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.whatsapp.modo_simulacion', false);
        config()->set('services.whatsapp.url_base', 'https://graph.facebook.com');
        config()->set('services.whatsapp.version_api', 'v23.0');
        config()->set('services.whatsapp.id_numero_telefono', '123456789');
        config()->set('services.whatsapp.token_acceso', 'token-real-de-prueba');
    }

    public function test_valida_el_numero_configurado_sin_mostrar_el_token(): void
    {
        Http::fake([
            'https://graph.facebook.com/v23.0/123456789*' => Http::response([
                'id' => '123456789',
                'display_phone_number' => '+54 9 3704 000000',
                'verified_name' => 'Villafañe Wifi',
                'quality_rating' => 'GREEN',
            ]),
        ]);

        $this->artisan('whatsapp:probar-conexion')
            ->expectsOutputToContain('Villafañe Wifi')
            ->expectsOutputToContain('Conexión y credenciales verificadas correctamente.')
            ->assertSuccessful();

        Http::assertSent(fn (Request $solicitud): bool => $solicitud->hasHeader(
            'Authorization',
            'Bearer token-real-de-prueba',
        ));
    }

    public function test_envia_un_mensaje_real_de_prueba_si_se_solicita(): void
    {
        Http::fake(function (Request $solicitud) {
            if (str_ends_with($solicitud->url(), '/messages')) {
                return Http::response(['messages' => [['id' => 'wamid.prueba-real']]]);
            }

            return Http::response([
                'id' => '123456789',
                'display_phone_number' => '+54 9 3704 000000',
                'verified_name' => 'Villafañe Wifi',
                'quality_rating' => 'GREEN',
            ]);
        });

        $this->artisan('whatsapp:probar-conexion', [
            'numero' => '+54 9 3704 111111',
            '--enviar' => true,
        ])->expectsOutputToContain('wamid.prueba-real')->assertSuccessful();

        Http::assertSent(fn (Request $solicitud): bool => str_ends_with($solicitud->url(), '/messages')
            && $solicitud['to'] === '5493704111111');
    }
}
