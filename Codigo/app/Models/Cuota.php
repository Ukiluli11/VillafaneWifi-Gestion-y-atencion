<?php

namespace App\Models;

use App\Enums\EstadoCuota;
use Database\Factories\CuotaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo Cuota
 * Representa la obligación mensual de pago por un servicio de internet contratado.
 */
class Cuota extends Model
{
    /** @use HasFactory<CuotaFactory> */
    use HasFactory;

    protected $table = 'cuota';

    protected $primaryKey = 'id_cuota';

    public $timestamps = false;

    protected $fillable = [
        'id_servicio', 'id_pago', 'periodo', 'monto', 'fecha_emision',
        'fecha_vencimiento', 'estado',
    ];

    protected $attributes = ['estado' => 'pendiente'];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'fecha_emision' => 'date',
            'fecha_vencimiento' => 'date',
            'estado' => EstadoCuota::class,
        ];
    }

    /**
     * Servicio de internet al que corresponde esta cuota.
     */
    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'id_servicio', 'id_servicio');
    }

    /**
     * Pago con el que se canceló esta cuota (si está pagada).
     */
    public function pago(): BelongsTo
    {
        return $this->belongsTo(Pago::class, 'id_pago', 'id_pago');
    }

    /**
     * Caso de Uso: Imputar pago a cuotas pendientes (Módulo 3)
     * Clase UML: Cuota
     * Método UML: + marcarComoPagada(pago: Pago) : void
     */
    public function marcarComoPagada(Pago $pago): void
    {
        $this->update(['id_pago' => $pago->id_pago, 'estado' => EstadoCuota::Pagada]);
    }

    /**
     * Caso de Uso: Generar cuotas y actualizar vencimientos (Módulo 1)
     * Clase UML: Cuota
     * Método UML: + marcarComoVencida() : void
     */
    public function marcarComoVencida(): void
    {
        if ($this->estado === EstadoCuota::Pendiente) {
            $this->update(['estado' => EstadoCuota::Vencida]);
        }
    }
}
