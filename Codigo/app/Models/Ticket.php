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
     * Caso de Uso: Asignar ticket a técnico
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
     * Caso de Uso: Poner ticket en proceso
     * Clase UML: Ticket | Método UML: + ponerEnProceso() : void
     */
    public function ponerEnProceso(): void
    {
        $this->update(['estado' => EstadoTicket::EnProceso]);
    }

    /**
     * Caso de Uso: Resolver ticket de soporte
     * Clase UML: Ticket | Método UML: + resolver() : void
     */
    public function resolver(): void
    {
        $this->update(['estado' => EstadoTicket::Resuelto, 'fecha_resolucion' => now()]);
    }

    /**
     * Caso de Uso: Cerrar ticket de soporte
     * Clase UML: Ticket | Método UML: + cerrar() : void
     */
    public function cerrar(): void
    {
        $this->update(['estado' => EstadoTicket::Cerrado]);
    }

    /**
     * Caso de Uso: Reabrir ticket de soporte
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
     * Caso de Uso: Agregar nota interna a ticket
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

    /**
     * Scope para filtrar y listar tickets con criterios múltiples (RF-28).
     */
    public function scopeFiltrar($query, array $filtros)
    {
        return $query
            ->when(!empty($filtros['estado']), function ($q) use ($filtros) {
                $q->where('estado', $filtros['estado']);
            })
            ->when(!empty($filtros['id_usuario_asignado']), function ($q) use ($filtros) {
                if ($filtros['id_usuario_asignado'] === 'sin_asignar') {
                    $q->whereNull('id_usuario_asignado');
                } else {
                    $q->where('id_usuario_asignado', $filtros['id_usuario_asignado']);
                }
            })
            ->when(!empty($filtros['tipo']), function ($q) use ($filtros) {
                $q->where('tipo', $filtros['tipo']);
            })
            ->when(!empty($filtros['busqueda']), function ($q) use ($filtros) {
                $busqueda = '%' . trim($filtros['busqueda']) . '%';
                $q->where(function ($sub) use ($busqueda, $filtros) {
                    $sub->where('descripcion', 'like', $busqueda)
                        ->orWhere('id_ticket', trim($filtros['busqueda']))
                        ->orWhereHas('servicio.cliente', function ($qc) use ($busqueda) {
                            $qc->where('nombre_razon_social', 'like', $busqueda)
                                ->orWhere('numero_documento', 'like', $busqueda)
                                ->orWhere('telefono_whatsapp', 'like', $busqueda);
                        });
                });
            })
            ->when(!empty($filtros['fecha_desde']), function ($q) use ($filtros) {
                $q->whereDate('fecha_creacion', '>=', $filtros['fecha_desde']);
            })
            ->when(!empty($filtros['fecha_hasta']), function ($q) use ($filtros) {
                $q->whereDate('fecha_creacion', '<=', $filtros['fecha_hasta']);
            });
    }
}
