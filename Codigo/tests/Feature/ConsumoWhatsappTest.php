<?php

namespace Tests\Feature;

use App\Dominio\ServicioComprobantes;
use App\Dominio\ServicioConsumoWhatsapp;
use App\Dominio\ServicioUsuarios;
use App\Dominio\WhatsAppService;
use App\Enums\MedioPago;
use App\Exceptions\LimiteConsumoWhatsappException;
use App\Models\Cliente;
use App\Models\Comprobante;
use App\Models\Conversacion;
use App\Models\CuentaReceptora;
use App\Models\Cuota;
use App\Models\Mensaje;
use App\Models\Servicio;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ConsumoWhatsappTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.whatsapp.control_consumo_habilitado' => true,
            'services.whatsapp.modo_simulacion' => false,
            'services.whatsapp.access_token' => 'token-ficticio',
            'services.whatsapp.phone_number_id' => 'telefono-ficticio',
            'services.whatsapp.graph_api_version' => 'v25.0',
            'services.whatsapp.destinatarios_permitidos' => '',
            'services.whatsapp.limite_servicios_mes' => 2,
            'services.whatsapp.plantillas_reales_habilitadas' => false,
            'services.whatsapp.plantillas_destinatarios' => '543704375104',
            'services.whatsapp.limite_plantillas_mes' => 4,
            'services.whatsapp.limite_plantillas_dia' => 2,
            'services.whatsapp.plantilla_vencimiento' => 'demo_cuota_proxima_v1',
        ]);
        Http::preventStrayRequests();
    }

    private function simularRespuestaMeta(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.control']]])]);
    }

    private function enviarPlantilla(): string
    {
        return app(WhatsAppService::class)->enviarPlantilla('5493704375104', 'demo_cuota_proxima_v1', 'es_AR', ['Cliente', '23/10/2026', '$100,00']);
    }

    public function test_el_limite_de_respuestas_bloquea_antes_de_contactar_a_meta(): void
    {
        $this->simularRespuestaMeta();
        $wa = app(WhatsAppService::class);
        $wa->enviarMensaje('5493704375104', 'Uno');
        $wa->enviarMensaje('5493704375104', 'Dos');
        try {
            $wa->enviarMensaje('5493704375104', 'Tres');
            $this->fail('Se permitió exceder el cupo.');
        } catch (LimiteConsumoWhatsappException) {
            Http::assertSentCount(2);
            $this->assertSame(2, app(ServicioConsumoWhatsapp::class)->resumir()['servicios']);
        }
    }

    public function test_plantillas_bloqueadas_no_envian_ni_reservan_cupo(): void
    {
        try {
            $this->enviarPlantilla();
            $this->fail('Se envió una plantilla deshabilitada.');
        } catch (LimiteConsumoWhatsappException) {
            Http::assertNothingSent();
            $this->assertDatabaseCount('consumo_whatsapp_mes', 0);
        }
    }

    public function test_plantillas_solo_aceptan_destinatarios_y_nombres_configurados(): void
    {
        config(['services.whatsapp.plantillas_reales_habilitadas' => true]);
        foreach ([['5493704000000', 'demo_cuota_proxima_v1'], ['5493704375104', 'campania_marketing']] as [$telefono, $nombre]) {
            try {
                app(WhatsAppService::class)->enviarPlantilla($telefono, $nombre, 'es_AR', []);
                $this->fail('Se permitió una plantilla fuera de la demostración.');
            } catch (LimiteConsumoWhatsappException) {
                Http::assertNothingSent();
            }
        }
        $this->assertDatabaseCount('consumo_whatsapp_mes', 0);
    }

    public function test_topes_diario_y_mensual_persisten_y_el_mes_nuevo_tiene_otro_cupo(): void
    {
        $this->travelTo(now()->utc()->setDate(2026, 10, 20)->setTime(12, 0));
        config(['services.whatsapp.plantillas_reales_habilitadas' => true]);
        $this->simularRespuestaMeta();
        for ($dia = 0; $dia < 2; $dia++) {
            $this->enviarPlantilla();
            $this->enviarPlantilla();
            try {
                $this->enviarPlantilla();
                $this->fail('Se excedieron dos plantillas diarias.');
            } catch (LimiteConsumoWhatsappException) {
                $this->assertSame(2, app(ServicioConsumoWhatsapp::class)->resumir()['plantillas_hoy']);
            }
            $this->travel(1)->days();
        }
        try {
            $this->enviarPlantilla();
            $this->fail('Se excedieron cuatro plantillas mensuales.');
        } catch (LimiteConsumoWhatsappException) {
            Http::assertSentCount(4);
        }
        $this->travelTo(now()->utc()->setDate(2026, 11, 1)->setTime(12, 0));
        $this->enviarPlantilla();
        $this->assertSame(1, app(ServicioConsumoWhatsapp::class)->resumir()['plantillas']);
        $this->assertDatabaseCount('consumo_whatsapp_mes', 2);
        Http::assertSentCount(5);
    }

    public function test_un_error_de_red_conserva_la_reserva_por_si_meta_recibio_el_mensaje(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::failedConnection()]);
        try {
            app(WhatsAppService::class)->enviarMensaje('5493704375104', 'Prueba');
            $this->fail('Se esperaba un error de red.');
        } catch (ConnectionException) {
            $this->assertSame(1, app(ServicioConsumoWhatsapp::class)->resumir()['servicios']);
        }
    }

    public function test_simulacion_no_usa_meta_ni_consume_los_cupos(): void
    {
        config(['services.whatsapp.modo_simulacion' => true, 'services.whatsapp.limite_servicios_mes' => 0]);
        $this->assertStringStartsWith('wamid.simulado.', app(WhatsAppService::class)->enviarMensaje('5493704375104', 'Prueba'));
        $this->assertStringStartsWith('wamid.simulado.plantilla.', $this->enviarPlantilla());
        Http::assertNothingSent();
        $this->assertDatabaseCount('consumo_whatsapp_mes', 0);
    }

    public function test_el_historial_real_previo_del_mes_se_incluye_sin_contar_mensajes_simulados(): void
    {
        $conversacion = Conversacion::factory()->create();
        Mensaje::factory()->create(['id_conversacion' => $conversacion->id_conversacion, 'tipo_emisor' => 'bot',
            'id_mensaje_externo' => 'wamid.anterior', 'fecha_hora' => now()->utc()]);
        Mensaje::factory()->create(['id_conversacion' => $conversacion->id_conversacion, 'tipo_emisor' => 'bot',
            'id_mensaje_externo' => 'wamid.simulado.prueba', 'fecha_hora' => now()->utc()]);
        $this->simularRespuestaMeta();
        app(WhatsAppService::class)->enviarMensaje('5493704375104', 'Prueba');
        $this->assertSame(2, app(ServicioConsumoWhatsapp::class)->resumir()['servicios']);
        $this->assertDatabaseCount('consumo_whatsapp_mes', 1);
    }

    public function test_el_panel_muestra_los_limites_sin_enviar_mensajes(): void
    {
        $admin = app(ServicioUsuarios::class)->crear(['nombre_usuario' => 'admin-consumo', 'contrasena' => 'clave-consumo',
            'tipo' => 'administrador', 'nivel_acceso' => 'total']);
        $this->actingAs($admin)->get(route('conversaciones.index'))->assertOk()
            ->assertSee('Consumo de WhatsApp')->assertSee('Las plantillas reales están bloqueadas');
        Http::assertNothingSent();
        $this->assertDatabaseCount('consumo_whatsapp_mes', 0);
    }

    public function test_bloquear_el_aviso_no_revierte_la_aprobacion_ni_el_pago(): void
    {
        config(['services.comprobantes.notificar_resultado' => true, 'services.whatsapp.limite_servicios_mes' => 0]);
        $cliente = Cliente::factory()->create(['telefono_whatsapp' => '5493704375104']);
        $conversacion = Conversacion::factory()->create(['id_cliente' => $cliente->id_cliente, 'numero_whatsapp' => $cliente->telefono_whatsapp]);
        Mensaje::factory()->create(['id_conversacion' => $conversacion->id_conversacion, 'fecha_hora' => now()->subMinutes(5)]);
        $servicio = Servicio::factory()->create(['id_cliente' => $cliente->id_cliente]);
        $cuota = Cuota::factory()->create(['id_servicio' => $servicio->id_servicio, 'monto' => '100.00', 'id_pago' => null]);
        $comprobante = Comprobante::create(['id_cliente' => $cliente->id_cliente,
            'numero_operacion' => 'CONSUMO-123456', 'monto_ocr' => '100.00', 'fecha_ocr' => today()->toDateString(),
            'hash_archivo' => hash('sha256', 'comprobante-control-consumo'), 'estado_validacion' => 'pendiente']);
        $admin = app(ServicioUsuarios::class)->crear(['nombre_usuario' => 'admin-cupo', 'contrasena' => 'clave-consumo',
            'tipo' => 'administrador', 'nivel_acceso' => 'total']);
        $pago = app(ServicioComprobantes::class)->conciliarYAprobar($comprobante,
            CuentaReceptora::factory()->create(), MedioPago::MercadoPago, usuario: $admin);

        $this->assertSame('aprobado', $comprobante->fresh()->estado_validacion->value);
        $this->assertSame($pago->id_pago, $cuota->fresh()->id_pago);
        $this->assertDatabaseCount('pago', 1);
        $this->assertDatabaseHas('mensaje', ['clave_idempotencia' => 'comprobante:aprobado:'.$comprobante->id_comprobante, 'estado_envio' => 'fallido']);
        Http::assertNothingSent();
    }
}
