<?php

namespace Tests\Feature;

use App\Dominio\ServicioUsuarios;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AutenticacionTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_usuario_activo_inicia_y_cierra_sesion(): void
    {
        app(ServicioUsuarios::class)->crear([
            'nombre_usuario' => 'ulises', 'contrasena' => 'clave-segura',
            'tipo' => 'administrador', 'nivel_acceso' => 'total',
        ]);

        $respuesta = $this->post('/iniciar-sesion', [
            'nombre_usuario' => 'ulises', 'contrasena' => 'clave-segura',
        ]);

        $respuesta->assertRedirect(route('inicio'));
        $this->assertAuthenticated();

        $this->post('/cerrar-sesion')->assertRedirect(route('sesion.crear'));
        $this->assertGuest();
    }

    public function test_credenciales_incorrectas_no_inician_sesion(): void
    {
        app(ServicioUsuarios::class)->crear([
            'nombre_usuario' => 'agustin', 'contrasena' => 'clave-correcta',
            'tipo' => 'empleado', 'area' => 'soporte',
        ]);

        $this->post('/iniciar-sesion', [
            'nombre_usuario' => 'agustin', 'contrasena' => 'otra-clave',
        ])->assertSessionHasErrors('nombre_usuario');
        $this->assertGuest();
    }
}
