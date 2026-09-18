<?php

namespace Tests\Feature;

use App\Contratos\ClasificadorIntencion;
use App\Dominio\ServicioReconocimientoIntencionWhatsapp;
use App\Enums\IntencionWhatsapp;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ClasificacionIntencionTest extends TestCase
{
    public function test_usa_clasificador_local_si_no_hay_clave_api(): void
    {
        config()->set('services.gemini.clave_api', null);

        $resultado = app(ClasificadorIntencion::class)->clasificar('Quiero consultar mi deuda');

        $this->assertSame(IntencionWhatsapp::ConsultarEstadoCuenta, $resultado->intencion);
        $this->assertSame('local', $resultado->origen);
        Http::assertNothingSent();
    }

    public function test_gemini_clasifica_una_frase_en_formato_estructurado(): void
    {
        config()->set('services.gemini.clave_api', 'clave-de-prueba');
        config()->set('services.gemini.modelo', 'gemini-3.5-flash-lite');
        Http::preventStrayRequests();
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => json_encode([
                                'intencion' => 'informar_pago',
                                'confianza' => 0.94,
                            ], JSON_THROW_ON_ERROR),
                        ]],
                    ],
                ]],
            ]),
        ]);

        $resultado = app(ClasificadorIntencion::class)
            ->clasificar('Ya hice una transferencia y quiero mandar el comprobante');

        $this->assertSame(IntencionWhatsapp::InformarPago, $resultado->intencion);
        $this->assertSame(0.94, $resultado->confianza);
        $this->assertSame('gemini', $resultado->origen);
        Http::assertSent(function (Request $solicitud): bool {
            return str_contains($solicitud->url(), 'gemini-3.5-flash-lite:generateContent')
                && $solicitud->hasHeader('x-goog-api-key', 'clave-de-prueba')
                && data_get($solicitud->data(), 'generationConfig.responseFormat.text.mimeType') === 'application/json';
        });
    }

    public function test_rechaza_una_clasificacion_con_confianza_insuficiente(): void
    {
        config()->set('services.gemini.clave_api', 'clave-de-prueba');
        config()->set('services.gemini.umbral_confianza', 0.65);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => '{"intencion":"registrar_reclamo","confianza":0.40}',
                        ]],
                    ],
                ]],
            ]),
        ]);

        $resultado = app(ServicioReconocimientoIntencionWhatsapp::class)
            ->reconocer('Tengo una situación difícil de explicar');

        $this->assertSame(IntencionWhatsapp::NoReconocida, $resultado->intencion);
        $this->assertSame(0.40, $resultado->confianza);
    }
}
