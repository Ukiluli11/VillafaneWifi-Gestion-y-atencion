<?php

namespace App\Models;

use App\Enums\EstadoPlan;
use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function servicios(): HasMany
    {
        return $this->hasMany(Servicio::class, 'id_plan', 'id_plan');
    }

    public function cambiarPrecio(string $nuevoPrecio): void
    {
        $this->update(['precio_vigente' => $nuevoPrecio]);
    }

    public function activar(): void
    {
        $this->update(['estado' => EstadoPlan::Activo]);
    }

    public function desactivar(): void
    {
        $this->update(['estado' => EstadoPlan::Inactivo]);
    }
}
