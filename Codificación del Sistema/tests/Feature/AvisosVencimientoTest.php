<?php

namespace Tests\Feature;

use App\Dominio\ServicioAvisosVencimiento;
use App\Models\AvisoVencimiento;
use App\Models\Cliente;
use App\Models\Conversacion;
use App\Models\Cuota;
use App\Models\Mensaje;
use App\Models\Servicio;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Verifica la detección, auditoría y no duplicación de los avisos RF-18. */
class AvisosVencimientoTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.whatsapp.url_base', 'https://graph.facebook.com');
        config()->set('services.whatsapp.version_api', 'v23.0');
        config()->set('services.whatsapp.id_numero_telefono', '123456789');
        config()->set('services.whatsapp.token_acceso', 'token-de-prueba');
        config()->set('villafane.avisos_vencimiento.dias_anticipacion', 3);
        Http::fake([
            '*' => Http::response(['messages' => [['id' => 'wamid.aviso-1']]]),
        ]);
    }

    public function test_envia_un_aviso_proximo_y_no_lo_duplica(): void
    {
        $this->travelTo('2026-09-17 09:00:00');
        $cuota = $this->crearCuotaNotificable('2026-09-20', 'pendiente');

        $primerResultado = app(ServicioAvisosVencimiento::class)->procesar();

        $this->assertSame(1, $primerResultado['candidatos'], json_encode($primerResultado));
        $this->assertSame(1, $primerResultado['enviados'], json_encode($primerResultado));
        $this->assertDatabaseHas('aviso_vencimiento', [
            'id_cuota' => $cuota->id_cuota,
            'tipo' => 'proximo',
            'estado' => 'enviado',
        ]);
        $aviso = AvisoVencimiento::query()->firstOrFail();
        $this->assertNotNull($aviso->id_conversacion);
        $this->assertNotNull($aviso->id_mensaje);
        $this->assertSame('cerrada', Conversacion::query()->firstOrFail()->estado->value);
        $this->assertStringContainsString(
            'vence el 20/09/2026',
            Mensaje::query()->firstOrFail()->contenido,
        );

        $segundoResultado = app(ServicioAvisosVencimiento::class)->procesar();

        $this->assertSame(0, $segundoResultado['enviados']);
        $this->assertSame(1, $segundoResultado['omitidos']);
        $this->assertSame(1, AvisoVencimiento::count());
        $this->assertSame(1, Conversacion::count());
        Http::assertSentCount(1);
    }

    public function test_actualiza_la_cuota_y_envia_un_aviso_de_vencida(): void
    {
        $this->travelTo('2026-09-17 09:00:00');
        $cuota = $this->crearCuotaNotificable('2026-09-16', 'pendiente');

        $resultado = app(ServicioAvisosVencimiento::class)->procesar();

        $this->assertSame(1, $resultado['enviados']);
        $this->assertSame('vencida', $cuota->refresh()->estado->value);
        $this->assertDatabaseHas('aviso_vencimiento', [
            'id_cuota' => $cuota->id_cuota,
            'tipo' => 'vencido',
            'estado' => 'enviado',
        ]);
        $this->assertStringContainsString(
            'venció el 16/09/2026 y continúa pendiente',
            Mensaje::query()->firstOrFail()->contenido,
        );
    }

    public function test_omite_cuotas_pagadas_y_clientes_sin_whatsapp(): void
    {
        $this->travelTo('2026-09-17 09:00:00');
        $this->crearCuotaNotificable('2026-09-20', 'pagada');
        $clienteSinTelefono = Cliente::factory()->create(['telefono_whatsapp' => null]);
        $servicio = Servicio::factory()->for($clienteSinTelefono, 'cliente')->create(['estado' => 'activo']);
        Cuota::factory()->for($servicio, 'servicio')->create([
            'fecha_vencimiento' => '2026-09-20',
            'estado' => 'pendiente',
        ]);

        $resultado = app(ServicioAvisosVencimiento::class)->procesar();

        $this->assertSame(0, $resultado['candidatos']);
        $this->assertSame(0, AvisoVencimiento::count());
        Http::assertNothingSent();
    }

    public function test_el_comando_ejecuta_el_control_diario(): void
    {
        $this->travelTo('2026-09-17 09:00:00');
        $this->crearCuotaNotificable('2026-09-20', 'pendiente');

        $this->artisan('avisos:enviar-vencimientos')
            ->expectsTable(
                ['Candidatos', 'Enviados', 'Omitidos', 'Fallidos'],
                [['1', '1', '0', '0']],
            )
            ->assertSuccessful();
    }

    private function crearCuotaNotificable(string $vencimiento, string $estado): Cuota
    {
        $cliente = Cliente::factory()->create(['telefono_whatsapp' => '5493704555010']);
        $servicio = Servicio::factory()->for($cliente, 'cliente')->create(['estado' => 'activo']);

        return Cuota::factory()->for($servicio, 'servicio')->create([
            'fecha_vencimiento' => $vencimiento,
            'estado' => $estado,
        ]);
    }
}
