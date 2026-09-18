<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\Conversacion;
use App\Models\Servicio;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Ticket> */
class TicketFactory extends Factory
{
    /** Genera un ticket abierto para pruebas automatizadas. */
    public function definition(): array
    {
        $cliente = Cliente::factory();

        return [
            'id_conversacion' => Conversacion::factory()->for($cliente, 'cliente'),
            'id_servicio' => Servicio::factory()->for($cliente, 'cliente'),
            'id_empleado' => null,
            'fecha_creacion' => now(),
            'tipo' => 'tecnico',
            'descripcion' => fake()->sentence(),
            'estado' => 'abierto',
            'fecha_resolucion' => null,
            'fecha_asignacion' => null,
        ];
    }
}
