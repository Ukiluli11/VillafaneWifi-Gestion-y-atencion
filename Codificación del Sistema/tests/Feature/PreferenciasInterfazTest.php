<?php

namespace Tests\Feature;

use App\Dominio\ServicioUsuarios;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/**
 * Verifica que el panel entregue los controles de personalización visual.
 */
class PreferenciasInterfazTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** Comprueba que un usuario autenticado pueda alternar tema y barra lateral. */
    public function test_panel_incluye_controles_de_tema_y_barra_lateral(): void
    {
        $usuario = app(ServicioUsuarios::class)->crear([
            'nombre_usuario' => 'administrador-interfaz',
            'contrasena' => 'clave-segura',
            'tipo' => 'administrador',
            'nivel_acceso' => 'total',
        ]);

        $this->actingAs($usuario)->get('/')
            ->assertOk()
            ->assertSee('data-alternar-tema', false)
            ->assertSee('data-alternar-lateral', false)
            ->assertSee('barra-lateral-colapsada', false);
    }
}
