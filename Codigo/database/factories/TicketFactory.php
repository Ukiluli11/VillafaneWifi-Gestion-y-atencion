<?php

namespace Database\Factories;

use App\Enums\EstadoTicket;
use App\Models\Conversacion;
use App\Models\Servicio;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Ticket> */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'id_conversacion' => Conversacion::factory(),
            'id_servicio' => Servicio::factory(),
            'id_usuario_asignado' => null,
            'fecha_creacion' => now(),
            'tipo' => 'tecnico',
            'descripcion' => fake()->sentence(),
            'estado' => EstadoTicket::Pendiente,
        ];
    }
}
