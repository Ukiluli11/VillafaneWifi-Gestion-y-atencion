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

    public function test_empleado_cambia_su_clave_con_la_actual_y_conserva_su_sesion(): void
    {
        $usuario = app(ServicioUsuarios::class)->crear([
            'nombre_usuario' => 'usuario-clave', 'contrasena' => 'clave-original',
            'tipo' => 'empleado', 'area' => 'soporte',
        ]);
        $datos = ['contrasena_nueva' => 'clave-renovada', 'contrasena_nueva_confirmation' => 'clave-renovada'];

        $this->actingAs($usuario)->get(route('cuenta.contrasena'))->assertOk();
        $this->put(route('cuenta.contrasena.actualizar'), $datos + ['contrasena_actual' => 'incorrecta'])
            ->assertSessionHasErrors('contrasena_actual')->assertSessionMissing('_old_input.contrasena_nueva');
        $this->put(route('cuenta.contrasena.actualizar'), $datos + ['contrasena_actual' => 'clave-original'])
            ->assertRedirect(route('cuenta.contrasena'));

        $this->get(route('cuenta.contrasena'))->assertOk();
        $this->assertTrue($usuario->fresh()->autenticar('clave-renovada'));
        $this->assertFalse($usuario->fresh()->autenticar('clave-original'));
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
