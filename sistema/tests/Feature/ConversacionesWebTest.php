<?php

namespace Tests\Feature;

use App\Dominio\ServicioUsuarios;
use App\Models\Cliente;
use App\Models\Conversacion;
use App\Models\Mensaje;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/**
 * Comprueba la consulta visual del historial de WhatsApp.
 */
class ConversacionesWebTest extends TestCase
{
    use LazilyRefreshDatabase;

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

    public function test_empleado_de_soporte_puede_consultar_conversaciones(): void
    {
        $usuario = $this->crearUsuario('empleado');

        $this->actingAs($usuario)->get('/conversaciones')
            ->assertOk()
            ->assertSee('Atención por WhatsApp');
    }

    private function crearUsuario(string $tipo): Usuario
    {
        return app(ServicioUsuarios::class)->crear([
            'nombre_usuario' => 'usuario-'.$tipo,
            'contrasena' => 'clave-segura',
            'tipo' => $tipo,
            'area' => $tipo === 'empleado' ? 'soporte' : null,
            'nivel_acceso' => $tipo === 'administrador' ? 'total' : null,
        ]);
    }
}
