<?php

namespace App\Models;

use App\Enums\EstadoTicket;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo Ticket
 * Representa una solicitud de soporte técnico o reclamo derivado de una conversación.
 */
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

    /**
     * Conversación de origen desde donde se generó el reclamo.
     */
    public function conversacion(): BelongsTo
    {
        return $this->belongsTo(Conversacion::class, 'id_conversacion', 'id_conversacion');
    }

    /**
     * Servicio de internet involucrado en el reclamo.
     */
    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'id_servicio', 'id_servicio');
    }

    /**
     * Usuario/técnico asignado para resolver el ticket.
     */
    public function usuarioAsignado(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_asignado', 'id_usuario');
    }

    /**
     * Caso de Uso: Asignar ticket a técnico (Módulo 2)
     * Clase UML: Ticket | Método UML: + asignar(usuario: Usuario) : void
     */
    public function asignar(Usuario $usuario): void
    {
        $this->update([
            'id_usuario_asignado' => $usuario->id_usuario,
            'estado' => EstadoTicket::Asignado,
            'fecha_asignacion' => now(),
        ]);
    }

    /**
     * Caso de Uso: Resolver ticket de reclamo (Módulo 2)
     * Clase UML: Ticket | Método UML: + resolver() : void
     */
    public function resolver(): void
    {
        $this->update(['estado' => EstadoTicket::Resuelto, 'fecha_resolucion' => now()]);
    }

    /**
     * Caso de Uso: Cerrar ticket de soporte (Módulo 2)
     * Clase UML: Ticket | Método UML: + cerrar() : void
     */
    public function cerrar(): void
    {
        $this->update(['estado' => EstadoTicket::Cerrado]);
    }

    /**
     * Caso de Uso: Reabrir ticket de soporte (Módulo 2)
     * Clase UML: Ticket | Método UML: + reabrir() : void
     */
    public function reabrir(): void
    {
        $this->update(['estado' => EstadoTicket::Reabierto, 'fecha_resolucion' => null]);
    }

    /**
     * Notas internas de seguimiento registradas en este ticket.
     */
    public function notas(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(NotaInterna::class, 'id_ticket', 'id_ticket');
    }

    /**
     * Caso de Uso: Agregar nota interna a ticket (Módulo 2)
     * Clase UML: Ticket
     * Método UML: + agregarNota(contenido: String, usuario: Usuario) : NotaInterna
     * Registra una nota interna asociada al ticket para seguimiento del equipo.
     */
    public function agregarNota(string $contenido, Usuario $usuario): NotaInterna
    {
        return $this->notas()->create([
            'id_usuario' => $usuario->id_usuario,
            'contenido'  => $contenido,
            'fecha_hora' => now(),
        ]);
    }
}
