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

        $respuesta->assertRedirect(route('inicio'))->assertSessionHas('password_hash_web');
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

    public function test_administrador_restablece_la_clave_de_otra_cuenta_y_la_sesion_anterior_caduca(): void
    {
        $servicio = app(ServicioUsuarios::class);
        $admin = $servicio->crear([
            'nombre_usuario' => 'admin-reset', 'contrasena' => 'clave-admin',
            'tipo' => 'administrador', 'nivel_acceso' => 'total',
        ]);
        $usuario = $servicio->crear([
            'nombre_usuario' => 'usuario-reset', 'contrasena' => 'clave-original',
            'tipo' => 'empleado', 'area' => 'soporte',
        ]);
        $hashAnterior = $usuario->getAuthPassword();
        $edicion = route('usuarios.edit', $usuario);
        $restablecer = route('usuarios.contrasena.restablecer', $usuario);

        $this->actingAs($admin)->get($edicion)->assertOk()->assertSee('Restablecer contraseña');
        $this->get(route('usuarios.edit', $admin))->assertOk()->assertDontSee('Restablecer contraseña');
        $this->from($edicion)->put($restablecer, [
            'contrasena_nueva' => 'clave-renovada',
            'contrasena_nueva_confirmation' => 'clave-distinta',
        ])->assertSessionHasErrors('contrasena_nueva')->assertSessionMissing('_old_input.contrasena_nueva');
        $this->assertSame($hashAnterior, $usuario->fresh()->getAuthPassword());

        $datos = ['contrasena_nueva' => 'clave-renovada', 'contrasena_nueva_confirmation' => 'clave-renovada'];
        $this->put(route('usuarios.contrasena.restablecer', $admin), $datos)->assertForbidden();
        $this->put($restablecer, $datos)->assertRedirect($edicion);
        $this->assertAuthenticatedAs($admin);

        $this->actingAs($usuario->fresh())->withSession(['password_hash_web' => $hashAnterior])
            ->get(route('inicio'))->assertRedirect(route('sesion.crear'));
        $this->assertGuest();
        $this->post(route('sesion.guardar'), [
            'nombre_usuario' => $usuario->nombre_usuario, 'contrasena' => 'clave-original',
        ])->assertSessionHasErrors('nombre_usuario');
        $this->post(route('sesion.guardar'), [
            'nombre_usuario' => $usuario->nombre_usuario, 'contrasena' => 'clave-renovada',
        ])->assertRedirect(route('inicio'));
        $this->assertAuthenticatedAs($usuario);
    }

    public function test_restablecer_claves_requiere_administracion_total_y_permiso_de_gestion(): void
    {
        $servicio = app(ServicioUsuarios::class);
        $usuario = $servicio->crear([
            'nombre_usuario' => 'cuenta-protegida', 'contrasena' => 'clave-original',
            'tipo' => 'empleado', 'area' => 'soporte',
        ]);
        $perfiles = [
            'admin-restringido' => ['tipo' => 'administrador', 'nivel_acceso' => 'restringido'],
            'admin-sin-permiso' => ['tipo' => 'administrador', 'nivel_acceso' => 'total', 'puede_gestionar_usuarios' => false],
            'soporte' => ['tipo' => 'empleado', 'area' => 'soporte'],
        ];

        foreach ($perfiles as $nombre => $perfil) {
            $editor = $servicio->crear($perfil + ['nombre_usuario' => $nombre, 'contrasena' => 'clave-editor']);
            $this->actingAs($editor)->withSession(['password_hash_web' => $editor->getAuthPassword()])
                ->put(route('usuarios.contrasena.restablecer', $usuario), [
                    'contrasena_nueva' => 'clave-renovada',
                    'contrasena_nueva_confirmation' => 'clave-renovada',
                ])->assertForbidden();
        }

        $this->assertTrue($usuario->fresh()->autenticar('clave-original'));
    }

    public function test_desactivar_usuario_cierra_su_sesion_al_volver_al_panel(): void
    {
        $usuario = app(ServicioUsuarios::class)->crear([
            'nombre_usuario' => 'operador-desactivado', 'contrasena' => 'clave-segura',
            'tipo' => 'empleado', 'area' => 'soporte',
        ]);
        $this->actingAs($usuario)->get(route('inicio'))->assertOk();
        $usuario->desactivar();

        $this->get(route('inicio'))->assertRedirect(route('sesion.crear'));
        $this->assertGuest();
        $this->get(route('cuenta.contrasena'))->assertRedirect(route('sesion.crear'));
        $this->post('/iniciar-sesion', [
            'nombre_usuario' => $usuario->nombre_usuario, 'contrasena' => 'clave-segura',
        ])->assertSessionHasErrors('nombre_usuario');
        $this->assertGuest();
    }
}
