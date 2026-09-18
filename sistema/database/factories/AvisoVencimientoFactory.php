<?php

namespace Database\Factories;

use App\Models\AvisoVencimiento;
use App\Models\Cuota;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AvisoVencimiento>
 */
class AvisoVencimientoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_cuota' => Cuota::factory(),
            'id_conversacion' => null,
            'id_mensaje' => null,
            'tipo' => 'proximo',
            'estado' => 'pendiente',
            'fecha_hora_ultimo_intento' => null,
            'fecha_hora_envio' => null,
            'detalle_error' => null,
        ];
    }
}
