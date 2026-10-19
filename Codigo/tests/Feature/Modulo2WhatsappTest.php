<?php

namespace Tests\Feature;

use App\Dominio\ServicioAtencionHumana;
use App\Dominio\ServicioAtencionWhatsapp;
use App\Dominio\ServicioConversaciones;
use App\Dominio\ServicioIdentificacion;
use App\Dominio\ServicioNotificacionesVencimiento;
use App\Dominio\ServicioUsuarios;
use App\Dominio\WhatsAppService;
use App\Enums\EstadoConversacion;
use App\Enums\EstadoEnvioMensaje;
use App\Enums\ModoAtencion;
use App\Enums\TipoEmisorMensaje;
use App\Enums\TipoMensaje;
use App\Models\Cliente;
use App\Models\Conversacion;
use App\Models\Cuota;
use App\Models\Mensaje;
use App\Models\Servicio;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Modulo2WhatsappTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.whatsapp.modo_simulacion' => true,
            'services.whatsapp.validar_firma' => false,
            'services.openai.api_key' => null,
            'services.openai.model' => null,
        ]);
    }

    public function test_reintenta_el_formato_argentino_autorizado_cuando_meta_rechaza_el_wa_id(): void
    {
        config([
            'services.whatsapp.modo_simulacion' => false,
            'services.whatsapp.access_token' => 'token-de-prueba',
            'services.whatsapp.phone_number_id' => 'telefono-de-prueba',
            'services.whatsapp.graph_api_version' => 'v25.0',
        ]);

        Http::fakeSequence()
            ->push(['error' => ['code' => 131030]], 400)
            ->push(['messages' => [['id' => 'wamid.respuesta']]], 200);

        $id = app(WhatsAppService::class)->enviarMensaje('5493704061032', 'Hola');

        $this->assertSame('wamid.respuesta', $id);
        Http::assertSentCount(2);
        $this->assertSame(
            ['5493704061032', '543704061032'],
            Http::recorded()->map(fn (array $registro): string => $registro[0]['to'])->all(),
        );
    }

    public function test_los_envios_reales_solo_aceptan_destinatarios_de_la_prueba(): void
    {
        config([
            'services.whatsapp.modo_simulacion' => false,
            'services.whatsapp.access_token' => 'token-de-prueba',
            'services.whatsapp.phone_number_id' => 'telefono-de-prueba',
            'services.whatsapp.graph_api_version' => 'v25.0',
            'services.whatsapp.destinatarios_permitidos' => '543704061032',
        ]);
        Http::fake(['*' => Http::response(['messages' => [['id' => 'wamid.prueba']]])]);

        $this->assertSame('wamid.prueba', app(WhatsAppService::class)->enviarMensaje('5493704061032', 'Prueba'));
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('no está autorizado');
        app(WhatsAppService::class)->enviarMensaje('5493704000000', 'No debe enviarse');
    }

    public function test_recordatorio_real_envia_la_plantilla_con_tres_datos_de_prueba(): void
    {
        config([
            'services.whatsapp.modo_simulacion' => false,
            'services.whatsapp.access_token' => 'token-de-prueba',
            'services.whatsapp.phone_number_id' => 'telefono-de-prueba',
            'services.whatsapp.graph_api_version' => 'v25.0',
            'services.whatsapp.destinatarios_permitidos' => '543704061032',
        ]);
        Http::fake(['*' => Http::response(['messages' => [['id' => 'wamid.recordatorio']]])]);

        $id = app(WhatsAppService::class)->enviarPlantilla(
            '5493704061032',
            'recordatorio_vencimiento',
            'es_AR',
            ['Cliente de prueba', '19/10/2026', '$18.000,00'],
        );

        $this->assertSame('wamid.recordatorio', $id);
        Http::assertSent(fn ($solicitud): bool => $solicitud['type'] === 'template'
            && $solicitud['template']['name'] === 'recordatorio_vencimiento'
            && $solicitud['template']['language']['code'] === 'es_AR'
            && $solicitud['template']['components'][0]['parameters'] === [
                ['type' => 'text', 'text' => 'Cliente de prueba'],
                ['type' => 'text', 'text' => '19/10/2026'],
                ['type' => 'text', 'text' => '$18.000,00'],
            ]);
    }

    public function test_webhook_registra_mensajes_responde_menu_y_cierra_consulta_resuelta(): void
    {
        $cliente = Cliente::factory()->create(['telefono_whatsapp' => '5493704000000']);
        $payload = [
            'entry' => [[
                'changes' => [[
                    'value' => ['messages' => [[
                        'id' => 'wamid.entrada.1',
                        'from' => '5493704000000',
                        'timestamp' => (string) now()->timestamp,
                        'type' => 'text',
                        'text' => ['body' => '1'],
                    ]]],
                ]],
            ]],
        ];

        $this->postJson('/webhooks/whatsapp', $payload)->assertOk()->assertJson(['recibido' => true]);

        $conversacion = Conversacion::whereBelongsTo($cliente)->firstOrFail();
        $this->assertSame(EstadoConversacion::Cerrada, $conversacion->estado);
        $this->assertDatabaseCount('mensaje', 2);
        $this->assertDatabaseHas('mensaje', [
            'id_conversacion' => $conversacion->id_conversacion,
            'tipo_emisor' => TipoEmisorMensaje::Bot->value,
            'estado_envio' => EstadoEnvioMensaje::Enviado->value,
        ]);
    }

    public function test_dos_intentos_desconocidos_escalan_a_atencion_humana(): void
    {
        $cliente = Cliente::factory()->create();
        $conversacion = Conversacion::factory()->create(['id_cliente' => $cliente->id_cliente]);
        $atencion = app(ServicioAtencionWhatsapp::class);

        foreach (['mensaje incomprensible', 'otra cosa extraña'] as $indice => $contenido) {
            $mensaje = Mensaje::factory()->create([
                'id_conversacion' => $conversacion->id_conversacion,
                'id_mensaje_externo' => 'desconocido-'.$indice,
                'contenido' => $contenido,
            ]);
            $respuesta = $atencion->procesarMensaje($conversacion->fresh(), $mensaje, $cliente);
        }

        $this->assertStringContainsString('operador', $respuesta);
        $this->assertSame(EstadoConversacion::Escalada, $conversacion->fresh()->estado);
    }

    public function test_identificacion_por_documento_vincula_el_numero_de_whatsapp(): void
    {
        $cliente = Cliente::factory()->create(['telefono_whatsapp' => null, 'numero_documento' => '30111222']);
        $conversacion = Conversacion::factory()->create(['numero_whatsapp' => '5493704111222']);

        $identificado = app(ServicioIdentificacion::class)->identificarCliente(
            $conversacion,
            '5493704111222',
            'Mi DNI es 30.111.222',
        );

        $this->assertTrue($cliente->is($identificado));
        $this->assertSame('5493704111222', $cliente->fresh()->telefono_whatsapp);
        $this->assertSame($cliente->id_cliente, $conversacion->fresh()->id_cliente);
    }

    public function test_estado_de_entrega_no_retrocede(): void
    {
        $conversacion = Conversacion::factory()->create();
        $mensaje = app(ServicioConversaciones::class)->registrarMensajeSaliente(
            $conversacion,
            'Respuesta',
            'wamid.salida.1',
            EstadoEnvioMensaje::Enviado,
        );

        $servicio = app(ServicioConversaciones::class);
        $servicio->actualizarEstadoExterno('wamid.salida.1', EstadoEnvioMensaje::Leido);
        $servicio->actualizarEstadoExterno('wamid.salida.1', EstadoEnvioMensaje::Entregado);

        $this->assertSame(EstadoEnvioMensaje::Leido, $mensaje->fresh()->estado_envio);
    }

    public function test_un_operador_toma_responde_y_cierra_una_conversacion_escalada(): void
    {
        $operador = app(ServicioUsuarios::class)->crear([
            'nombre_usuario' => 'operador-prueba',
            'contrasena' => 'clave-segura',
            'tipo' => 'empleado',
            'area' => 'soporte',
        ]);
        $conversacion = Conversacion::factory()->create([
            'estado' => EstadoConversacion::Escalada,
            'modo_atencion' => ModoAtencion::Humano,
        ]);

        $atencion = app(ServicioAtencionHumana::class);
        $atencion->tomar($conversacion, $operador);
        $atencion->responder($conversacion, $operador, 'Ya revisamos tu consulta.');
        $atencion->cerrar($conversacion, $operador);

        $this->assertSame(EstadoConversacion::Cerrada, $conversacion->fresh()->estado);
        $this->assertSame($operador->id_usuario, $conversacion->fresh()->id_usuario_atencion);
        $this->assertSame(2, $conversacion->mensajes()->count());
        $this->assertDatabaseHas('mensaje', [
            'id_conversacion' => $conversacion->id_conversacion,
            'contenido' => 'Ya revisamos tu consulta.',
            'tipo_emisor' => TipoEmisorMensaje::UsuarioInterno->value,
            'estado_envio' => EstadoEnvioMensaje::Enviado->value,
        ]);
    }

    public function test_recordatorios_de_vencimiento_no_se_duplican(): void
    {
        $cliente = Cliente::factory()->create(['telefono_whatsapp' => '5493704000000']);
        $servicio = Servicio::factory()->create(['id_cliente' => $cliente->id_cliente]);
        Cuota::factory()->create([
            'id_servicio' => $servicio->id_servicio,
            'fecha_vencimiento' => today()->addDay()->toDateString(),
        ]);

        $notificaciones = app(ServicioNotificacionesVencimiento::class);
        $this->assertSame(['enviadas' => 1, 'omitidas' => 0, 'fallidas' => 0], $notificaciones->enviar());
        $this->assertSame(['enviadas' => 0, 'omitidas' => 1, 'fallidas' => 0], $notificaciones->enviar());
        $this->assertDatabaseCount('mensaje', 1);
    }

    public function test_comando_envia_solo_la_cuota_elegida_y_omite_destinatarios_no_autorizados(): void
    {
        config([
            'services.whatsapp.modo_simulacion' => false,
            'services.whatsapp.access_token' => 'token-de-prueba',
            'services.whatsapp.phone_number_id' => 'telefono-de-prueba',
            'services.whatsapp.destinatarios_permitidos' => '543704000000',
        ]);
        Http::fake(['*' => Http::response(['messages' => [['id' => 'wamid.cuota-seleccionada']]])]);

        $fechaVencimiento = CarbonImmutable::today('America/Argentina/Buenos_Aires')->addDay()->toDateString();
        $clientePermitido = Cliente::factory()->create(['telefono_whatsapp' => '5493704000000']);
        $clienteNoPermitido = Cliente::factory()->create(['telefono_whatsapp' => '5493704000001']);
        $servicioPermitido = Servicio::factory()->create(['id_cliente' => $clientePermitido->id_cliente]);
        $servicioNoPermitido = Servicio::factory()->create(['id_cliente' => $clienteNoPermitido->id_cliente]);
        $cuotaSeleccionada = Cuota::factory()->create([
            'id_servicio' => $servicioPermitido->id_servicio,
            'fecha_vencimiento' => $fechaVencimiento,
        ]);
        $cuotaNoPermitida = Cuota::factory()->create([
            'id_servicio' => $servicioNoPermitido->id_servicio,
            'fecha_vencimiento' => $fechaVencimiento,
        ]);

        $this->artisan('whatsapp:notificar-vencimientos', ['--id-cuota' => $cuotaSeleccionada->id_cuota])
            ->assertExitCode(0);
        Http::assertSentCount(1);
        $this->assertDatabaseHas('mensaje', ['clave_idempotencia' => 'vencimiento:proxima:'.$cuotaSeleccionada->id_cuota]);
        $this->assertDatabaseMissing('mensaje', ['clave_idempotencia' => 'vencimiento:proxima:'.$cuotaNoPermitida->id_cuota]);

        $this->artisan('whatsapp:notificar-vencimientos')->assertExitCode(0);
        Http::assertSentCount(1);
        $this->assertDatabaseCount('mensaje', 1);
    }

    public function test_cuota_vencida_usa_la_plantilla_y_el_texto_de_demostracion_correctos(): void
    {
        config([
            'services.whatsapp.modo_simulacion' => false,
            'services.whatsapp.access_token' => 'token-de-prueba',
            'services.whatsapp.phone_number_id' => 'telefono-de-prueba',
            'services.whatsapp.graph_api_version' => 'v25.0',
            'services.whatsapp.destinatarios_permitidos' => '543704061032',
        ]);
        Http::fake(['*' => Http::response(['messages' => [['id' => 'wamid.vencida']]])]);

        $cliente = Cliente::factory()->create([
            'nombre_razon_social' => 'Cliente de prueba',
            'telefono_whatsapp' => '5493704061032',
        ]);
        $servicio = Servicio::factory()->create(['id_cliente' => $cliente->id_cliente]);
        $fechaVencimiento = CarbonImmutable::today('America/Argentina/Buenos_Aires')->subDay();
        $cuota = Cuota::factory()->create([
            'id_servicio' => $servicio->id_servicio,
            'monto' => '18000.00',
            'fecha_vencimiento' => $fechaVencimiento->toDateString(),
        ]);

        $this->assertSame(
            ['enviadas' => 1, 'omitidas' => 0, 'fallidas' => 0],
            app(ServicioNotificacionesVencimiento::class)->enviar(),
        );
        Http::assertSent(fn ($solicitud): bool => $solicitud['template']['name'] === 'recordatorio_cuota_vencida'
            && $solicitud['template']['components'][0]['parameters'][1]['text'] === $fechaVencimiento->format('d/m/Y')
            && $solicitud['template']['components'][0]['parameters'][2]['text'] === '$18.000,00');
        $this->assertDatabaseHas('mensaje', [
            'id_mensaje_externo' => 'wamid.vencida',
            'contenido' => sprintf('Villafañe Wifi (demostración): Hola Cliente de prueba, la cuota de prueba venció el %s y su importe simulado pendiente es $18.000,00. No realices pagos; este aviso forma parte de una prueba.', $fechaVencimiento->format('d/m/Y')),
        ]);
        $this->assertSame('vencimiento:vencida:'.$cuota->id_cuota, Mensaje::query()->sole()->clave_idempotencia);
    }

    public function test_recibe_comprobante_simulado_y_cierra_la_conversacion(): void
    {
        Storage::fake('local');
        $cliente = Cliente::factory()->create();
        $conversacion = Conversacion::factory()->create([
            'id_cliente' => $cliente->id_cliente,
            'paso_actual' => 'esperando_comprobante',
        ]);
        $mensaje = Mensaje::factory()->create([
            'id_conversacion' => $conversacion->id_conversacion,
            'tipo' => TipoMensaje::Documento,
            'contenido' => 'comprobante.pdf',
            'archivo_adjunto' => 'meta://simulado-documento-1',
        ]);

        $respuesta = app(ServicioAtencionWhatsapp::class)->procesarMensaje($conversacion, $mensaje, $cliente);

        $this->assertStringContainsString('Recibimos', $respuesta);
        $this->assertSame(EstadoConversacion::Cerrada, $conversacion->fresh()->estado);
        $this->assertDatabaseHas('comprobante', ['id_mensaje' => $mensaje->id_mensaje]);
    }

    public function test_simulador_procesa_un_mensaje_sin_credenciales_externas(): void
    {
        $usuario = app(ServicioUsuarios::class)->crear([
            'nombre_usuario' => 'operador-simulador',
            'contrasena' => 'clave-segura',
            'tipo' => 'empleado',
            'area' => 'soporte',
        ]);

        $respuesta = $this->actingAs($usuario)->post('/simulador-whatsapp', [
            'telefono' => '5493704555666',
            'tipo' => 'texto',
            'contenido' => 'Mi DNI es 30.111.222',
        ]);

        $conversacion = Conversacion::where('numero_whatsapp', '5493704555666')->firstOrFail();
        $respuesta->assertRedirect(route('conversaciones.show', $conversacion));
        $this->assertSame(2, $conversacion->mensajes()->count());
    }
}
