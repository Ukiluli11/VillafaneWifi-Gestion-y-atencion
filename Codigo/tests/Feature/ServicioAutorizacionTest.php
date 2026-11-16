<?php

namespace Tests\Feature;

use App\Autorizacion\ServicioAutorizacion;
use App\Dominio\ServicioUsuarios;
use App\Enums\AccionSistema;
use App\Enums\AreaEmpleado;
use App\Models\Administrador;
use App\Models\Empleado;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ServicioAutorizacionTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @return array<string, array{AreaEmpleado, AccionSistema, bool}> */
    public static function permisosEmpleados(): array
    {
        return [
            'administración gestiona planes' => [AreaEmpleado::Administracion, AccionSistema::GestionarPlanes, true],
            'soporte consulta servicios' => [AreaEmpleado::Soporte, AccionSistema::ConsultarServicios, true],
            'soporte no gestiona clientes' => [AreaEmpleado::Soporte, AccionSistema::GestionarClientes, false],
            'atención consulta cuentas' => [AreaEmpleado::AtencionCliente, AccionSistema::ConsultarCuentas, true],
            'atención no gestiona pagos' => [AreaEmpleado::AtencionCliente, AccionSistema::GestionarPagos, false],
        ];
    }

    #[DataProvider('permisosEmpleados')]
    public function test_empleado_recibe_permisos_segun_area(
        AreaEmpleado $area,
        AccionSistema $accion,
        bool $esperado,
    ): void {
        $usuario = app(ServicioUsuarios::class)->crear([
            'nombre_usuario' => 'empleado-'.$area->value,
            'contrasena' => 'clave-segura',
            'tipo' => 'empleado',
            'area' => $area->value,
        ]);

        $this->assertSame($esperado, app(ServicioAutorizacion::class)->puede($usuario, $accion));
    }

    public function test_administrador_respeta_banderas_especificas(): void
    {
        $usuario = app(ServicioUsuarios::class)->crear([
            'nombre_usuario' => 'administrador-limitado',
            'contrasena' => 'clave-segura',
            'tipo' => 'administrador',
            'puede_gestionar_usuarios' => false,
            'puede_configurar_planes' => true,
        ]);

        $servicio = app(ServicioAutorizacion::class);

        $this->assertFalse($servicio->puede($usuario, AccionSistema::GestionarUsuarios));
        $this->assertTrue($servicio->puede($usuario, AccionSistema::GestionarPlanes));
        $this->assertTrue($servicio->puede($usuario, AccionSistema::GestionarClientes));
    }

    public function test_el_panel_actualiza_perfil_y_aplica_el_nivel_restringido(): void
    {
        $servicio = app(ServicioUsuarios::class);
        $admin = $servicio->crear(['nombre_usuario' => 'admin-general', 'contrasena' => 'clave-segura', 'tipo' => 'administrador']);
        $usuario = $servicio->crear(['nombre_usuario' => 'tecnico', 'contrasena' => 'clave-segura', 'tipo' => 'empleado', 'area' => 'soporte']);
        $datos = ['nombre_usuario' => 'administrador-secundario', 'tipo' => 'administrador', 'nivel_acceso' => 'restringido'];

        $this->actingAs($admin)->get(route('usuarios.index'))->assertOk();
        $this->get(route('usuarios.edit', $usuario))->assertOk();
        $this->put(route('usuarios.update', $usuario), $datos)->assertRedirect(route('usuarios.index'));
        $this->put(route('usuarios.update', $admin), ['nombre_usuario' => $admin->nombre_usuario] + $datos)
            ->assertSessionHasErrors('tipo');

        $usuario->refresh();
        $this->assertSame('administrador-secundario', $usuario->nombre_usuario);
        $this->assertNull($usuario->empleado);
        $this->assertSame('restringido', $usuario->administrador->nivel_acceso->value);
        $this->flushSession();
        $this->actingAs($usuario)->get(route('usuarios.index'))->assertForbidden();
        $this->get(route('planes.index'))->assertOk();
    }

    public function test_rechaza_usuario_inactivo_o_con_dos_subtipos(): void
    {
        $inactivo = app(ServicioUsuarios::class)->crear([
            'nombre_usuario' => 'inactivo',
            'contrasena' => 'clave-segura',
            'tipo' => 'administrador',
        ]);
        $inactivo->desactivar();

        $doble = app(ServicioUsuarios::class)->crear([
            'nombre_usuario' => 'doble-subtipo',
            'contrasena' => 'clave-segura',
            'tipo' => 'administrador',
        ]);
        Empleado::create([
            'id_empleado' => $doble->id_usuario,
            'area' => AreaEmpleado::Soporte,
        ]);

        $servicio = app(ServicioAutorizacion::class);

        $this->assertFalse($servicio->puede($inactivo->fresh(), AccionSistema::ConsultarClientes));
        $this->assertFalse($servicio->puede($doble->fresh(), AccionSistema::ConsultarClientes));
        $this->assertSame(1, Administrador::whereKey($doble->id_usuario)->count());
    }
}
