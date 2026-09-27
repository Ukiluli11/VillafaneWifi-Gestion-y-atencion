<?php

namespace App\Models;

use App\Enums\EstadoCuentaReceptora;
use App\Enums\TipoCuentaReceptora;
use Database\Factories\CuentaReceptoraFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CuentaReceptora extends Model
{
    /** @use HasFactory<CuentaReceptoraFactory> */
    use HasFactory;

    protected $table = 'cuenta_receptora';

    protected $primaryKey = 'id_cuenta';

    public $timestamps = false;

    protected $fillable = ['nombre', 'tipo', 'identificador', 'estado'];

    protected $attributes = ['estado' => 'activa'];

    protected function casts(): array
    {
        return ['tipo' => TipoCuentaReceptora::class, 'estado' => EstadoCuentaReceptora::class];
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'id_cuenta', 'id_cuenta');
    }

    public function activar(): void
    {
        $this->update(['estado' => EstadoCuentaReceptora::Activa]);
    }

    public function desactivar(): void
    {
        $this->update(['estado' => EstadoCuentaReceptora::Inactiva]);
    }
}
