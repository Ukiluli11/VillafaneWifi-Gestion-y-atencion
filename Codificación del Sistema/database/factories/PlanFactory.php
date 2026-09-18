<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => 'Plan '.fake()->unique()->numberBetween(10, 999),
            'velocidad' => fake()->randomElement(['10/5 Mbps', '30/10 Mbps', '50/20 Mbps']),
            'precio_vigente' => fake()->randomFloat(2, 5000, 50000),
            'estado' => 'activo',
        ];
    }
}
