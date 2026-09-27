<?php

namespace Database\Factories;

use App\Models\Empleado;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Empleado>
 */
class EmpleadoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_empleado' => Usuario::factory(),
            'area' => fake()->randomElement(['administracion', 'soporte', 'atencion_cliente']),
            'cargo' => fake()->jobTitle(),
            'turno' => fake()->randomElement(['mañana', 'tarde', 'noche']),
            'fecha_ingreso' => fake()->date(),
        ];
    }
}
