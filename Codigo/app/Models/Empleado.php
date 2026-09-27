<?php

namespace App\Models;

use App\Enums\AreaEmpleado;
use App\Enums\TurnoEmpleado;
use Database\Factories\EmpleadoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Empleado extends Model
{
    /** @use HasFactory<EmpleadoFactory> */
    use HasFactory;

    protected $table = 'empleado';

    protected $primaryKey = 'id_empleado';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['id_empleado', 'area', 'cargo', 'turno', 'fecha_ingreso'];

    protected function casts(): array
    {
        return ['area' => AreaEmpleado::class, 'turno' => TurnoEmpleado::class, 'fecha_ingreso' => 'date'];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_empleado', 'id_usuario');
    }
}
