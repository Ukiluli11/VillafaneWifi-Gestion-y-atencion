<?php

namespace App\Models;

use App\Enums\EstadoServicio;
use Database\Factories\ServicioFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo Servicio
 * Representa la conexión física de internet instalada en el domicilio de un cliente.
 */
class Servicio extends Model
{
    /** @use HasFactory<ServicioFactory> */
    use HasFactory;

    protected $table = 'servicio';

    protected $primaryKey = 'id_servicio';

    public $timestamps = false;

    protected $fillable = [
        'id_plan', 'id_cliente', 'calle_instalacion', 'numero_instalacion',
        'localidad_instalacion', 'dia_vencimiento', 'proximo_vencimiento',
        'fecha_alta', 'ipv4', 'mac', 'estado',
    ];

    protected $attributes = ['estado' => 'activo'];

    protected function casts(): array
    {
        return [
            'proximo_vencimiento' => 'date',
            'fecha_alta' => 'date',
            'estado' => EstadoServicio::class,
        ];
    }

    /**
     * Cliente titular de la conexión.
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
    }

    /**
     * Plan de velocidad asignado a este servicio.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'id_plan', 'id_plan');
    }

    /**
     * Cuotas mensuales facturadas por este servicio.
     */
    public function cuotas(): HasMany
    {
        return $this->hasMany(Cuota::class, 'id_servicio', 'id_servicio');
    }

    /**
     * Tickets técnicos o reclamos asociados a este servicio.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'id_servicio', 'id_servicio');
    }

    /**
     * Caso de Uso: Suspender servicio por falta de pago (Módulo 1)
     * Clase UML: Servicio
     * Método UML: + suspender() : void
     */
    public function suspender(): void
    {
        $this->update(['estado' => EstadoServicio::Suspendido]);
    }

    /**
     * Caso de Uso: Reactivar servicio tras acreditación (Módulo 1)
     * Clase UML: Servicio
     * Método UML: + reactivar() : void
     */
    public function reactivar(): void
    {
        $this->update(['estado' => EstadoServicio::Activo]);
    }
}
