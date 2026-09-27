<?php

namespace App\Models;

use App\Enums\MedioPago;
use Database\Factories\PagoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(CuentaReceptora::class, 'id_cuenta', 'id_cuenta');
    }

    public function cuotas(): HasMany
    {
        return $this->hasMany(Cuota::class, 'id_pago', 'id_pago');
    }
}
