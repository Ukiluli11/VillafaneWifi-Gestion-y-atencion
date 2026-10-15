<?php

namespace App\Models;

use App\Enums\EstadoUsuario;
use Database\Factories\UsuarioFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;

/**
 * Modelo Usuario
 * Gestiona la autenticación, credenciales y perfiles de acceso (empleado/administrador).
 */
class Usuario extends Authenticatable
{
    /** @use HasFactory<UsuarioFactory> */
    use HasFactory;

    protected $table = 'usuario';

    protected $primaryKey = 'id_usuario';

    public $timestamps = false;

    protected $rememberTokenName = null;

    protected $fillable = ['nombre_usuario', 'credencial', 'estado'];

    protected $attributes = ['estado' => 'activo'];

    protected $hidden = ['credencial'];

    protected function casts(): array
    {
        return ['estado' => EstadoUsuario::class];
    }

    /**
     * Nombre de la columna que almacena la contraseña hasheada.
     */
    public function getAuthPasswordName(): string
    {
        return 'credencial';
    }

    /**
     * Perfil operativo de empleado (si corresponde).
     */
    public function empleado(): HasOne
    {
        return $this->hasOne(Empleado::class, 'id_empleado', 'id_usuario');
    }

    /**
     * Perfil directivo de administrador (si corresponde).
     */
    public function administrador(): HasOne
    {
        return $this->hasOne(Administrador::class, 'id_administrador', 'id_usuario');
    }

    /**
     * Conversaciones de atención humana atendidas por este usuario.
     */
    public function conversacionesAtendidas(): HasMany
    {
        return $this->hasMany(Conversacion::class, 'id_usuario_atencion', 'id_usuario');
    }

    /**
     * Mensajes manuales redactados y enviados por este usuario.
     */
    public function mensajes(): HasMany
    {
        return $this->hasMany(Mensaje::class, 'id_usuario', 'id_usuario');
    }

    /**
     * Tickets técnicos asignados para resolución a este usuario.
     */
    public function ticketsAsignados(): HasMany
    {
        return $this->hasMany(Ticket::class, 'id_usuario_asignado', 'id_usuario');
    }

    /**
     * Caso de Uso: Iniciar sesión (Módulo 6)
     * Clase UML: Usuario
     * Método UML: + autenticar(contrasena: String) : boolean
     */
    public function autenticar(string $contrasena): bool
    {
        return $this->estado === EstadoUsuario::Activo && Hash::check($contrasena, $this->credencial);
    }

    /**
     * Caso de Uso: Cambiar contraseña (Módulo 6)
     * Clase UML: Usuario
     * Método UML: + cambiarContrasena(nueva: String) : void
     */
    public function cambiarContrasena(string $nueva): void
    {
        $this->forceFill(['credencial' => Hash::make($nueva)])->save();
    }

    /**
     * Caso de Uso: Asignar tipo y nivel de acceso - Activar usuario (Módulo 6)
     * Clase UML: Usuario
     * Método UML: + activar() : void
     */
    public function activar(): void
    {
        $this->update(['estado' => EstadoUsuario::Activo]);
    }

    /**
     * Caso de Uso: Desactivar usuario (Módulo 6)
     * Clase UML: Usuario
     * Método UML: + desactivar() : void
     */
    public function desactivar(): void
    {
        $this->update(['estado' => EstadoUsuario::Inactivo]);
    }
}
