<?php

namespace Database\Factories;

use App\Models\CuentaReceptora;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CuentaReceptora>
 */
class CuentaReceptoraFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->company(),
            'tipo' => fake()->randomElement(['banco', 'mercado_pago', 'uala', 'otro']),
            'identificador' => fake()->unique()->bothify('cuenta-####-????'),
            'estado' => 'activa',
        ];
    }
}
