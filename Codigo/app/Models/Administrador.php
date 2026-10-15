<?php

namespace App\Models;

use App\Enums\NivelAcceso;
use Database\Factories\AdministradorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo Administrador
 * Representa el perfil de administrador con permisos sobre usuarios y planes.
 */
class Administrador extends Model
{
    /** @use HasFactory<AdministradorFactory> */
    use HasFactory;

    protected $table = 'administrador';

    protected $primaryKey = 'id_administrador';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['id_administrador', 'nivel_acceso', 'puede_gestionar_usuarios', 'puede_configurar_planes'];

    protected $attributes = [
        'nivel_acceso' => 'total',
        'puede_gestionar_usuarios' => true,
        'puede_configurar_planes' => true,
    ];

    protected function casts(): array
    {
        return [
            'nivel_acceso' => NivelAcceso::class,
            'puede_gestionar_usuarios' => 'boolean',
            'puede_configurar_planes' => 'boolean',
        ];
    }

    /**
     * Relación con la cuenta de usuario base asociada.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_administrador', 'id_usuario');
    }

    /**
     * Caso de Uso: Gestionar usuarios del sistema (Módulo 6)
     * Clase UML: Administrador
     * Método UML: + gestionarUsuarios() : void
     * Servicio coordinador: ServicioUsuarios::crear()
     * Verifica si este administrador tiene permiso para gestionar usuarios.
     */
    public function gestionarUsuarios(): bool
    {
        return (bool) $this->puede_gestionar_usuarios;
    }

    /**
     * Caso de Uso: Configurar catálogo de planes (Módulo 1)
     * Clase UML: Administrador
     * Método UML: + configurarPlanes() : void
     * Servicio coordinador: ServicioPlanes::crear(), ServicioPlanes::actualizar()
     * Verifica si este administrador tiene permiso para configurar planes.
     */
    public function configurarPlanes(): bool
    {
        return (bool) $this->puede_configurar_planes;
    }
}
