<?php

namespace Database\Factories;

use App\Models\Conversacion;
use App\Models\Mensaje;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mensaje>
 */
class MensajeFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'id_conversacion' => Conversacion::factory(),
            'id_usuario' => null,
            'id_mensaje_externo' => null,
            'fecha_hora' => now(),
            'tipo' => 'texto',
            'contenido' => fake()->sentence(),
            'archivo_adjunto' => null,
            'tipo_emisor' => 'cliente',
            'estado_envio' => 'recibido',
        ];
    }
}
