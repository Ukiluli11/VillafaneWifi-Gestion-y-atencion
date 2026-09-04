<?php

namespace Tests\Feature;

use App\Dominio\ServicioUsuarios;
use App\Models\Cliente;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ApiV1Test extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_api_devuelve_401_sin_credenciales(): void
    {
        $this->getJson('/api/v1/clientes')->assertUnauthorized();
    }

    public function test_api_permite_crear_y_consultar_cliente_con_permiso(): void
    {
        app(ServicioUsuarios::class)->crear([
            'nombre_usuario' => 'api-admin', 'contrasena' => 'clave-segura',
            'tipo' => 'administrador', 'nivel_acceso' => 'total',
        ]);

        $respuesta = $this->withBasicAuth('api-admin', 'clave-segura')->postJson('/api/v1/clientes', [
            'tipo_documento' => 'CUIT', 'numero_documento' => '30-71234567-8',
            'nombre_razon_social' => 'Comercio API', 'tipo_cliente' => 'comercio',
            'localidad_contacto' => 'Villafañe', 'telefono_whatsapp' => '5493718123456',
        ]);

        $respuesta->assertCreated()->assertJsonPath('data.nombre_razon_social', 'Comercio API');
        $this->assertSame(1, Cliente::count());

        $this->withBasicAuth('api-admin', 'clave-segura')->getJson('/api/v1/clientes')
            ->assertOk()->assertJsonPath('data.0.numero_documento', '30712345678');
    }

    public function test_api_devuelve_403_cuando_el_area_no_puede_modificar(): void
    {
        app(ServicioUsuarios::class)->crear([
            'nombre_usuario' => 'api-soporte', 'contrasena' => 'clave-segura',
            'tipo' => 'empleado', 'area' => 'soporte',
        ]);

        $this->withBasicAuth('api-soporte', 'clave-segura')->postJson('/api/v1/clientes', [
            'tipo_documento' => 'DNI', 'numero_documento' => '12345678',
            'nombre_razon_social' => 'Sin permiso', 'tipo_cliente' => 'particular',
        ])->assertForbidden();
        $this->assertSame(0, Cliente::count());
    }
}
