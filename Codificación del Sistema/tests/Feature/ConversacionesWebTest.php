<?php

namespace Tests\Feature;

use App\Contratos\PuertaEnlaceWhatsapp;
use App\Dominio\ServicioUsuarios;
use App\Models\AvisoVencimiento;
use App\Models\Cliente;
use App\Models\Conversacion;
use App\Models\Mensaje;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Comprueba la consulta visual del historial de WhatsApp.
 */
class ConversacionesWebTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.whatsapp.url_base', 'https://graph.facebook.com');
        config()->set('services.whatsapp.version_api', 'v23.0');
        config()->set('services.whatsapp.id_numero_telefono', '123456789');
        config()->set('services.whatsapp.token_acceso', 'token-de-prueba');
        $numeroRespuesta = 0;
        Http::fake(function () use (&$numeroRespuesta) {
            $numeroRespuesta++;

            return Http::response([
                'messages' => [['id' => 'wamid.respuesta-humana-'.$numeroRespuesta]],
            ]);
        });
    }

    public function test_administrador_ve_el_listado_y_puede_filtrarlo(): void
    {
        $usuario = $this->crearUsuario('administrador');
        $visible = Cliente::factory()->create(['nombre_razon_social' => 'Cliente Conversación']);
        $oculto = Cliente::factory()->create(['nombre_razon_social' => 'Otro Cliente']);
        Conversacion::factory()->for($visible, 'cliente')->create(['estado' => 'abierta']);
        Conversacion::factory()->for($oculto, 'cliente')->create(['estado' => 'cerrada']);

        $this->actingAs($usuario)->get('/conversaciones?estado=abierta')
            ->assertOk()
            ->assertSee('Cliente Conversación')
            ->assertDontSee('Otro Cliente')
            ->assertSee('Conversaciones');
    }

    public function test_muestra_los_mensajes_en_el_detalle_de_la_conversacion(): void
    {
        $usuario = $this->crearUsuario('administrador');
        $cliente = Cliente::factory()->create(['nombre_razon_social' => 'María González']);
        $conversacion = Conversacion::factory()->for($cliente, 'cliente')->create();
        Mensaje::factory()->for($conversacion, 'conversacion')->create([
            'contenido' => 'Hola, necesito ayuda',
            'tipo_emisor' => 'cliente',
        ]);
        Mensaje::factory()->for($conversacion, 'conversacion')->create([
            'contenido' => 'Elegí una opción del menú',
            'tipo_emisor' => 'bot',
            'estado_envio' => 'enviado',
        ]);

        $this->actingAs($usuario)->get(route('conversaciones.show', $conversacion))
            ->assertOk()
            ->assertSee('María González')
            ->assertSee('Hola, necesito ayuda')
            ->assertSee('Elegí una opción del menú')
            ->assertSee('Bot Villafañe');
    }

    public function test_muestra_los_avisos_auditables_en_el_detalle_de_la_conversacion(): void
    {
        $usuario = $this->crearUsuario('administrador');
        $conversacion = Conversacion::factory()->create();
        AvisoVencimiento::factory()->create([
            'id_conversacion' => $conversacion->id_conversacion,
            'tipo' => 'vencido',
            'estado' => 'enviado',
            'fecha_hora_envio' => '2026-09-17 09:00:00',
        ]);

        $this->actingAs($usuario)->get(route('conversaciones.show', $conversacion))
            ->assertOk()
            ->assertSee('Avisos de vencimiento')
            ->assertSee('Cuota vencida')
            ->assertSee('Enviado')
            ->assertSee('17/09/2026 09:00');
    }

    public function test_empleado_de_soporte_puede_consultar_conversaciones(): void
    {
        $usuario = $this->crearUsuario('empleado');

        $this->actingAs($usuario)->get('/conversaciones')
            ->assertOk()
            ->assertSee('Atención por WhatsApp');
    }

    public function test_usuario_toma_conversacion_y_se_presenta_ante_cliente(): void
    {
        $usuario = $this->crearUsuario('empleado', 'responsable');
        $conversacion = Conversacion::factory()->create([
            'numero_whatsapp' => '5493704123456',
        ]);

        $this->actingAs($usuario)
            ->post(route('conversaciones.tomar', $conversacion))
            ->assertRedirect(route('conversaciones.show', $conversacion))
            ->assertSessionHas('exito');

        $conversacion->refresh();
        $this->assertSame($usuario->id_usuario, $conversacion->id_usuario_atencion);
        $this->assertSame('usuario_interno', $conversacion->modo_atencion->value);
        $this->assertSame('escalada', $conversacion->estado->value);
        $this->assertNotNull($conversacion->inicio_atencion);
        $this->assertDatabaseHas('mensaje', [
            'id_conversacion' => $conversacion->id_conversacion,
            'id_usuario' => $usuario->id_usuario,
            'tipo_emisor' => 'usuario_interno',
            'estado_envio' => 'enviado',
        ]);
        $presentacion = Mensaje::query()->latest('id_mensaje')->firstOrFail();
        $this->assertStringContainsString($usuario->nombre_usuario, $presentacion->contenido);
        $this->assertStringContainsString('Villafañe Wifi', $presentacion->contenido);
        Http::assertSent(fn (Request $solicitud): bool => $solicitud['to'] === '543704123456'
            && str_contains($solicitud['text']['body'], $usuario->nombre_usuario));
    }

    public function test_dos_usuarios_no_pueden_tomar_la_misma_conversacion(): void
    {
        $primero = $this->crearUsuario('empleado', 'primero');
        $segundo = $this->crearUsuario('empleado', 'segundo');
        $conversacion = Conversacion::factory()->create();

        $this->actingAs($primero)->post(route('conversaciones.tomar', $conversacion))
            ->assertSessionHasNoErrors();
        $this->actingAs($segundo)->post(route('conversaciones.tomar', $conversacion))
            ->assertSessionHasErrors('conversacion');

        $this->assertSame($primero->id_usuario, $conversacion->refresh()->id_usuario_atencion);
        Http::assertSentCount(1);
    }

    public function test_error_de_meta_no_rompe_la_pantalla_al_tomar_conversacion(): void
    {
        $this->mock(PuertaEnlaceWhatsapp::class, function ($puertaEnlace): void {
            $puertaEnlace->shouldReceive('enviarTexto')
                ->once()
                ->andThrow(new ConnectionException('Meta no está disponible.'));
        });

        $usuario = $this->crearUsuario('empleado', 'meta-no-disponible');
        $conversacion = Conversacion::factory()->create([
            'numero_whatsapp' => '5493704999999',
        ]);

        $this->actingAs($usuario)
            ->post(route('conversaciones.tomar', $conversacion))
            ->assertRedirect(route('conversaciones.show', $conversacion))
            ->assertSessionHasErrors('whatsapp');

        $this->assertSame($usuario->id_usuario, $conversacion->refresh()->id_usuario_atencion);
        $this->assertDatabaseHas('mensaje', [
            'id_conversacion' => $conversacion->id_conversacion,
            'id_usuario' => $usuario->id_usuario,
            'tipo_emisor' => 'usuario_interno',
            'estado_envio' => 'fallido',
        ]);
    }

    public function test_responsable_responde_y_mensaje_queda_en_historial(): void
    {
        $usuario = $this->crearUsuario('administrador', 'respuesta');
        $conversacion = Conversacion::factory()->create();
        $this->actingAs($usuario)->post(route('conversaciones.tomar', $conversacion));

        $this->actingAs($usuario)
            ->post(route('conversaciones.responder', $conversacion), [
                'contenido' => 'Voy a revisar la conexión y te aviso.',
            ])
            ->assertRedirect(route('conversaciones.show', $conversacion))
            ->assertSessionHas('exito');

        $this->assertDatabaseHas('mensaje', [
            'id_conversacion' => $conversacion->id_conversacion,
            'id_usuario' => $usuario->id_usuario,
            'contenido' => 'Voy a revisar la conexión y te aviso.',
            'tipo_emisor' => 'usuario_interno',
            'estado_envio' => 'enviado',
        ]);
        Http::assertSentCount(2);
    }

    public function test_responsable_cierra_la_conversacion_y_avisa_al_cliente(): void
    {
        $this->travelTo('2026-09-17 11:30:00');
        $usuario = $this->crearUsuario('empleado', 'cierre');
        $conversacion = Conversacion::factory()->create();
        $this->actingAs($usuario)->post(route('conversaciones.tomar', $conversacion));

        $this->actingAs($usuario)
            ->post(route('conversaciones.cerrar', $conversacion))
            ->assertRedirect(route('conversaciones.show', $conversacion))
            ->assertSessionHas('exito');

        $conversacion->refresh();
        $this->assertSame('cerrada', $conversacion->estado->value);
        $this->assertSame('2026-09-17 11:30:00', $conversacion->fecha_hora_cierre?->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-17 11:30:00', $conversacion->fin_atencion?->format('Y-m-d H:i:s'));
        $this->assertDatabaseHas('mensaje', [
            'id_conversacion' => $conversacion->id_conversacion,
            'id_usuario' => $usuario->id_usuario,
            'tipo_emisor' => 'usuario_interno',
            'estado_envio' => 'enviado',
        ]);
        $mensajeCierre = Mensaje::query()->latest('id_mensaje')->firstOrFail();
        $this->assertStringContainsString('Villafañe Wifi', $mensajeCierre->contenido);
        $this->assertStringContainsString('finalizada', $mensajeCierre->contenido);
        Http::assertSentCount(2);

        $this->actingAs($usuario)
            ->get(route('conversaciones.show', $conversacion))
            ->assertOk()
            ->assertSee('Conversación finalizada')
            ->assertDontSee('Cerrar conversación');
    }

    public function test_usuario_ajeno_no_puede_cerrar_conversacion_asignada(): void
    {
        $responsable = $this->crearUsuario('empleado', 'responsable-cierre');
        $ajeno = $this->crearUsuario('empleado', 'ajeno-cierre');
        $conversacion = Conversacion::factory()->create();
        $this->actingAs($responsable)->post(route('conversaciones.tomar', $conversacion));

        $this->actingAs($ajeno)
            ->post(route('conversaciones.cerrar', $conversacion))
            ->assertSessionHasErrors('conversacion');

        $this->assertSame('escalada', $conversacion->refresh()->estado->value);
        Http::assertSentCount(1);
    }

    public function test_administrador_puede_cerrar_una_conversacion_asignada_a_otro_usuario(): void
    {
        $responsable = $this->crearUsuario('empleado', 'responsable-admin');
        $administrador = $this->crearUsuario('administrador', 'cierre-ajeno');
        $conversacion = Conversacion::factory()->create();
        $this->actingAs($responsable)->post(route('conversaciones.tomar', $conversacion));

        $this->actingAs($administrador)
            ->post(route('conversaciones.cerrar', $conversacion))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('exito');

        $this->assertSame('cerrada', $conversacion->refresh()->estado->value);
        Http::assertSentCount(2);
    }

    public function test_usuario_ajeno_no_puede_responder_conversacion_asignada(): void
    {
        $responsable = $this->crearUsuario('empleado', 'responsable');
        $ajeno = $this->crearUsuario('empleado', 'ajeno');
        $conversacion = Conversacion::factory()->create();
        $this->actingAs($responsable)->post(route('conversaciones.tomar', $conversacion));

        $this->actingAs($ajeno)
            ->post(route('conversaciones.responder', $conversacion), [
                'contenido' => 'Este mensaje no debe salir.',
            ])
            ->assertSessionHasErrors('contenido');

        $this->assertDatabaseMissing('mensaje', [
            'contenido' => 'Este mensaje no debe salir.',
        ]);
        Http::assertSentCount(1);
    }

    private function crearUsuario(string $tipo, string $sufijo = ''): Usuario
    {
        return app(ServicioUsuarios::class)->crear([
            'nombre_usuario' => 'usuario-'.$tipo.($sufijo !== '' ? '-'.$sufijo : ''),
            'contrasena' => 'clave-segura',
            'tipo' => $tipo,
            'area' => $tipo === 'empleado' ? 'soporte' : null,
            'nivel_acceso' => $tipo === 'administrador' ? 'total' : null,
        ]);
    }
}
