<?php

namespace Database\Factories;

use App\Models\Cuota;
use App\Models\Servicio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cuota>
 */
class CuotaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_servicio' => Servicio::factory(),
            'id_pago' => null,
            'periodo' => now()->format('Y-m'),
            'monto' => '10000.00',
            'fecha_emision' => now()->startOfMonth()->toDateString(),
            'fecha_vencimiento' => now()->startOfMonth()->addDays(9)->toDateString(),
            'estado' => 'pendiente',
        ];
    }
}
