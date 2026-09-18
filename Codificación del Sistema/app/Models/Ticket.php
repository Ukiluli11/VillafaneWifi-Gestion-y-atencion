<?php

namespace App\Models;

use App\Enums\EstadoTicket;
use App\Enums\TipoTicket;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Representa un reclamo generado desde una conversación de WhatsApp. */
class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    protected $table = 'ticket';

    protected $primaryKey = 'id_ticket';

    public $timestamps = false;

    protected $fillable = [
        'id_conversacion', 'id_servicio', 'id_empleado', 'fecha_creacion',
        'tipo', 'descripcion', 'estado', 'fecha_resolucion', 'fecha_asignacion',
    ];

    protected $attributes = ['tipo' => 'tecnico', 'estado' => 'abierto'];

    /** Convierte estados, tipos y fechas a objetos del dominio. */
    protected function casts(): array
    {
        return [
            'tipo' => TipoTicket::class,
            'estado' => EstadoTicket::class,
            'fecha_creacion' => 'datetime',
            'fecha_resolucion' => 'datetime',
            'fecha_asignacion' => 'datetime',
        ];
    }

    /** Conversación en la que se originó el reclamo. */
    public function conversacion(): BelongsTo
    {
        return $this->belongsTo(Conversacion::class, 'id_conversacion', 'id_conversacion');
    }

    /** Servicio de Internet afectado por el reclamo. */
    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'id_servicio', 'id_servicio');
    }

    /** Empleado que tomó el ticket, cuando ya fue asignado. */
    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'id_empleado', 'id_empleado');
    }
}
