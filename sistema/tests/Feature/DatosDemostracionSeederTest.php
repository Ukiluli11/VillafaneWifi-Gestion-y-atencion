<?php

namespace Tests\Feature;

use Database\Seeders\DatosDemostracionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DatosDemostracionSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_carga_ejemplos_completos_sin_duplicarlos(): void
    {
        $this->seed(DatosDemostracionSeeder::class);

        $this->assertDatabaseCount('cliente', 6);
        $this->assertDatabaseCount('plan', 3);
        $this->assertDatabaseCount('servicio', 6);
        $this->assertDatabaseCount('cuenta_receptora', 2);
        $this->assertDatabaseCount('cuota', 18);
        $this->assertDatabaseCount('pago', 2);
        $this->assertDatabaseCount('conversacion', 9);
        $this->assertDatabaseCount('mensaje', 32);
        $this->assertDatabaseCount('comprobante', 2);
        $this->assertDatabaseCount('ticket', 2);
        $this->assertDatabaseCount('aviso_vencimiento', 2);
        $this->assertDatabaseHas('cliente', ['nombre_razon_social' => 'Kiosco El Lapacho']);
        $this->assertDatabaseHas('cuota', ['estado' => 'pagada']);
        $this->assertDatabaseHas('conversacion', ['id_cliente' => null, 'estado_flujo' => 'esperando_nombre_registro']);
        $this->assertDatabaseHas('conversacion', ['estado' => 'escalada', 'modo_atencion' => 'usuario_interno']);
        $this->assertDatabaseHas('conversacion', [
            'numero_whatsapp' => '5493718123401',
            'estado' => 'cerrada',
        ]);
        $this->assertDatabaseHas('ticket', ['tipo' => 'tecnico', 'estado' => 'abierto']);
        $this->assertDatabaseHas('aviso_vencimiento', ['tipo' => 'vencido', 'estado' => 'enviado']);

        $this->seed(DatosDemostracionSeeder::class);

        $this->assertDatabaseCount('cliente', 6);
        $this->assertDatabaseCount('cuota', 18);
        $this->assertDatabaseCount('pago', 2);
        $this->assertDatabaseCount('conversacion', 9);
        $this->assertDatabaseCount('mensaje', 32);
        $this->assertDatabaseCount('comprobante', 2);
        $this->assertDatabaseCount('ticket', 2);
        $this->assertDatabaseCount('aviso_vencimiento', 2);
    }
}
