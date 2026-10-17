<?php

namespace App\Models;

use App\Enums\AreaEmpleado;
use App\Enums\TurnoEmpleado;
use Database\Factories\EmpleadoFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo Empleado
 * Representa el perfil del personal operativo asignado a soporte y atención.
 */
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

    /**
     * Cuenta de usuario del sistema vinculada al empleado.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_empleado', 'id_usuario');
    }

    /**
     * Caso de Uso: Consultar tickets asignados al empleado
     * Clase UML: Empleado
     * Método UML: + consultarTicketsAsignados() : List<Ticket>
     * Retorna los tickets asignados al usuario vinculado a este empleado.
     */
    public function consultarTicketsAsignados(): Collection
    {
        return $this->usuario->ticketsAsignados()->get();
    }

    /**
     * Caso de Uso: Tomar control de conversación escalada
     * Clase UML: Empleado
     * Método UML: + atenderConversacion(c: Conversacion) : void
     * Servicio coordinador: ServicioAtencionHumana::tomar()
     */
    public function atenderConversacion(Conversacion $conversacion): void
    {
        $conversacion->derivarAUsuario($this->usuario);
    }
}
