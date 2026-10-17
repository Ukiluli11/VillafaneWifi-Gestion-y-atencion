<?php

namespace Tests\Feature;

use App\Dominio\ServicioAtencionWhatsapp;
use App\Dominio\ServicioConversaciones;
use App\Dominio\ServicioIdentificacion;
use App\Dominio\ServicioUsuarios;
use App\Enums\EstadoConversacion;
use App\Enums\EstadoEnvioMensaje;
use App\Enums\TipoEmisorMensaje;
use App\Enums\TipoMensaje;
use App\Models\Cliente;
use App\Models\Conversacion;
use App\Models\Mensaje;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
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
