<?php

namespace Database\Factories;

use App\Enums\EstadoConversacion;
use App\Enums\ModoAtencion;
use App\Models\Conversacion;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Conversacion> */
class ConversacionFactory extends Factory
{
    protected $model = Conversacion::class;

    public function definition(): array
    {
        return [
            'id_cliente' => null,
            'id_usuario_atencion' => null,
            'numero_whatsapp' => '549'.fake()->numerify('##########'),
            'fecha_hora_inicio' => now(),
            'estado' => EstadoConversacion::Abierta,
            'modo_atencion' => ModoAtencion::Bot,
            'intentos_interpretacion' => 0,
        ];
    }
}
