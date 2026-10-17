<?php

namespace Database\Factories;

use App\Enums\EstadoEnvioMensaje;
use App\Enums\TipoEmisorMensaje;
use App\Enums\TipoMensaje;
use App\Models\Conversacion;
use App\Models\Mensaje;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Mensaje> */
class MensajeFactory extends Factory
{
    protected $model = Mensaje::class;

    public function definition(): array
    {
        return [
            'id_conversacion' => Conversacion::factory(),
            'id_usuario' => null,
            'id_mensaje_externo' => fake()->unique()->uuid(),
            'fecha_hora' => now(),
            'tipo' => TipoMensaje::Texto,
            'contenido' => fake()->sentence(),
            'archivo_adjunto' => null,
            'tipo_emisor' => TipoEmisorMensaje::Cliente,
            'estado_envio' => EstadoEnvioMensaje::Recibido,
        ];
    }
}
