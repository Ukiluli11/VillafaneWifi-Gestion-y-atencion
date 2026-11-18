<?php

namespace Tests\Feature;

use App\Dominio\ServicioClientes;
use App\Dominio\ServicioUsuarios;
use App\Models\Cliente;
use App\Models\Plan;
use App\Models\Servicio;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ClientesTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_administrador_registra_cliente_y_normaliza_contactos(): void
    {
        $usuario = app(ServicioUsuarios::class)->crear([
            'nombre_usuario' => 'admin-clientes', 'contrasena' => 'clave-segura',
            'tipo' => 'administrador', 'nivel_acceso' => 'total',
        ]);

        $respuesta = $this->actingAs($usuario)->post('/clientes', [
            'tipo_documento' => 'DNI', 'numero_documento' => '35.123.456',
            'nombre_razon_social' => 'María Gómez', 'tipo_cliente' => 'particular',
            'calle_contacto' => 'Belgrano', 'numero_contacto' => '120',
            'localidad_contacto' => 'Villa Dos Trece', 'telefono_whatsapp' => '+54 9 3718 123456',
        ]);

        $cliente = Cliente::sole();
        $respuesta->assertRedirect(route('clientes.show', $cliente));
        $this->assertSame('35123456', $cliente->numero_documento);
        $this->assertSame('5493718123456', $cliente->telefono_whatsapp);
        $this->assertFalse($cliente->recordatorios_whatsapp);
    }

    public function test_alta_rechaza_documento_telefono_y_altura_negativos(): void
    {
        $usuario = app(ServicioUsuarios::class)->crear([
            'nombre_usuario' => 'admin-formato', 'contrasena' => 'clave-segura', 'tipo' => 'administrador',
        ]);

        $this->actingAs($usuario)->post('/clientes', [
            'tipo_documento' => 'DNI', 'numero_documento' => '-35.123.456',
            'nombre_razon_social' => 'Cliente de prueba', 'tipo_cliente' => 'particular',
            'numero_contacto' => '-120', 'telefono_whatsapp' => '-54 9 3718 123456',
        ])->assertSessionHasErrors(['numero_documento', 'telefono_whatsapp', 'numero_contacto']);
        $this->assertDatabaseCount('cliente', 0);
    }

    public function test_gestionar_recordatorios_desde_la_ficha_y_revalidar_si_cambia_el_telefono(): void
    {
        $admin = app(ServicioUsuarios::class)->crear([
            'nombre_usuario' => 'admin-recordatorios', 'contrasena' => 'clave-segura',
            'tipo' => 'administrador',
        ]);
        $cliente = Cliente::factory()->create(['telefono_whatsapp' => '5493704000000']);
        $otro = Cliente::factory()->create();
        $this->actingAs($admin)->get(route('clientes.show', $cliente))
            ->assertOk()->assertSee('Habilitar recordatorios');

        $this->patch(route('clientes.recordatorios', $cliente), ['habilitados' => '1'])
            ->assertRedirect(route('clientes.show', $cliente));
        $this->assertTrue($cliente->fresh()->recordatorios_whatsapp);
        $this->assertFalse($otro->fresh()->recordatorios_whatsapp);
        $this->get(route('clientes.show', $cliente))->assertOk()->assertSee('Desactivar recordatorios');

        $this->patch(route('clientes.recordatorios', $cliente), ['habilitados' => '0'])
            ->assertRedirect(route('clientes.show', $cliente));
        $this->assertFalse($cliente->fresh()->recordatorios_whatsapp);

        $this->patch(route('clientes.recordatorios', $cliente), ['habilitados' => '1'])->assertRedirect();
        app(ServicioClientes::class)->actualizar($cliente, ['telefono_whatsapp' => '5493704000001']);
        $this->assertFalse($cliente->fresh()->recordatorios_whatsapp);
    }

    public function test_recordatorios_requieren_permiso_de_edicion_y_un_numero_registrado(): void
    {
        $soporte = app(ServicioUsuarios::class)->crear([
            'nombre_usuario' => 'soporte-recordatorios', 'contrasena' => 'clave-segura',
            'tipo' => 'empleado', 'area' => 'soporte',
        ]);
        $admin = app(ServicioUsuarios::class)->crear([
            'nombre_usuario' => 'admin-recordatorios', 'contrasena' => 'clave-segura', 'tipo' => 'administrador',
        ]);
        $cliente = Cliente::factory()->create(['telefono_whatsapp' => null]);
        $ruta = route('clientes.recordatorios', $cliente);
        $this->actingAs($soporte)->patch($ruta, ['habilitados' => '1'])->assertForbidden();
        $this->flushSession();
        $this->actingAs($admin)->patch($ruta, ['habilitados' => '1'])->assertSessionHasErrors('recordatorios_whatsapp');
        $this->assertFalse($cliente->fresh()->recordatorios_whatsapp);
    }

    public function test_alta_conjunta_crea_primera_cuota_y_revierte_si_el_plan_esta_inactivo(): void
    {
        $this->travelTo(Carbon::parse('2026-11-15 13:00:00'));
        $admin = app(ServicioUsuarios::class)->crear([
            'nombre_usuario' => 'admin-alta', 'contrasena' => 'clave-segura', 'tipo' => 'administrador',
        ]);
        $plan = Plan::factory()->create(['estado' => 'inactivo', 'precio_vigente' => '12500.50']);
        $datos = [
            'tipo_documento' => 'DNI', 'numero_documento' => '35123456',
            'nombre_razon_social' => 'Cliente nuevo', 'tipo_cliente' => 'particular',
            'servicio' => ['id_plan' => $plan->getKey(), 'dia_vencimiento' => 10, 'fecha_alta' => '2026-11-15',
                'calle_instalacion' => 'Belgrano', 'localidad_instalacion' => 'Villafañe', 'mac' => 'aa-bb-cc-dd-ee-ff'],
        ];

        $this->actingAs($admin)->get(route('clientes.create'))->assertOk()->assertSee('Primer servicio');
        $this->post(route('clientes.alta-completa'), array_diff_key($datos, ['servicio' => true]))
            ->assertSessionHasErrors('servicio');
        $this->post(route('clientes.alta-completa'), $datos)->assertSessionHasErrors('id_plan');
        $this->assertDatabaseCount('cliente', 0);

        $plan->update(['estado' => 'activo']);
        $this->post(route('clientes.alta-completa'), $datos)->assertRedirect();
        $servicio = Servicio::sole();
        $cuota = $servicio->cuotas()->sole();
        $this->assertSame('AA:BB:CC:DD:EE:FF', $servicio->mac);
        $this->assertSame('2026-11-15', $cuota->fecha_vencimiento->toDateString());
        $this->assertSame('12500.50', $cuota->monto);
    }

    public function test_soporte_puede_consultar_pero_no_registrar_clientes(): void
    {
        $usuario = app(ServicioUsuarios::class)->crear([
            'nombre_usuario' => 'tecnico', 'contrasena' => 'clave-segura',
            'tipo' => 'empleado', 'area' => 'soporte',
        ]);

        $this->actingAs($usuario)->get('/clientes')->assertOk();
        $this->actingAs($usuario)->post('/clientes', [])->assertForbidden();
    }

    public function test_busqueda_encuentra_por_whatsapp(): void
    {
        $usuario = app(ServicioUsuarios::class)->crear([
            'nombre_usuario' => 'administracion', 'contrasena' => 'clave-segura',
            'tipo' => 'empleado', 'area' => 'administracion',
        ]);
        Cliente::factory()->create(['nombre_razon_social' => 'Cliente visible', 'telefono_whatsapp' => '5493718998877']);
        Cliente::factory()->create(['nombre_razon_social' => 'Cliente oculto', 'telefono_whatsapp' => '5493718112233']);

        $this->actingAs($usuario)->get('/clientes?buscar=998877')
            ->assertSee('Cliente visible')
            ->assertDontSee('Cliente oculto');
    }
}
