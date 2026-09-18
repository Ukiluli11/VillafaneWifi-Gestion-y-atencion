<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\Conversacion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conversacion>
 */
class ConversacionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'id_cliente' => Cliente::factory(),
            'id_usuario_atencion' => null,
            'numero_whatsapp' => '549'.fake()->unique()->numerify('##########'),
            'fecha_hora_inicio' => now(),
            'fecha_hora_cierre' => null,
            'estado' => 'abierta',
            'modo_atencion' => 'bot',
            'intentos_intencion' => 0,
            'datos_registro' => null,
            'inicio_atencion' => null,
            'fin_atencion' => null,
        ];
    }
}
