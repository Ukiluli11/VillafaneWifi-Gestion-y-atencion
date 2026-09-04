<?php

namespace Tests\Feature;

use App\Dominio\ServicioUsuarios;
use App\Models\Cliente;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
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
