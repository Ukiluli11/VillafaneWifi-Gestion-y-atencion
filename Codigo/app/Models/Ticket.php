<?php

namespace App\Models;

use App\Enums\EstadoTicket;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    protected $table = 'ticket';

    protected $primaryKey = 'id_ticket';

    public $timestamps = false;

    protected $fillable = [
        'id_conversacion', 'id_servicio', 'id_usuario_asignado', 'fecha_creacion',
        'tipo', 'descripcion', 'estado', 'fecha_asignacion', 'fecha_resolucion',
    ];

    protected $attributes = ['tipo' => 'tecnico', 'estado' => 'pendiente'];

    protected function casts(): array
    {
        return [
            'fecha_creacion' => 'datetime',
            'estado' => EstadoTicket::class,
            'fecha_asignacion' => 'datetime',
            'fecha_resolucion' => 'datetime',
        ];
    }

    public function conversacion(): BelongsTo
    {
        return $this->belongsTo(Conversacion::class, 'id_conversacion', 'id_conversacion');
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'id_servicio', 'id_servicio');
    }

    public function usuarioAsignado(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_asignado', 'id_usuario');
    }

    public function asignar(Usuario $usuario): void
    {
        $this->update([
            'id_usuario_asignado' => $usuario->id_usuario,
            'estado' => EstadoTicket::Asignado,
            'fecha_asignacion' => now(),
        ]);
    }

    public function resolver(): void
    {
        $this->update(['estado' => EstadoTicket::Resuelto, 'fecha_resolucion' => now()]);
    }

    public function cerrar(): void
    {
        $this->update(['estado' => EstadoTicket::Cerrado]);
    }

    public function reabrir(): void
    {
        $this->update(['estado' => EstadoTicket::Reabierto, 'fecha_resolucion' => null]);
    }
}
