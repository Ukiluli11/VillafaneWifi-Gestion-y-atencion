<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class SaludSistemaTest extends TestCase
{
    public function test_devuelve_estado_disponible_si_responde_la_base_de_datos(): void
    {
        $this->getJson('/up')->assertOk()->assertExactJson(['status' => 'up']);
    }

    public function test_devuelve_error_sin_exponer_detalles_de_la_conexion(): void
    {
        config(['app.debug' => false]);
        DB::shouldReceive('select')->with('SELECT 1')->twice()
            ->andThrow(new RuntimeException('detalle interno de conexión'));

        $this->getJson('/up')->assertInternalServerError()->assertExactJson(['status' => 'down']);
        $this->get('/up')->assertInternalServerError()->assertDontSee('detalle interno de conexión');
    }
}
