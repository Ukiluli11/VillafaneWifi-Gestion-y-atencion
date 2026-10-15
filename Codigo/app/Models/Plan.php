<?php

namespace App\Models;

use App\Enums\EstadoPlan;
use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo Plan
 * Representa un plan de internet del catálogo comercial (velocidad y tarifa mensual).
 */
class Plan extends Model
{
    /** @use HasFactory<PlanFactory> */
    use HasFactory;

    protected $table = 'plan';

    protected $primaryKey = 'id_plan';

    public $timestamps = false;

    protected $fillable = ['nombre', 'velocidad', 'precio_vigente', 'estado'];

    protected $attributes = ['estado' => 'activo'];

    protected function casts(): array
    {
        return ['precio_vigente' => 'decimal:2', 'estado' => EstadoPlan::class];
    }

    /**
     * Servicios contratados que utilizan este plan.
     */
    public function servicios(): HasMany
    {
        return $this->hasMany(Servicio::class, 'id_plan', 'id_plan');
    }

    /**
     * Caso de Uso: Editar plan de servicio (Módulo 1)
     * Clase UML: Plan
     * Método UML: + cambiarPrecio(nuevoPrecio: BigDecimal) : void
     */
    public function cambiarPrecio(string $nuevoPrecio): void
    {
        $this->update(['precio_vigente' => $nuevoPrecio]);
    }

    /**
     * Caso de Uso: Gestionar catálogo de planes - Activar (Módulo 1)
     * Clase UML: Plan
     * Método UML: + activar() : void
     */
    public function activar(): void
    {
        $this->update(['estado' => EstadoPlan::Activo]);
    }

    /**
     * Caso de Uso: Dar de baja plan de servicio (Módulo 1)
     * Clase UML: Plan
     * Método UML: + desactivar() : void
     */
    public function desactivar(): void
    {
        $this->update(['estado' => EstadoPlan::Inactivo]);
    }
}
