<?php

namespace Tests\Feature;

use App\Dominio\ServicioUsuarios;
use App\Models\Cliente;
use App\Models\Comprobante;
use App\Models\Conversacion;
use App\Models\Mensaje;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Comprueba la interfaz local que reemplaza temporalmente a Meta. */
class SimuladorWhatsappWebTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.whatsapp.modo_simulacion', true);
        Http::preventStrayRequests();
    }

    public function test_administrador_ve_y_utiliza_el_simulador_sin_contactar_meta(): void
    {
        $usuario = $this->crearAdministrador();
        Cliente::factory()->create([
            'nombre_razon_social' => 'Cliente del simulador',
            'telefono_whatsapp' => '5493704555011',
        ]);

        $this->actingAs($usuario)
            ->get(route('simulador-whatsapp.create'))
            ->assertOk()
            ->assertSee('Modo simulación activo')
            ->assertSee('Cliente del simulador');

        $respuesta = $this->actingAs($usuario)->post(route('simulador-whatsapp.store'), [
            'numero_whatsapp' => '5493704555011',
            'tipo' => 'text',
            'contenido' => 'Hola',
        ]);

        $conversacion = Conversacion::query()->firstOrFail();
        $respuesta->assertRedirect(route('conversaciones.show', $conversacion));
        $this->assertSame(2, Mensaje::count());
        $this->assertDatabaseHas('mensaje', ['contenido' => 'Hola', 'tipo_emisor' => 'cliente']);
        $this->assertStringStartsWith(
            'wamid.simulado.salida.',
            Mensaje::query()->where('tipo_emisor', 'bot')->firstOrFail()->id_mensaje_externo,
        );
        Http::assertNothingSent();
    }

    public function test_permite_simular_el_envio_de_un_comprobante(): void
    {
        $usuario = $this->crearAdministrador();
        Cliente::factory()->create(['telefono_whatsapp' => '5493704555012']);
        $this->actingAs($usuario);

        $this->post(route('simulador-whatsapp.store'), [
            'numero_whatsapp' => '5493704555012', 'tipo' => 'text', 'contenido' => 'Hola',
        ])->assertRedirect();
        $this->post(route('simulador-whatsapp.store'), [
            'numero_whatsapp' => '5493704555012', 'tipo' => 'text', 'contenido' => '2',
        ])->assertRedirect();
        $this->post(route('simulador-whatsapp.store'), [
            'numero_whatsapp' => '5493704555012', 'tipo' => 'image', 'contenido' => 'Transferencia de prueba',
        ])->assertRedirect();

        $this->assertSame(1, Comprobante::count());
        $this->assertDatabaseHas('comprobante', ['estado_validacion' => 'pendiente']);
        $this->assertDatabaseHas('mensaje', [
            'tipo' => 'imagen',
            'contenido' => 'Transferencia de prueba',
            'tipo_emisor' => 'cliente',
        ]);
        $this->assertSame('cerrada', Conversacion::query()->firstOrFail()->estado->value);
        Http::assertNothingSent();
    }

    public function test_oculta_el_simulador_cuando_se_activa_la_integracion_real(): void
    {
        config()->set('services.whatsapp.modo_simulacion', false);
        $usuario = $this->crearAdministrador();

        $this->actingAs($usuario)
            ->get(route('simulador-whatsapp.create'))
            ->assertNotFound();
    }

    private function crearAdministrador(): Usuario
    {
        return app(ServicioUsuarios::class)->crear([
            'nombre_usuario' => 'administrador-simulador',
            'contrasena' => 'clave-segura',
            'tipo' => 'administrador',
            'nivel_acceso' => 'total',
        ]);
    }
}
