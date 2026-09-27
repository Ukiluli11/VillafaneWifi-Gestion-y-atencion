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
        $this->assertDatabaseHas('cliente', ['nombre_razon_social' => 'Kiosco El Lapacho']);
        $this->assertDatabaseHas('cuota', ['estado' => 'pagada']);

        $this->seed(DatosDemostracionSeeder::class);

        $this->assertDatabaseCount('cliente', 6);
        $this->assertDatabaseCount('cuota', 18);
        $this->assertDatabaseCount('pago', 2);
    }
}
