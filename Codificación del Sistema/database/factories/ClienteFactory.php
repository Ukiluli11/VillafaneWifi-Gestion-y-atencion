<?php

namespace Database\Factories;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cliente>
 */
class ClienteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tipo_documento' => 'DNI',
            'numero_documento' => fake()->unique()->numerify('########'),
            'nombre_razon_social' => fake()->name(),
            'tipo_cliente' => 'particular',
            'calle_contacto' => fake()->streetName(),
            'numero_contacto' => fake()->buildingNumber(),
            'localidad_contacto' => fake()->city(),
            'telefono_whatsapp' => '549'.fake()->unique()->numerify('##########'),
            'estado' => 'activo',
        ];
    }
}
