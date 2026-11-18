<?php

namespace Tests\Feature;

use App\Dominio\IAService;
use App\Dominio\ServicioAtencionHumana;
use App\Dominio\ServicioAtencionWhatsapp;
use App\Dominio\ServicioClientes;
use App\Dominio\ServicioConversaciones;
use App\Dominio\ServicioCuentaCorriente;
use App\Dominio\ServicioIdentificacion;
use App\Dominio\ServicioNotificacionesVencimiento;
use App\Dominio\ServicioUsuarios;
use App\Dominio\WhatsAppService;
use App\Enums\EstadoConversacion;
use App\Enums\EstadoEnvioMensaje;
use App\Enums\IntencionConversacion;
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
use PHPUnit\Framework\Attributes\TestWith;
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
            'services.whatsapp.plantillas_reales_habilitadas' => true,
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

    public function test_dni_desde_numero_desconocido_no_vincula_ni_cambia_el_telefono(): void
    {
        $secreto = 'secreto-webhook-de-prueba';
        config([
            'services.whatsapp.validar_firma' => true,
            'services.whatsapp.app_secret' => $secreto,
        ]);
        $cliente = Cliente::factory()->create(['telefono_whatsapp' => '543704061032', 'numero_documento' => '30111222', 'tipo_documento' => 'DNI']);
        $telefono = '5493704111222';

        foreach (['Mi DNI es 30.111.222', '1', 'REGISTRARME', 'DNI', '30111222'] as $indice => $texto) {
            $payload = ['entry' => [['changes' => [['value' => ['messages' => [[
                'id' => 'wamid.verificacion.'.$indice, 'from' => $telefono,
                'timestamp' => (string) now()->timestamp, 'type' => 'text', 'text' => ['body' => $texto],
            ]]]]]]]];
            $firma = 'sha256='.hash_hmac('sha256', json_encode($payload, JSON_THROW_ON_ERROR), $secreto);

            if ($indice === 0) {
                $this->postJson('/webhooks/whatsapp', $payload)->assertUnauthorized();
                $adulterado = $payload;
                $adulterado['entry'][0]['changes'][0]['value']['messages'][0]['text']['body'] = 'PAGAR';
                $this->postJson('/webhooks/whatsapp', $adulterado, ['X-Hub-Signature-256' => $firma])->assertUnauthorized();
                $this->assertDatabaseCount('conversacion', 0);
                $this->assertDatabaseCount('mensaje', 0);
            }

            $this->postJson('/webhooks/whatsapp', $payload, ['X-Hub-Signature-256' => $firma])->assertOk();
        }

        $conversacion = Conversacion::where('numero_whatsapp', $telefono)->sole();
        $this->assertNull($conversacion->id_cliente);
        $this->assertSame('543704061032', $cliente->fresh()->telefono_whatsapp);
        $this->assertSame(EstadoConversacion::Escalada, $conversacion->estado);
        $this->assertFalse($conversacion->mensajes()->where('contenido', 'like', '%Total pendiente%')->exists());
        $this->assertFalse($conversacion->mensajes()->where('contenido', 'like', '%Estás al día%')->exists());

        $legitima = app(ServicioConversaciones::class)->obtenerOCrearConversacion('5493704061032');
        $this->assertTrue($cliente->is(app(ServicioIdentificacion::class)->identificarCliente($legitima, '5493704061032')));
    }

    public function test_cambio_de_telefono_no_reutiliza_la_conversacion_del_numero_anterior(): void
    {
        $cliente = Cliente::factory()->create(['telefono_whatsapp' => '5493704061032']);
        $anterior = Conversacion::factory()->create(['id_cliente' => $cliente->id_cliente, 'numero_whatsapp' => $cliente->telefono_whatsapp]);
        $cliente->update(['telefono_whatsapp' => '5493704000000']);

        $operador = app(ServicioUsuarios::class)->crear([
            'nombre_usuario' => 'operador-cambio-telefono', 'contrasena' => 'clave-segura',
            'tipo' => 'empleado', 'area' => 'soporte',
        ]);
        $this->actingAs($operador)->post(route('conversaciones.tomar', $anterior))->assertSessionHasErrors('conversacion');
        $this->assertFalse($anterior->mensajes()->exists());

        $conversaciones = app(ServicioConversaciones::class);
        $nueva = $conversaciones->obtenerOCrearConversacion($anterior->numero_whatsapp);
        $this->assertFalse($anterior->is($nueva));
        $this->assertSame(EstadoConversacion::Cerrada, $anterior->fresh()->estado);
        $this->assertNull(app(ServicioIdentificacion::class)->identificarCliente($nueva, $anterior->numero_whatsapp));
    }

    public function test_telefono_compartido_no_exhibe_una_cuenta_arbitraria(): void
    {
        Cliente::factory()->create(['telefono_whatsapp' => '5493704061032']);
        Cliente::factory()->create(['telefono_whatsapp' => '543704061032']);

        $this->assertNull(app(ServicioIdentificacion::class)->clientePorTelefono('5493704061032'));
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

    #[TestWith(['2026-11-17T13:00:00Z', 'Buenos días'])]
    #[TestWith(['2026-11-17T22:00:00Z', 'Buenas tardes'])]
    #[TestWith(['2026-11-18T00:00:00Z', 'Buenas noches'])]
    public function test_coquito_saluda_en_hora_local_y_muestra_el_menu(string $fecha, string $saludo): void
    {
        $cliente = Cliente::factory()->create();
        $conversacion = Conversacion::factory()->for($cliente, 'cliente')->create();
        $mensaje = Mensaje::factory()->for($conversacion, 'conversacion')->create([
            'contenido' => 'Holaaa, Coquito!', 'fecha_hora' => CarbonImmutable::parse($fecha)->utc(),
        ]);
        $respuesta = app(ServicioAtencionWhatsapp::class)->procesarMensaje($conversacion, $mensaje, $cliente);

        $this->assertStringContainsString($saludo, $respuesta);
        $this->assertStringContainsString('Soy Coquito', $respuesta);
        $this->assertStringContainsString('5. Ver cómo pagar', $respuesta);
        $this->assertStringContainsString('tus propias palabras', $respuesta);
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

        $this->assertStringContainsString('2. Registrar un reclamo técnico', $respuesta);
        $this->assertStringContainsString('3. Enviar un comprobante', $respuesta);
        $this->assertNull($conversacion->fresh()->paso_actual);
        $this->assertSame(0, $conversacion->fresh()->intentos_interpretacion);

        $mensaje = Mensaje::factory()->create([
            'id_conversacion' => $conversacion->id_conversacion,
            'contenido' => '3',
        ]);
        $respuesta = app(ServicioAtencionWhatsapp::class)->procesarMensaje($conversacion->fresh(), $mensaje, $cliente);
        $this->assertStringContainsString('adjuntar', $respuesta);
        $this->assertSame('esperando_comprobante', $conversacion->fresh()->paso_actual);

        $mensaje = Mensaje::factory()->for($conversacion, 'conversacion')->create(['contenido' => '#4']);
        $respuesta = app(ServicioAtencionWhatsapp::class)->procesarMensaje($conversacion->fresh(), $mensaje, $cliente);
        $this->assertStringContainsString('Te transferimos con un operador', $respuesta);
        $this->assertSame(EstadoConversacion::Escalada, $conversacion->fresh()->estado);
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

    public function test_recordatorios_omiten_un_telefono_asociado_a_dos_clientes(): void
    {
        $cliente = Cliente::factory()->create(['telefono_whatsapp' => '5493704000000', 'recordatorios_whatsapp' => true]);
        Cliente::factory()->create(['telefono_whatsapp' => '543704000000']);
        $servicio = Servicio::factory()->for($cliente, 'cliente')->create(['estado' => 'activo']);
        $cuota = Cuota::factory()->for($servicio, 'servicio')->create([
            'fecha_vencimiento' => CarbonImmutable::today('America/Argentina/Buenos_Aires')->addDay()->toDateString(),
        ]);

        $this->assertSame(
            ['enviadas' => 0, 'omitidas' => 1, 'fallidas' => 0],
            app(ServicioNotificacionesVencimiento::class)->enviar($cuota->id_cuota),
        );
        $this->assertDatabaseCount('mensaje', 0);
    }

    public function test_recordatorios_de_vencimiento_no_se_duplican(): void
    {
        $cliente = Cliente::factory()->create(['telefono_whatsapp' => '5493704000000', 'recordatorios_whatsapp' => true]);
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

    public function test_comando_respeta_la_cuota_elegida_y_la_autorizacion_desde_la_ficha(): void
    {
        config([
            'services.whatsapp.modo_simulacion' => false,
            'services.whatsapp.access_token' => 'token-de-prueba',
            'services.whatsapp.phone_number_id' => 'telefono-de-prueba',
            'services.whatsapp.destinatarios_permitidos' => '543704000000',
            'services.whatsapp.plantillas_destinatarios' => '543704000000',
            'services.whatsapp.control_consumo_habilitado' => true,
        ]);
        Http::fakeSequence()
            ->push(['messages' => [['id' => 'wamid.cuota-seleccionada']]])
            ->push(['messages' => [['id' => 'wamid.segunda-cuota']]]);

        $fechaVencimiento = CarbonImmutable::today('America/Argentina/Buenos_Aires')->addDay()->toDateString();
        $clientePermitido = Cliente::factory()->create(['telefono_whatsapp' => '5493704000000', 'recordatorios_whatsapp' => true]);
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

        app(ServicioClientes::class)->configurarRecordatorios($clienteNoPermitido, true);
        $this->artisan('whatsapp:notificar-vencimientos')->assertExitCode(0);
        Http::assertSentCount(2);
        Http::assertSent(fn ($solicitud): bool => $solicitud['type'] === 'template' && $solicitud['to'] === '5493704000001');
        $this->assertDatabaseHas('consumo_whatsapp_mes', ['plantillas_reservadas' => 2]);
    }

    public function test_cuota_vencida_usa_la_plantilla_y_el_texto_de_demostracion_correctos(): void
    {
        config([
            'services.whatsapp.plantilla_vencida' => 'recordatorio_cuota_vencida',
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
            'recordatorios_whatsapp' => true,
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

    public function test_recordatorio_vencido_requiere_autorizacion_del_cliente_y_habilitacion_global(): void
    {
        config([
            'services.whatsapp.modo_simulacion' => false,
            'services.whatsapp.access_token' => 'token-de-prueba',
            'services.whatsapp.phone_number_id' => 'telefono-de-prueba',
            'services.whatsapp.destinatarios_permitidos' => '543704000000',
            'services.whatsapp.plantillas_reales_habilitadas' => false,
        ]);
        Http::fake(['*' => Http::response(['messages' => [['id' => 'wamid.prueba-cliente']]])]);
        $cliente = Cliente::factory()->create(['telefono_whatsapp' => '5493704000000']);
        $servicio = Servicio::factory()->for($cliente, 'cliente')->create(['estado' => 'activo']);
        $cuota = Cuota::factory()->for($servicio, 'servicio')->create([
            'fecha_vencimiento' => CarbonImmutable::today('America/Argentina/Buenos_Aires')->subDay()->toDateString(),
        ]);

        $notificaciones = app(ServicioNotificacionesVencimiento::class);
        $cliente->forceFill(['recordatorios_whatsapp' => true])->save();
        $this->assertSame(0, $notificaciones->enviar($cuota->getKey())['enviadas']);
        config(['services.whatsapp.plantillas_reales_habilitadas' => true, 'services.whatsapp.recordatorios_habilitados' => false]);
        $this->assertSame(0, $notificaciones->enviar($cuota->getKey())['enviadas']);

        config(['services.whatsapp.recordatorios_habilitados' => true]);
        $cliente->forceFill(['recordatorios_whatsapp' => false])->save();
        $this->assertSame(1, $notificaciones->enviar($cuota->getKey())['omitidas']);
        $this->assertDatabaseCount('mensaje', 0);
        Http::assertNothingSent();

        $cliente->forceFill(['recordatorios_whatsapp' => true])->save();
        $this->assertSame(1, $notificaciones->enviar($cuota->getKey())['enviadas']);
        Http::assertSentCount(1);
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

    public function test_ia_clasifica_y_recurre_a_reglas_si_falla_el_proveedor(): void
    {
        config([
            'services.whatsapp.modo_simulacion' => false,
            'services.openai.api_key' => 'credencial-ficticia',
            'services.openai.model' => 'modelo-ficticio',
            'services.openai.base_url' => 'https://ia.ejemplo.test/v1',
        ]);
        Http::preventStrayRequests();
        Http::fake(['https://ia.ejemplo.test/v1/responses' => Http::sequence()
            ->push(['output' => [['type' => 'message', 'content' => [[
                'type' => 'output_text', 'text' => '{"intencion":"consulta_cuenta","confianza":0.98}',
            ]]]]])
            ->pushStatus(503)]);

        $ia = app(IAService::class);
        $this->assertSame(IntencionConversacion::ConsultaCuenta, $ia->interpretarIntencion('Quisiera saber cómo estoy')['intencion']);
        $this->assertSame(IntencionConversacion::ReclamoSoporte, $ia->interpretarIntencion('Estoy sin internet')['intencion']);
        Http::assertSentCount(2);
    }

    public function test_gemini_responde_repreguntas_sin_compartir_el_historial_de_cuenta(): void
    {
        $this->configurarGeminiFicticio();
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent';
        $clasificacion = ['intencion' => 'consulta_cuenta', 'confianza' => 0.98, 'pregunta' => ''];
        $salida = ['candidates' => [['content' => ['parts' => [['text' => json_encode($clasificacion)]]]]]];
        Http::fake([$url => Http::sequence()->push($salida)->push($salida)]);

        $cliente = Cliente::factory()->create();
        $servicio = Servicio::factory()->for($cliente)->create();
        Cuota::factory()->for($servicio)->create([
            'monto' => '100.00',
            'periodo' => '2026-11',
            'fecha_emision' => '2026-11-01',
            'fecha_vencimiento' => '2026-11-15',
            'estado' => 'vencida',
        ]);
        $conversaciones = app(ServicioConversaciones::class);
        $atencion = app(ServicioAtencionWhatsapp::class);

        foreach (['Quisiera saber cuánto me queda por abonar', 'Y cuando vence ?', '¿Y cuándo? Mi documento es 30.111.222'] as $indice => $texto) {
            $conversacion = $conversaciones->obtenerOCrearConversacion($cliente->telefono_whatsapp);
            $conversacion->update(['id_cliente' => $cliente->id_cliente]);
            $mensaje = $conversaciones->registrarMensajeEntrante($conversacion, [
                'id_externo' => "wamid.gemini.{$indice}",
                'telefono' => $cliente->telefono_whatsapp,
                'tipo' => 'texto',
                'contenido' => $texto,
                'fecha_hora' => CarbonImmutable::now(),
                'referencia_archivo' => null,
            ]);

            $this->assertSame(
                'Hay cuotas vencidas en tu cuenta. Total pendiente: $100,00. La cuota venció el 15/11/2026.',
                $atencion->procesarMensaje($conversacion, $mensaje, $cliente),
            );
            $this->assertSame(EstadoConversacion::Cerrada, $conversacion->fresh()->estado);
        }

        Http::assertSentCount(2);
        $solicitud = Http::recorded()->last()[0];
        $entrada = $solicitud['contents'][0]['parts'][0]['text'];
        $this->assertStringContainsString('Contexto: consulta_cuenta', $entrada);
        $this->assertStringContainsString('[dato omitido]', $entrada);
        $this->assertStringNotContainsString('30.111.222', $entrada);
        $this->assertStringNotContainsString('Total pendiente', $entrada);
        $this->assertTrue($solicitud->hasHeader('x-goog-api-key', 'credencial-gemini-ficticia'));
        $this->assertSame(0, $solicitud['generationConfig']['thinkingConfig']['thinkingBudget']);
    }

    public function test_gemini_filtra_preguntas_con_datos_sensibles_y_usa_respaldo_ante_fallo(): void
    {
        $this->configurarGeminiFicticio();
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent';
        $salida = fn (string $pregunta): array => ['candidates' => [['content' => ['parts' => [['text' => json_encode([
            'intencion' => 'desconocida', 'confianza' => 0.98, 'pregunta' => $pregunta,
        ])]]]]]];
        Http::fake([$url => Http::sequence()
            ->push($salida('¿Querés revisar tu cuenta o tenés un problema de conexión?'))
            ->push($salida('¿Cuál es tu contraseña?'))
            ->pushStatus(503)]);

        $ia = app(IAService::class);
        $this->assertSame('¿Querés revisar tu cuenta o tenés un problema de conexión?', $ia->interpretarIntencion('Eso mismo')['pregunta']);
        $this->assertNull($ia->interpretarIntencion('Tengo una consulta')['pregunta']);
        $this->assertSame(IntencionConversacion::ReclamoSoporte, $ia->interpretarIntencion('Estoy sin internet')['intencion']);
        $this->assertSame(IntencionConversacion::ConsultaCuenta, $ia->interpretarIntencion('1')['intencion']);
        Http::assertSentCount(3);

        config(['services.ia.destinatarios_demo' => '5493704000000']);
        $this->assertNull($ia->proveedorActivo('5493704000001'));
        $this->assertSame('gemini', $ia->proveedorActivo('543704000000'));
        $this->assertSame(IntencionConversacion::ReclamoSoporte, $ia->interpretarIntencion('Estoy sin internet', telefono: '5493704000001')['intencion']);
        Http::assertSentCount(3);
    }

    private function configurarGeminiFicticio(): void
    {
        config([
            'services.whatsapp.modo_simulacion' => false,
            'services.ia.proveedor' => 'gemini',
            'services.gemini.api_key' => 'credencial-gemini-ficticia',
            'services.gemini.model' => 'gemini-2.5-flash-lite',
        ]);
        Http::preventStrayRequests();
    }

    #[TestWith([''])]
    #[TestWith(['#'])]
    public function test_registro_y_contratacion_desde_numero_nuevo(string $prefijoPlan): void
    {
        Http::preventStrayRequests();
        $plan = Plan::factory()->create(['estado' => 'activo']);
        $numero = '5493704000101';
        $planInactivo = Plan::factory()->create(['estado' => 'inactivo']);
        $mensajes = [
            'hola', 'REGISTRARME', 'DNI', '-39999991', '39999991', 'Cliente Sintético', 'PARTICULAR',
            'Calle Uno | 10 | Villafañe', $prefijoPlan.$planInactivo->getKey(),
            $prefijoPlan.$plan->getKey(), 'Calle Dos | -20 | Villafañe', 'Calle Dos | 20 | Villafañe', '-15', '15',
        ];

        foreach ($mensajes as $indice => $texto) {
            $this->postJson('/webhooks/whatsapp', ['entry' => [['changes' => [['value' => ['messages' => [[
                'id' => "wamid.registro.$indice", 'from' => $numero, 'timestamp' => (string) now()->timestamp,
                'type' => 'text', 'text' => ['body' => $texto],
            ]]]]]]]])->assertOk();
        }

        $cliente = Cliente::where('numero_documento', '39999991')->sole();
        $servicio = Servicio::where('id_cliente', $cliente->getKey())->sole();
        $this->assertEquals($plan->getKey(), $servicio->id_plan);
        $this->assertEquals(15, $servicio->dia_vencimiento);
        $conversacion = Conversacion::where('id_cliente', $cliente->getKey())->sole();
        $this->assertSame(EstadoConversacion::Cerrada, $conversacion->estado);
        $respuesta = $conversacion->mensajes()->latest('id_mensaje')->firstOrFail()->contenido;
        $this->assertStringContainsString('Soy Coquito', $respuesta);
        $this->assertStringContainsString('1. Consultar saldo y vencimientos', $respuesta);
    }

    public function test_simulador_procesa_un_mensaje_sin_contactar_proveedores_aunque_haya_credenciales(): void
    {
        config(['services.openai.api_key' => 'credencial-ficticia', 'services.openai.model' => 'modelo-ficticio']);
        Http::fake();
        Cliente::factory()->create(['telefono_whatsapp' => '5493704555666']);
        $usuario = app(ServicioUsuarios::class)->crear([
            'nombre_usuario' => 'operador-simulador',
            'contrasena' => 'clave-segura',
            'tipo' => 'empleado',
            'area' => 'soporte',
        ]);

        $respuesta = $this->actingAs($usuario)->post('/simulador-whatsapp', [
            'telefono' => '5493704555666',
            'tipo' => 'texto',
            'contenido' => '¿Tengo deuda?',
        ]);

        $conversacion = Conversacion::where('numero_whatsapp', '5493704555666')->firstOrFail();
        $respuesta->assertRedirect(route('conversaciones.show', $conversacion));
        $this->assertSame(2, $conversacion->mensajes()->count());
        Http::assertNothingSent();
    }
}
