<?php

namespace App\Dominio;

use App\Enums\EstadoServicio;
use App\Enums\EstadoTicket;
use App\Models\Cliente;
use App\Models\Conversacion;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

/**
 * ServicioTickets: Genera tickets de reclamo tecnico originados desde la atencion automatica de WhatsApp.
 * Corresponde a los casos de uso: 'Registrar reclamo o soporte tecnico' y 'Generar ticket en el sistema'.
 * Conecta con el modelo Ticket y el servicio activo del cliente.
 */
class ServicioTickets
{
    public function crearDesdeConversacion(
        Conversacion $conversacion,
        Cliente $cliente,
        string $descripcion,
    ): ?Ticket {
        $servicio = $cliente->servicios()
            ->where('estado', '!=', EstadoServicio::Baja->value)
            ->orderByRaw('CASE WHEN estado = ? THEN 0 ELSE 1 END', [EstadoServicio::Activo->value])
            ->orderBy('id_servicio')
            ->first();

        if ($servicio === null) {
            return null;
        }

        return DB::transaction(fn (): Ticket => Ticket::create([
            'id_conversacion' => $conversacion->id_conversacion,
            'id_servicio' => $servicio->id_servicio,
            'fecha_creacion' => now(),
            'tipo' => 'tecnico',
            'descripcion' => trim($descripcion),
            'estado' => EstadoTicket::Pendiente,
        ]));
    }
}
