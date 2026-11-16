<?php

namespace Tests\Feature;

use App\Dominio\ServicioFacturacion;
use App\Dominio\ServicioUsuarios;
use App\Enums\EstadoCuentaReceptora;
use App\Enums\MedioPago;
use App\Models\CuentaReceptora;
use App\Models\Cuota;
use App\Models\Pago;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CuentasReceptorasTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function usuario(string $tipo = 'administrador'): Usuario
    {
        return app(ServicioUsuarios::class)->crear([
            'nombre_usuario' => 'cuentas-'.$tipo,
            'contrasena' => 'clave-cuentas-123',
            'tipo' => $tipo,
            'area' => 'atencion_cliente',
        ]);
    }

    public function test_admin_puede_editar_desactivar_reactivar_y_eliminar_una_cuenta_sin_pagos(): void
    {
        $cuenta = CuentaReceptora::factory()->create(['estado' => 'activa']);
        $this->actingAs($this->usuario());
        $this->get(route('cuentas-receptoras.index'))->assertOk()->assertSee('Desactivar')->assertSee('Eliminar');
        $this->get(route('cuentas-receptoras.edit', $cuenta))->assertOk()->assertSee($cuenta->identificador);
        $this->put(route('cuentas-receptoras.update', $cuenta), [
            'nombre' => 'Cuenta de prueba editada',
            'tipo' => 'mercado_pago',
            'identificador' => 'cuenta.demo',
        ])->assertRedirect(route('cuentas-receptoras.index'));
        $this->assertDatabaseHas('cuenta_receptora', ['id_cuenta' => $cuenta->getKey(), 'identificador' => 'cuenta.demo']);

        $this->patch(route('cuentas-receptoras.estado', $cuenta), ['estado' => 'inactiva'])->assertRedirect();
        $this->assertSame(EstadoCuentaReceptora::Inactiva, $cuenta->fresh()->estado);
        $this->get(route('cuentas-receptoras.index'))->assertOk()->assertSee('Reactivar');
        $this->patch(route('cuentas-receptoras.estado', $cuenta), ['estado' => 'activa'])->assertRedirect();
        $this->assertSame(EstadoCuentaReceptora::Activa, $cuenta->fresh()->estado);

        $this->delete(route('cuentas-receptoras.destroy', $cuenta))->assertRedirect();
        $this->assertModelMissing($cuenta);
    }

    public function test_cuenta_con_pagos_se_puede_desactivar_y_conserva_su_historial(): void
    {
        $cuenta = CuentaReceptora::factory()->create(['estado' => 'activa']);
        $pago = Pago::factory()->for($cuenta, 'cuenta')->create();
        $this->actingAs($this->usuario());

        $this->get(route('cuentas-receptoras.index'))->assertOk()->assertDontSee('Eliminar');
        $this->delete(route('cuentas-receptoras.destroy', $cuenta))->assertSessionHasErrors('cuenta');
        $this->patch(route('cuentas-receptoras.estado', $cuenta), ['estado' => 'inactiva'])->assertRedirect();
        $this->assertSame($cuenta->getKey(), $pago->fresh()->id_cuenta);
        $this->assertSame(EstadoCuentaReceptora::Inactiva, $cuenta->fresh()->estado);
    }

    public function test_usuario_con_permiso_de_consulta_no_puede_modificar_cuentas(): void
    {
        $cuenta = CuentaReceptora::factory()->create();
        $this->actingAs($this->usuario('empleado'));

        $this->get(route('cuentas-receptoras.index'))->assertOk()->assertDontSee('Acciones');
        $this->get(route('cuentas-receptoras.edit', $cuenta))->assertForbidden();
        $this->put(route('cuentas-receptoras.update', $cuenta))->assertForbidden();
        $this->patch(route('cuentas-receptoras.estado', $cuenta), ['estado' => 'inactiva'])->assertForbidden();
        $this->delete(route('cuentas-receptoras.destroy', $cuenta))->assertForbidden();
    }

    public function test_un_pago_manual_rechaza_una_cuenta_desactivada_despues_de_cargarla(): void
    {
        $cuenta = CuentaReceptora::factory()->create(['estado' => 'activa']);
        $cuota = Cuota::factory()->create(['estado' => 'pendiente', 'id_pago' => null]);
        $cuenta->fresh()->desactivar();

        try {
            app(ServicioFacturacion::class)->registrarPago(
                [$cuota->getKey()], $cuenta, MedioPago::Transferencia, now()->toDateString(),
            );
            $this->fail('La cuenta desactivada no debe aceptar pagos.');
        } catch (ValidationException $error) {
            $this->assertSame(['id_cuenta'], array_keys($error->errors()));
        }

        $this->assertNull($cuota->fresh()->id_pago);
        $this->assertDatabaseCount('pago', 0);
    }
}
