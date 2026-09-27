<?php

namespace Database\Factories;

use App\Models\Administrador;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Administrador>
 */
class AdministradorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_administrador' => Usuario::factory(),
            'nivel_acceso' => 'total',
            'puede_gestionar_usuarios' => true,
            'puede_configurar_planes' => true,
        ];
    }
}
