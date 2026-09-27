<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\Plan;
use App\Models\Servicio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Servicio>
 */
class ServicioFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_plan' => Plan::factory(),
            'id_cliente' => Cliente::factory(),
            'calle_instalacion' => fake()->streetName(),
            'numero_instalacion' => fake()->buildingNumber(),
            'localidad_instalacion' => fake()->city(),
            'dia_vencimiento' => fake()->numberBetween(1, 28),
            'proximo_vencimiento' => now()->addMonth()->toDateString(),
            'fecha_alta' => now()->subMonth()->toDateString(),
            'ipv4' => fake()->ipv4(),
            'mac' => 'AA:BB:CC:DD:EE:FF',
            'estado' => 'activo',
        ];
    }
}
