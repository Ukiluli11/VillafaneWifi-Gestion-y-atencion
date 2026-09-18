<?php

namespace Tests\Feature;

use App\Contratos\DescargadorArchivosWhatsapp;
use App\Enums\EstadoEnvioMensaje;
use App\Infraestructura\Whatsapp\DescargadorArchivosMetaWhatsapp;
use App\Models\Administrador;
use App\Models\Cliente;
use App\Models\Comprobante;
use App\Models\Conversacion;
use App\Models\Cuota;
use App\Models\Mensaje;
use App\Models\Plan;
use App\Models\Servicio;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
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
        config()->set('services.whatsapp.descargar_archivos', false);
        $numeroRespuesta = 0;
        Http::fake(function (Request $solicitud) use (&$numeroRespuesta) {
            if (str_contains($solicitud->url(), 'media-real-123')) {
                return Http::response([
                    'url' => 'https://lookaside.fbsbx.com/whatsapp-media/archivo-real',
                    'mime_type' => 'image/jpeg',
                    'file_size' => 14,
                    'id' => 'media-real-123',
                ]);
            }

            if (str_contains($solicitud->url(), 'lookaside.fbsbx.com/whatsapp-media/archivo-real')) {
                return Http::response('imagen-de-pago', 200, ['Content-Type' => 'image/jpeg']);
            }

            $numeroRespuesta++;

            return Http::response([
                'messages' => [['id' => 'wamid.respuesta-bot-'.$numeroRespuesta]],
            ]);
        });
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
            'id_mensaje_externo' => 'wamid.respuesta-bot-1',
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

        Http::assertSent(fn (Request $solicitud): bool => $solicitud['to'] === '543704123456'
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

    public function test_solicita_el_dni_y_guarda_la_conversacion_de_un_contacto_no_identificado(): void
    {
        $this->enviarEventoFirmado($this->eventoConMensaje())
            ->assertOk()
            ->assertJsonPath('clientes_no_identificados', 1)
            ->assertJsonPath('mensajes_procesados', 1)
            ->assertJsonPath('respuestas_enviadas', 1);

        $conversacion = Conversacion::query()->firstOrFail();
        $this->assertNull($conversacion->id_cliente);
        $this->assertSame('esperando_documento', $conversacion->estado_flujo->value);
        $this->assertSame(2, Mensaje::count());
        $this->assertSame(0, Cliente::count());
        Http::assertSent(fn (Request $solicitud): bool => $solicitud['to'] === '543704123456'
            && str_contains($solicitud['text']['body'], 'Escribí tu DNI'));
    }

    public function test_identifica_por_dni_un_cliente_existente_y_asocia_su_telefono(): void
    {
        $cliente = Cliente::factory()->create([
            'nombre_razon_social' => 'Ana Benítez',
            'numero_documento' => '30123456',
            'telefono_whatsapp' => null,
        ]);

        $this->enviarEventoFirmado($this->eventoConMensaje('Hola', 'wamid.inicial-desconocido'))->assertOk();
        $this->enviarEventoFirmado($this->eventoConMensaje('30.123.456', 'wamid.documento'))
            ->assertOk()
            ->assertJsonPath('mensajes_procesados', 1)
            ->assertJsonPath('respuestas_enviadas', 1);

        $conversacion = Conversacion::query()->firstOrFail();
        $this->assertSame($cliente->id_cliente, $conversacion->id_cliente);
        $this->assertSame('menu', $conversacion->estado_flujo->value);
        $this->assertSame('5493704123456', $cliente->refresh()->telefono_whatsapp);
        $this->assertStringContainsString(
            'Hola, Ana Benítez.',
            Mensaje::query()->latest('id_mensaje')->firstOrFail()->contenido,
        );
        $this->assertSame(4, Mensaje::count());
    }

    public function test_registra_un_nuevo_cliente_y_su_servicio_desde_la_conversacion(): void
    {
        $plan = Plan::factory()->create([
            'nombre' => 'Fibra 50',
            'velocidad' => '50 Mbps',
            'precio_vigente' => '25000.00',
            'estado' => 'activo',
        ]);

        $pasos = [
            ['Hola', 'wamid.registro-inicio'],
            ['32123456', 'wamid.registro-dni'],
            ['Lucía Martínez', 'wamid.registro-nombre'],
            ['Belgrano | 123 | Formosa', 'wamid.registro-contacto'],
            [(string) $plan->id_plan, 'wamid.registro-plan'],
            ['Mitre | 456 | Formosa', 'wamid.registro-instalacion'],
            ['15', 'wamid.registro-vencimiento'],
        ];

        foreach ($pasos as [$contenido, $identificador]) {
            $this->enviarEventoFirmado($this->eventoConMensaje($contenido, $identificador))
                ->assertOk()
                ->assertJsonPath('mensajes_procesados', 1)
                ->assertJsonPath('respuestas_enviadas', 1);
        }

        $cliente = Cliente::query()->where('numero_documento', '32123456')->firstOrFail();
        $this->assertSame('Lucía Martínez', $cliente->nombre_razon_social);
        $this->assertSame('5493704123456', $cliente->telefono_whatsapp);
        $this->assertDatabaseHas('servicio', [
            'id_cliente' => $cliente->id_cliente,
            'id_plan' => $plan->id_plan,
            'calle_instalacion' => 'Mitre',
            'numero_instalacion' => '456',
            'localidad_instalacion' => 'Formosa',
            'dia_vencimiento' => 15,
            'estado' => 'activo',
        ]);

        $conversacion = Conversacion::query()->firstOrFail();
        $this->assertSame($cliente->id_cliente, $conversacion->id_cliente);
        $this->assertSame('menu', $conversacion->estado_flujo->value);
        $this->assertNull($conversacion->datos_registro);
        $this->assertStringContainsString(
            'se completaron correctamente',
            Mensaje::query()->latest('id_mensaje')->firstOrFail()->contenido,
        );
        $this->assertSame(14, Mensaje::count());
        Http::assertSentCount(7);
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

    public function test_un_estado_tardio_no_hace_retroceder_un_mensaje_leido(): void
    {
        $mensaje = Mensaje::factory()->create([
            'id_mensaje_externo' => 'wamid.salida-leida',
            'tipo_emisor' => 'bot',
            'estado_envio' => EstadoEnvioMensaje::Leido,
        ]);
        $evento = [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'changes' => [[
                    'field' => 'messages',
                    'value' => ['statuses' => [['id' => 'wamid.salida-leida', 'status' => 'delivered']]],
                ]],
            ]],
        ];

        $this->enviarEventoFirmado($evento)
            ->assertOk()
            ->assertJsonPath('estados_actualizados', 0);

        $this->assertSame(EstadoEnvioMensaje::Leido, $mensaje->refresh()->estado_envio);
    }

    public function test_opcion_uno_responde_el_estado_de_cuenta_y_guarda_el_historial(): void
    {
        $this->travelTo('2026-09-16 10:00:00');
        $cliente = Cliente::factory()->create([
            'nombre_razon_social' => 'María González',
            'telefono_whatsapp' => '5493704123456',
        ]);
        $plan = Plan::factory()->create(['nombre' => 'Plan Hogar', 'velocidad' => '30/10 Mbps']);
        $servicio = Servicio::factory()->for($cliente, 'cliente')->for($plan, 'plan')->create();
        $cuotaVencida = Cuota::factory()->for($servicio, 'servicio')->create([
            'periodo' => '2026-08',
            'monto' => '18000.00',
            'fecha_vencimiento' => '2026-09-10',
            'estado' => 'pendiente',
        ]);
        Cuota::factory()->for($servicio, 'servicio')->create([
            'periodo' => '2026-09',
            'monto' => '21000.50',
            'fecha_vencimiento' => '2026-09-20',
            'estado' => 'pendiente',
        ]);
        $this->enviarEventoFirmado($this->eventoConMensaje('Hola', 'wamid.inicial'))->assertOk();
        $this->enviarEventoFirmado($this->eventoConMensaje('1', 'wamid.opcion-uno'))
            ->assertOk()
            ->assertJsonPath('mensajes_procesados', 1)
            ->assertJsonPath('respuestas_enviadas', 1);

        $respuesta = Mensaje::query()->latest('id_mensaje')->firstOrFail();
        $this->assertSame('bot', $respuesta->tipo_emisor->value);
        $this->assertStringStartsWith('Hola.', $respuesta->contenido);
        $this->assertStringContainsString('servicio virtual de Villafañe Wifi', $respuesta->contenido);
        $this->assertStringContainsString('Estado de cuenta de María González', $respuesta->contenido);
        $this->assertStringContainsString('Plan Hogar (30/10 Mbps)', $respuesta->contenido);
        $this->assertStringContainsString('Total pendiente: $39.000,50', $respuesta->contenido);
        $this->assertStringContainsString('Total vencido: $18.000,00', $respuesta->contenido);
        $this->assertStringContainsString('Próximo vencimiento: 20/09/2026', $respuesta->contenido);
        $this->assertSame('vencida', $cuotaVencida->refresh()->estado->value);
        $conversacion = Conversacion::query()->firstOrFail();
        $this->assertSame('cerrada', $conversacion->estado->value);
        $this->assertNotNull($conversacion->fecha_hora_cierre);
        $this->assertSame(4, Mensaje::count());
        Http::assertSentCount(2);
    }

    public function test_cliente_puede_solicitar_nuevamente_el_menu(): void
    {
        Cliente::factory()->create(['telefono_whatsapp' => '5493704123456']);

        $this->enviarEventoFirmado($this->eventoConMensaje('Hola', 'wamid.inicial'))->assertOk();
        $this->enviarEventoFirmado($this->eventoConMensaje('menú', 'wamid.menu'))
            ->assertOk()
            ->assertJsonPath('respuestas_enviadas', 1);

        $respuesta = Mensaje::query()->latest('id_mensaje')->firstOrFail();
        $this->assertStringStartsWith('Hola.', $respuesta->contenido);
        $this->assertStringContainsString('servicio virtual de Villafañe Wifi', $respuesta->contenido);
        $this->assertStringContainsString('Menú principal:', $respuesta->contenido);
        $this->assertStringContainsString('1. Consultar estado de cuenta', $respuesta->contenido);
        $this->assertSame(4, Mensaje::count());
    }

    public function test_pide_reformular_y_deriva_despues_de_dos_intenciones_no_reconocidas(): void
    {
        Cliente::factory()->create(['telefono_whatsapp' => '5493704123456']);

        $this->enviarEventoFirmado($this->eventoConMensaje('Hola', 'wamid.inicial'))->assertOk();
        $this->enviarEventoFirmado($this->eventoConMensaje('xyz abc', 'wamid.incierto-uno'))
            ->assertOk()
            ->assertJsonPath('respuestas_enviadas', 1);

        $conversacion = Conversacion::query()->firstOrFail();
        $this->assertSame(1, $conversacion->intentos_intencion);
        $this->assertSame('abierta', $conversacion->estado->value);
        $this->assertStringContainsString(
            'Reformulá tu consulta',
            Mensaje::query()->latest('id_mensaje')->firstOrFail()->contenido,
        );

        $this->enviarEventoFirmado($this->eventoConMensaje('otra cosa incierta', 'wamid.incierto-dos'))
            ->assertOk()
            ->assertJsonPath('respuestas_enviadas', 1);

        $conversacion->refresh();
        $this->assertSame('escalada', $conversacion->estado->value);
        $this->assertSame('usuario_interno', $conversacion->modo_atencion->value);
        $this->assertNull($conversacion->id_usuario_atencion);
        $this->assertSame(0, $conversacion->intentos_intencion);
        $this->assertStringContainsString(
            'atención humana',
            Mensaje::query()->latest('id_mensaje')->firstOrFail()->contenido,
        );
        Http::assertSentCount(3);
    }

    public function test_opcion_cuatro_deriva_directamente_a_atencion_humana(): void
    {
        Cliente::factory()->create(['telefono_whatsapp' => '5493704123456']);

        $this->enviarEventoFirmado($this->eventoConMensaje('Hola', 'wamid.inicial'))->assertOk();
        $this->enviarEventoFirmado($this->eventoConMensaje('4', 'wamid.atencion'))
            ->assertOk()
            ->assertJsonPath('respuestas_enviadas', 1);

        $conversacion = Conversacion::query()->firstOrFail();
        $this->assertSame('escalada', $conversacion->estado->value);
        $this->assertSame('usuario_interno', $conversacion->modo_atencion->value);
        $this->assertNull($conversacion->id_usuario_atencion);
        $this->assertStringContainsString(
            'primer empleado disponible',
            Mensaje::query()->latest('id_mensaje')->firstOrFail()->contenido,
        );
    }

    public function test_opcion_dos_solicita_y_registra_un_comprobante_pendiente(): void
    {
        Cliente::factory()->create([
            'nombre_razon_social' => 'María González',
            'telefono_whatsapp' => '5493704123456',
        ]);

        $this->enviarEventoFirmado($this->eventoConMensaje('Hola', 'wamid.inicial'))->assertOk();
        $this->enviarEventoFirmado($this->eventoConMensaje('2', 'wamid.opcion-dos'))
            ->assertOk()
            ->assertJsonPath('respuestas_enviadas', 1);

        $conversacion = Conversacion::query()->firstOrFail();
        $this->assertSame('esperando_comprobante', $conversacion->estado_flujo->value);
        $this->assertStringContainsString(
            'enviá ahora una foto o un archivo PDF',
            Mensaje::query()->latest('id_mensaje')->firstOrFail()->contenido,
        );
        $this->assertStringStartsWith(
            'Hola. Te atiende el servicio virtual de Villafañe Wifi.',
            Mensaje::query()->latest('id_mensaje')->firstOrFail()->contenido,
        );

        $this->enviarEventoFirmado($this->eventoConImagen('media-comprobante-1', 'wamid.comprobante'))
            ->assertOk()
            ->assertJsonPath('mensajes_procesados', 1)
            ->assertJsonPath('respuestas_enviadas', 1);

        $mensajeArchivo = Mensaje::query()->where('id_mensaje_externo', 'wamid.comprobante')->firstOrFail();
        $this->assertSame('imagen', $mensajeArchivo->tipo->value);
        $this->assertSame('meta-media:media-comprobante-1', $mensajeArchivo->archivo_adjunto);
        $this->assertDatabaseHas('comprobante', [
            'id_mensaje' => $mensajeArchivo->id_mensaje,
            'estado_validacion' => 'pendiente',
            'fecha_recepcion' => '2026-09-10 15:00:00',
        ]);
        $this->assertSame('menu', $conversacion->refresh()->estado_flujo->value);
        $this->assertSame('cerrada', $conversacion->estado->value);
        $this->assertNotNull($conversacion->fecha_hora_cierre);
        $this->assertStringContainsString(
            'pendiente de validación',
            Mensaje::query()->latest('id_mensaje')->firstOrFail()->contenido,
        );
        $this->assertSame(1, Comprobante::count());
        $this->assertSame(6, Mensaje::count());
        Http::assertSentCount(3);
    }

    public function test_descarga_un_comprobante_real_al_recibirlo_por_el_webhook(): void
    {
        config([
            'services.whatsapp.modo_simulacion' => false,
            'services.whatsapp.descargar_archivos' => true,
        ]);
        $this->app->bind(DescargadorArchivosWhatsapp::class, DescargadorArchivosMetaWhatsapp::class);

        Storage::fake('local');

        $cliente = Cliente::factory()->create([
            'telefono_whatsapp' => '5493704123456',
        ]);
        Servicio::factory()->for($cliente, 'cliente')->create(['estado' => 'activo']);

        $this->enviarEventoFirmado($this->eventoConMensaje('Hola', 'wamid.inicial'))->assertOk();
        $this->enviarEventoFirmado($this->eventoConMensaje('2', 'wamid.opcion-pago'))->assertOk();
        $this->enviarEventoFirmado($this->eventoConImagen('media-real-123', 'wamid.comprobante-real'))->assertOk();

        $mensaje = Mensaje::query()
            ->where('id_mensaje_externo', 'wamid.comprobante-real')
            ->firstOrFail();

        $this->assertStringStartsWith('whatsapp/comprobantes/', (string) $mensaje->archivo_adjunto);
        Storage::disk('local')->assertExists($mensaje->archivo_adjunto);
        $this->assertDatabaseHas('comprobante', [
            'id_mensaje' => $mensaje->id_mensaje,
            'estado_validacion' => 'pendiente',
        ]);
    }

    public function test_si_espera_comprobante_un_texto_no_crea_el_registro(): void
    {
        Cliente::factory()->create(['telefono_whatsapp' => '5493704123456']);

        $this->enviarEventoFirmado($this->eventoConMensaje('Hola', 'wamid.inicial'))->assertOk();
        $this->enviarEventoFirmado($this->eventoConMensaje('2', 'wamid.opcion-dos'))->assertOk();
        $this->enviarEventoFirmado($this->eventoConMensaje('ya transferí', 'wamid.texto-invalido'))
            ->assertOk()
            ->assertJsonPath('respuestas_enviadas', 1);

        $this->assertSame(0, Comprobante::count());
        $this->assertSame(
            'esperando_comprobante',
            Conversacion::query()->firstOrFail()->estado_flujo->value,
        );
        $this->assertStringContainsString(
            'una imagen o un archivo PDF',
            Mensaje::query()->latest('id_mensaje')->firstOrFail()->contenido,
        );
    }

    public function test_opcion_tres_solicita_descripcion_y_genera_un_ticket(): void
    {
        $cliente = Cliente::factory()->create([
            'nombre_razon_social' => 'María González',
            'telefono_whatsapp' => '5493704123456',
        ]);
        $servicio = Servicio::factory()->for($cliente, 'cliente')->create([
            'calle_instalacion' => 'Belgrano',
            'numero_instalacion' => '123',
            'localidad_instalacion' => 'Formosa',
        ]);

        $this->enviarEventoFirmado($this->eventoConMensaje('Hola', 'wamid.inicial'))->assertOk();
        $this->enviarEventoFirmado($this->eventoConMensaje('3', 'wamid.opcion-tres'))
            ->assertOk()
            ->assertJsonPath('respuestas_enviadas', 1);

        $conversacion = Conversacion::query()->firstOrFail();
        $this->assertSame('esperando_descripcion_reclamo', $conversacion->estado_flujo->value);
        $this->assertStringContainsString(
            'Describí el problema',
            Mensaje::query()->latest('id_mensaje')->firstOrFail()->contenido,
        );

        $this->enviarEventoFirmado($this->eventoConMensaje('Estoy sin Internet desde ayer', 'wamid.reclamo'))
            ->assertOk()
            ->assertJsonPath('mensajes_procesados', 1)
            ->assertJsonPath('respuestas_enviadas', 1);

        $ticket = Ticket::query()->firstOrFail();
        $this->assertSame($conversacion->id_conversacion, $ticket->id_conversacion);
        $this->assertSame($servicio->id_servicio, $ticket->id_servicio);
        $this->assertNull($ticket->id_empleado);
        $this->assertSame('tecnico', $ticket->tipo->value);
        $this->assertSame('abierto', $ticket->estado->value);
        $this->assertSame('Estoy sin Internet desde ayer', $ticket->descripcion);
        $this->assertSame('menu', $conversacion->refresh()->estado_flujo->value);
        $this->assertSame('cerrada', $conversacion->estado->value);
        $this->assertNotNull($conversacion->fecha_hora_cierre);
        $this->assertStringContainsString(
            "ticket #{$ticket->id_ticket}",
            Mensaje::query()->latest('id_mensaje')->firstOrFail()->contenido,
        );
        $this->assertStringContainsString(
            'Será atendido por orden de llegada',
            Mensaje::query()->latest('id_mensaje')->firstOrFail()->contenido,
        );
    }

    public function test_un_cliente_con_varios_servicios_debe_indicar_cual_esta_afectado(): void
    {
        $cliente = Cliente::factory()->create(['telefono_whatsapp' => '5493704123456']);
        $primerServicio = Servicio::factory()->for($cliente, 'cliente')->create();
        $segundoServicio = Servicio::factory()->for($cliente, 'cliente')->create();

        $this->enviarEventoFirmado($this->eventoConMensaje('Hola', 'wamid.inicial'))->assertOk();
        $this->enviarEventoFirmado($this->eventoConMensaje('3', 'wamid.opcion-tres'))->assertOk();

        $solicitud = Mensaje::query()->latest('id_mensaje')->firstOrFail();
        $this->assertStringContainsString("#{$primerServicio->id_servicio}", $solicitud->contenido);
        $this->assertStringContainsString("#{$segundoServicio->id_servicio}", $solicitud->contenido);

        $this->enviarEventoFirmado($this->eventoConMensaje(
            "#{$segundoServicio->id_servicio} La conexión se corta",
            'wamid.reclamo-segundo-servicio',
        ))->assertOk();

        $this->assertDatabaseHas('ticket', [
            'id_servicio' => $segundoServicio->id_servicio,
            'descripcion' => 'La conexión se corta',
            'estado' => 'abierto',
        ]);
    }

    public function test_bot_no_responde_mientras_un_usuario_atiende_la_conversacion(): void
    {
        Cliente::factory()->create(['telefono_whatsapp' => '5493704123456']);
        $this->enviarEventoFirmado($this->eventoConMensaje('Hola', 'wamid.inicial'))->assertOk();

        $administrador = Administrador::factory()->create();
        $conversacion = Conversacion::query()->firstOrFail();
        $conversacion->update([
            'id_usuario_atencion' => $administrador->id_administrador,
            'estado' => 'escalada',
            'modo_atencion' => 'usuario_interno',
            'inicio_atencion' => now(),
        ]);

        $this->enviarEventoFirmado($this->eventoConMensaje('Sigo sin conexión', 'wamid.atencion-humana'))
            ->assertOk()
            ->assertJsonPath('mensajes_procesados', 1)
            ->assertJsonPath('respuestas_enviadas', 0);

        $this->assertDatabaseHas('mensaje', [
            'id_mensaje_externo' => 'wamid.atencion-humana',
            'contenido' => 'Sigo sin conexión',
            'tipo_emisor' => 'cliente',
        ]);
        $this->assertSame(3, Mensaje::count());
        Http::assertSentCount(1);
    }

    public function test_un_nuevo_mensaje_inicia_otra_conversacion_despues_del_cierre_automatico(): void
    {
        Cliente::factory()->create(['telefono_whatsapp' => '5493704123456']);

        $this->enviarEventoFirmado($this->eventoConMensaje('Hola', 'wamid.inicial'))->assertOk();
        $this->enviarEventoFirmado($this->eventoConMensaje('1', 'wamid.consulta'))->assertOk();
        $primera = Conversacion::query()->firstOrFail();
        $this->assertSame('cerrada', $primera->estado->value);

        $this->enviarEventoFirmado($this->eventoConMensaje('Hola nuevamente', 'wamid.nueva'))->assertOk();

        $this->assertSame(2, Conversacion::count());
        $segunda = Conversacion::query()->latest('id_conversacion')->firstOrFail();
        $this->assertSame('abierta', $segunda->estado->value);
        $this->assertNotSame($primera->id_conversacion, $segunda->id_conversacion);
    }

    /**
     * Construye una notificación equivalente a la publicada por Meta.
     *
     * @return array<string, mixed>
     */
    private function eventoConMensaje(
        string $contenido = 'Quiero consultar mi deuda',
        string $identificador = 'wamid.mensaje-1',
    ): array {
        return [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'changes' => [[
                    'field' => 'messages',
                    'value' => [
                        'messaging_product' => 'whatsapp',
                        'messages' => [[
                            'from' => '5493704123456',
                            'id' => $identificador,
                            'timestamp' => '1789052400',
                            'type' => 'text',
                            'text' => ['body' => $contenido],
                        ]],
                    ],
                ]],
            ]],
        ];
    }

    /**
     * Construye el evento de una imagen sin descargarla todavía desde Meta.
     *
     * @return array<string, mixed>
     */
    private function eventoConImagen(string $identificadorArchivo, string $identificadorMensaje): array
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
                            'id' => $identificadorMensaje,
                            'timestamp' => '1789052400',
                            'type' => 'image',
                            'image' => [
                                'id' => $identificadorArchivo,
                                'caption' => 'Comprobante de transferencia',
                            ],
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
