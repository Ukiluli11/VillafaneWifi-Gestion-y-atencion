<?php

namespace Tests\Feature;

use App\Dominio\ServicioAtencionHumana;
use App\Dominio\ServicioAtencionWhatsapp;
use App\Dominio\ServicioConversaciones;
use App\Dominio\ServicioCuentaCorriente;
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
use App\Models\CuentaReceptora;
use App\Models\Cuota;
use App\Models\Mensaje;
use App\Models\Plan;
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
            'services.whatsapp.control_consumo_habilitado' => false,
            'services.whatsapp.recordatorios_habilitados' => true,
            'services.whatsapp.recordatorios_destinatarios' => '543704061032,543704000000',
            'services.transferencias.habilitadas' => false,
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

    public function test_pagar_muestra_la_misma_cuenta_a_clientes_de_distintos_planes_y_sin_deuda(): void
    {
        $cliente = Cliente::factory()->create();
        $plan = Plan::factory()->create(['precio_vigente' => '100.00']);
        $servicio = Servicio::factory()->create(['id_cliente' => $cliente->id_cliente, 'id_plan' => $plan->id_plan]);
        $cuota = Cuota::factory()->create(['id_servicio' => $servicio->id_servicio, 'monto' => '100.00', 'id_pago' => null]);
        $cuenta = CuentaReceptora::factory()->create(['nombre' => 'Titular de prueba', 'tipo' => 'mercado_pago', 'identificador' => 'alias.ficticio', 'estado' => 'activa']);
        config(['services.transferencias.habilitadas' => true, 'services.transferencias.id_cuenta' => $cuenta->id_cuenta]);
        $conversacion = Conversacion::factory()->create(['id_cliente' => $cliente->id_cliente]);
        $mensaje = Mensaje::factory()->create(['id_conversacion' => $conversacion->id_conversacion, 'contenido' => 'PAGAR']);

        $respuesta = app(ServicioAtencionWhatsapp::class)->procesarMensaje($conversacion, $mensaje, $cliente);

        $this->assertStringContainsString('Pruebas académicas', $respuesta);
        $this->assertStringContainsString('$100,00', $respuesta);
        $this->assertStringContainsString('alias.ficticio', $respuesta);
        $this->assertStringContainsString('manualmente', $respuesta);
        $otro = Cliente::factory()->create();
        $otroPlan = Plan::factory()->create(['precio_vigente' => '200.00']);
        $otroServicio = Servicio::factory()->create(['id_cliente' => $otro->id_cliente, 'id_plan' => $otroPlan->id_plan]);
        Cuota::factory()->create(['id_servicio' => $otroServicio->id_servicio, 'monto' => '200.00', 'id_pago' => null]);
        $otrasInstrucciones = app(ServicioCuentaCorriente::class)->instruccionesTransferencia($otro);
        $this->assertStringContainsString('alias.ficticio', $otrasInstrucciones);
        $this->assertStringContainsString('$200,00', $otrasInstrucciones);
        $cuota->update(['estado' => 'pagada']);
        $sinDeuda = app(ServicioCuentaCorriente::class)->instruccionesTransferencia($cliente);
        $this->assertStringContainsString('alias.ficticio', $sinDeuda);
        $this->assertStringContainsString('No es necesario realizar una transferencia', $sinDeuda);
        $cuenta->update(['estado' => 'inactiva']);
        $this->assertNull(app(ServicioCuentaCorriente::class)->instruccionesTransferencia($otro));
    }

    public function test_saludos_agradecimientos_y_menu_no_acumulan_fallos(): void
    {
        $cliente = Cliente::factory()->create();
        $conversacion = Conversacion::factory()->create(['id_cliente' => $cliente->id_cliente]);

        foreach (['gracias', '¡Hola!', 'MENÚ', 'Muchas gracias.'] as $contenido) {
            $mensaje = Mensaje::factory()->create([
                'id_conversacion' => $conversacion->id_conversacion,
                'contenido' => $contenido,
            ]);
            $respuesta = app(ServicioAtencionWhatsapp::class)->procesarMensaje($conversacion->fresh(), $mensaje, $cliente);

            $this->assertNotNull($respuesta);
            $this->assertSame(0, $conversacion->fresh()->intentos_interpretacion);
            $this->assertSame(ModoAtencion::Bot, $conversacion->fresh()->modo_atencion);
        }
    }

    public function test_menu_cancela_la_espera_de_comprobante_y_permite_elegir_otra_opcion(): void
    {
        $cliente = Cliente::factory()->create();
        $conversacion = Conversacion::factory()->create([
            'id_cliente' => $cliente->id_cliente,
            'paso_actual' => 'esperando_comprobante',
            'intentos_interpretacion' => 1,
        ]);
        $mensaje = Mensaje::factory()->create([
            'id_conversacion' => $conversacion->id_conversacion,
            'contenido' => 'Menú!',
        ]);

        $respuesta = app(ServicioAtencionWhatsapp::class)->procesarMensaje($conversacion, $mensaje, $cliente);

        $this->assertStringContainsString('2. Registrar reclamo', $respuesta);
        $this->assertStringContainsString('3. Enviar comprobante', $respuesta);
        $this->assertNull($conversacion->fresh()->paso_actual);
        $this->assertSame(0, $conversacion->fresh()->intentos_interpretacion);

        $mensaje = Mensaje::factory()->create([
            'id_conversacion' => $conversacion->id_conversacion,
            'contenido' => '3',
        ]);
        $respuesta = app(ServicioAtencionWhatsapp::class)->procesarMensaje($conversacion->fresh(), $mensaje, $cliente);
        $this->assertStringContainsString('adjuntar', $respuesta);
        $this->assertSame('esperando_comprobante', $conversacion->fresh()->paso_actual);
    }

    public function test_menu_retoma_el_bot_si_la_derivacion_aun_no_tiene_operador(): void
    {
        $cliente = Cliente::factory()->create();
        $conversacion = Conversacion::factory()->create([
            'id_cliente' => $cliente->id_cliente,
            'estado' => EstadoConversacion::Escalada,
            'modo_atencion' => ModoAtencion::Humano,
            'intentos_interpretacion' => 2,
            'paso_actual' => 'esperando_reformulacion',
        ]);
        $mensaje = Mensaje::factory()->create([
            'id_conversacion' => $conversacion->id_conversacion,
            'contenido' => 'MENU',
        ]);

        $respuesta = app(ServicioAtencionWhatsapp::class)->procesarMensaje($conversacion, $mensaje, $cliente);

        $this->assertStringContainsString('Elegí una opción', $respuesta);
        $this->assertSame(EstadoConversacion::Abierta, $conversacion->fresh()->estado);
        $this->assertSame(ModoAtencion::Bot, $conversacion->fresh()->modo_atencion);
        $this->assertSame(0, $conversacion->fresh()->intentos_interpretacion);
    }

    public function test_menu_no_interrumpe_a_un_operador_que_tomo_la_conversacion(): void
    {
        $cliente = Cliente::factory()->create();
        $conversacion = Conversacion::factory()->create([
            'id_cliente' => $cliente->id_cliente,
            'estado' => EstadoConversacion::Escalada,
            'modo_atencion' => ModoAtencion::Humano,
        ]);
        $copiaAnterior = $conversacion->fresh();
        $operador = app(ServicioUsuarios::class)->crear([
            'nombre_usuario' => 'operador-menu',
            'contrasena' => 'clave-segura',
            'tipo' => 'empleado',
            'area' => 'soporte',
        ]);
        $conversacion->derivarAUsuario($operador);
        $mensaje = Mensaje::factory()->create([
            'id_conversacion' => $conversacion->id_conversacion,
            'contenido' => 'MENU',
        ]);

        $this->assertNull(app(ServicioAtencionWhatsapp::class)->procesarMensaje($copiaAnterior, $mensaje, $cliente));
        $this->assertSame(EstadoConversacion::EnAtencion, $conversacion->fresh()->estado);
        $this->assertSame($operador->id_usuario, $conversacion->fresh()->id_usuario_atencion);
    }

    public function test_un_fallo_del_dia_anterior_no_provoca_una_derivacion_inmediata(): void
    {
        $cliente = Cliente::factory()->create();
        $conversacion = Conversacion::factory()->create([
            'id_cliente' => $cliente->id_cliente,
            'intentos_interpretacion' => 1,
            'paso_actual' => 'esperando_reformulacion',
        ]);
        Mensaje::factory()->create([
            'id_conversacion' => $conversacion->id_conversacion,
            'fecha_hora' => now()->subDay(),
            'contenido' => 'mensaje incomprensible',
        ]);
        $mensaje = Mensaje::factory()->create([
            'id_conversacion' => $conversacion->id_conversacion,
            'contenido' => 'otra cosa extraña',
        ]);

        $respuesta = app(ServicioAtencionWhatsapp::class)->procesarMensaje($conversacion, $mensaje, $cliente);

        $this->assertStringContainsString('otras palabras', $respuesta);
        $this->assertSame(1, $conversacion->fresh()->intentos_interpretacion);
        $this->assertSame(ModoAtencion::Bot, $conversacion->fresh()->modo_atencion);
    }

    public function test_un_saludo_con_una_consulta_sigue_procesando_la_consulta(): void
    {
        $cliente = Cliente::factory()->create();
        $conversacion = Conversacion::factory()->create(['id_cliente' => $cliente->id_cliente]);
        $mensaje = Mensaje::factory()->create([
            'id_conversacion' => $conversacion->id_conversacion,
            'contenido' => 'Hola, quiero enviar un comprobante',
        ]);

        $respuesta = app(ServicioAtencionWhatsapp::class)->procesarMensaje($conversacion, $mensaje, $cliente);

        $this->assertStringContainsString('adjuntar', $respuesta);
        $this->assertSame('esperando_comprobante', $conversacion->fresh()->paso_actual);
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
            'contenido' => sprintf('Villafañe Wifi (demostración académica). Hola Cliente de prueba, tu cuota con vencimiento %s por $18.000,00 figura pendiente. Si ya enviaste un comprobante, aguardá su revisión antes de volver a pagar.', $fechaVencimiento->format('d/m/Y')),
        ]);
        $this->assertSame('vencimiento:vencida:'.$cuota->id_cuota, Mensaje::query()->sole()->clave_idempotencia);
    }

    public function test_recordatorios_desactivados_o_sin_lista_no_envian_a_clientes_de_la_demo_abierta(): void
    {
        config(['services.whatsapp.modo_simulacion' => false, 'services.whatsapp.recordatorios_destinatarios' => '',
            'services.whatsapp.destinatarios_permitidos' => '']);
        Http::preventStrayRequests();
        $cliente = Cliente::factory()->create(['telefono_whatsapp' => '5493704000000']);
        $servicio = Servicio::factory()->create(['id_cliente' => $cliente->id_cliente]);
        Cuota::factory()->create(['id_servicio' => $servicio->id_servicio, 'fecha_vencimiento' => today()->toDateString()]);

        $this->assertSame(['enviadas' => 0, 'omitidas' => 1, 'fallidas' => 0], app(ServicioNotificacionesVencimiento::class)->enviar());
        config(['services.whatsapp.recordatorios_habilitados' => false]);
        $this->assertSame(['enviadas' => 0, 'omitidas' => 0, 'fallidas' => 0], app(ServicioNotificacionesVencimiento::class)->enviar());

        Http::assertNothingSent();
        $this->assertDatabaseCount('mensaje', 0);
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
