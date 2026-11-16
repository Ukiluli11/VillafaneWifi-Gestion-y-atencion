<?php

namespace Tests\Feature;

use App\Dominio\ServicioContrataciones;
use App\Dominio\ServicioUsuarios;
use App\Models\Cliente;
use App\Models\Conversacion;
use App\Models\Plan;
use App\Models\Servicio;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class FiltrosPanelTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(app(ServicioUsuarios::class)->crear([
            'nombre_usuario' => 'admin-filtros',
            'contrasena' => 'clave-prueba-123',
            'tipo' => 'administrador',
        ]));
    }

    public function test_puede_combinar_busqueda_estado_y_tipo_de_cliente(): void
    {
        Cliente::factory()->create(['nombre_razon_social' => 'Prueba Comercio Activo', 'estado' => 'activo', 'tipo_cliente' => 'comercio']);
        Cliente::factory()->create(['nombre_razon_social' => 'Prueba Comercio Suspendido', 'estado' => 'suspendido', 'tipo_cliente' => 'comercio']);
        Cliente::factory()->create(['nombre_razon_social' => 'Prueba Particular', 'estado' => 'activo', 'tipo_cliente' => 'particular']);

        $this->get(route('clientes.index', ['buscar' => 'Prueba', 'estado' => 'activo', 'tipo' => 'comercio']))
            ->assertOk()->assertSee('Prueba Comercio Activo')->assertDontSee('Prueba Comercio Suspendido')->assertDontSee('Prueba Particular');
    }

    public function test_puede_buscar_servicios_y_filtrarlos_por_plan_y_estado(): void
    {
        $plan = Plan::factory()->create();
        $servicio = Servicio::factory()->for($plan, 'plan')->create(['localidad_instalacion' => 'Localidad buscada', 'estado' => 'activo']);
        Servicio::factory()->for($plan, 'plan')->create(['localidad_instalacion' => 'Localidad buscada', 'estado' => 'suspendido']);
        Servicio::factory()->create(['localidad_instalacion' => 'Localidad buscada', 'estado' => 'activo']);

        $this->get(route('servicios.index', ['buscar' => 'Localidad buscada', 'estado' => 'activo', 'plan' => $plan->getKey()]))
            ->assertOk()->assertViewHas('servicios', fn ($resultado): bool => $resultado->total() === 1 && $resultado->first()->is($servicio));

        $this->get(route('servicios.index', ['buscar' => '#'.$servicio->getKey()]))
            ->assertOk()->assertViewHas('servicios', fn ($resultado): bool => $resultado->total() === 1 && $resultado->first()->is($servicio));
    }

    public function test_conversaciones_filtra_por_nombre_estado_modo_y_asignacion(): void
    {
        $cliente = Cliente::factory()->create(['nombre_razon_social' => 'Nombre buscado']);
        $conversacion = Conversacion::factory()->for($cliente, 'cliente')->create([
            'estado' => 'en_atencion',
            'modo_atencion' => 'humano',
            'id_usuario_atencion' => auth()->id(),
        ]);
        Conversacion::factory()->for($cliente, 'cliente')->create(['estado' => 'abierta', 'modo_atencion' => 'bot', 'id_usuario_atencion' => null]);
        Conversacion::factory()->create(['estado' => 'en_atencion', 'modo_atencion' => 'humano', 'id_usuario_atencion' => auth()->id()]);

        $this->get(route('conversaciones.index', [
            'buscar' => 'Nombre buscado',
            'estado' => 'en_atencion',
            'modo' => 'humano',
            'asignacion' => 'propias',
        ]))->assertOk()->assertViewHas('conversaciones', fn ($resultado): bool => $resultado->total() === 1 && $resultado->first()->is($conversacion));
    }

    public function test_planes_filtra_por_velocidad_estado_y_uso_y_conserva_los_filtros_al_paginar(): void
    {
        Plan::factory()->count(21)->create(['velocidad' => '300 Mbps', 'estado' => 'activo']);
        Plan::factory()->create(['velocidad' => '300 Mbps', 'estado' => 'inactivo']);
        Plan::factory()->create(['velocidad' => '600 Mbps', 'estado' => 'activo']);
        $enUso = Plan::factory()->create(['velocidad' => '300 Mbps', 'estado' => 'activo']);
        Servicio::factory()->for($enUso, 'plan')->create();

        $this->get(route('planes.index', ['buscar' => '300 Mbps', 'estado' => 'activo', 'uso' => 'sin_servicios', 'page' => 2]))
            ->assertOk()
            ->assertViewHas('planes', fn ($resultado): bool => $resultado->total() === 21 && $resultado->count() === 1
                && str_contains($resultado->previousPageUrl(), 'uso=sin_servicios'));
    }

    public function test_planes_permite_editar_desactivar_reactivar_y_eliminar_sin_servicios(): void
    {
        $plan = Plan::factory()->create(['estado' => 'activo']);
        $this->get(route('planes.index'))->assertOk()->assertSee('Desactivar')->assertSee('Eliminar');
        $this->get(route('planes.edit', $plan))->assertOk();
        $this->put(route('planes.update', $plan), [
            'nombre' => 'Plan actualizado', 'velocidad' => '300 Mbps', 'precio_vigente' => '100.00', 'estado' => 'activo',
        ])->assertRedirect(route('planes.index'));
        $this->assertSame('Plan actualizado', $plan->fresh()->nombre);
        $this->delete(route('planes.destroy', $plan))->assertRedirect();
        $this->assertSame('inactivo', $plan->fresh()->estado->value);
        $this->post(route('planes.reactivar', $plan))->assertRedirect();
        $this->assertSame('activo', $plan->fresh()->estado->value);
        $this->delete(route('planes.eliminar', $plan))->assertRedirect();
        $this->assertModelMissing($plan);
    }

    public function test_un_plan_con_servicios_puede_desactivarse_y_conserva_los_contratos(): void
    {
        $plan = Plan::factory()->create(['estado' => 'activo']);
        $servicio = Servicio::factory()->for($plan, 'plan')->create();
        $this->get(route('planes.index'))->assertOk()->assertDontSee('Eliminar');
        $this->delete(route('planes.eliminar', $plan))->assertSessionHasErrors('plan');
        $this->delete(route('planes.destroy', $plan))->assertRedirect();
        $this->assertSame('inactivo', $plan->fresh()->estado->value);
        $this->assertSame($plan->getKey(), $servicio->fresh()->id_plan);
        $this->assertSame('192.0.2.10', app(ServicioContrataciones::class)->actualizar($servicio, [
            'id_plan' => $plan->getKey(), 'ipv4' => '192.0.2.10',
        ])->ipv4);
    }
}
