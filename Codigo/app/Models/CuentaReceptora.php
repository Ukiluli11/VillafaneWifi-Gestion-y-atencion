<?php

namespace App\Models;

use App\Enums\EstadoCuentaReceptora;
use App\Enums\TipoCuentaReceptora;
use Database\Factories\CuentaReceptoraFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo CuentaReceptora
 * Representa las cuentas bancarias o billeteras virtuales habilitadas para recibir pagos.
 */
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

    /**
     * Pagos imputados y registrados en esta cuenta receptora.
     */
    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'id_cuenta', 'id_cuenta');
    }

    /**
     * Caso de Uso: Gestionar cuentas receptoras - Activar (Módulo 1 / Módulo 3)
     * Clase UML: CuentaReceptora
     * Método UML: + activar() : void
     */
    public function activar(): void
    {
        $this->update(['estado' => EstadoCuentaReceptora::Activa]);
    }

    /**
     * Caso de Uso: Desactivar cuenta receptora (Módulo 1 / Módulo 3)
     * Clase UML: CuentaReceptora
     * Método UML: + desactivar() : void
     */
    public function desactivar(): void
    {
        $this->update(['estado' => EstadoCuentaReceptora::Inactiva]);
    }
}
