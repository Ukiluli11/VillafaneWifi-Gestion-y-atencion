<?php

namespace App\Models;

use App\Enums\MedioPago;
use Database\Factories\PagoFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo Pago
 * Registra el ingreso económico confirmado y su imputación a cuotas adeudadas.
 */
class Pago extends Model
{
    /** @use HasFactory<PagoFactory> */
    use HasFactory;

    protected $table = 'pago';

    protected $primaryKey = 'id_pago';

    public $timestamps = false;

    protected $fillable = ['id_comprobante', 'id_cuenta', 'fecha', 'monto_total', 'medio_pago'];

    protected function casts(): array
    {
        return ['fecha' => 'date', 'monto_total' => 'decimal:2', 'medio_pago' => MedioPago::class];
    }

    /**
     * Cuenta receptora donde ingresó el dinero.
     */
    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(CuentaReceptora::class, 'id_cuenta', 'id_cuenta');
    }

    /**
     * Comprobante respaldatorio del pago (si provino de validación).
     */
    public function comprobante(): BelongsTo
    {
        return $this->belongsTo(Comprobante::class, 'id_comprobante', 'id_comprobante');
    }

    /**
     * Cuotas canceladas mediante este pago.
     */
    public function cuotas(): HasMany
    {
        return $this->hasMany(Cuota::class, 'id_pago', 'id_pago');
    }

    /**
     * Caso de Uso: Imputar pago a cuotas pendientes (Módulo 3)
     * Clase UML: Pago
     * Método UML: + imputar(cuotas: List<Cuota>) : void
     * Servicio coordinador: ServicioFacturacion::imputarPagoACuotas()
     *
     * @param Collection<int, Cuota> $cuotas
     */
    public function imputar(Collection $cuotas): void
    {
        foreach ($cuotas as $cuota) {
            $cuota->marcarComoPagada($this);
        }
    }
}
