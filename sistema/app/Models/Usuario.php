<?php

namespace App\Models;

use App\Enums\EstadoUsuario;
use Database\Factories\UsuarioFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;

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

    public function getAuthPasswordName(): string
    {
        return 'credencial';
    }

    public function empleado(): HasOne
    {
        return $this->hasOne(Empleado::class, 'id_empleado', 'id_usuario');
    }

    public function administrador(): HasOne
    {
        return $this->hasOne(Administrador::class, 'id_administrador', 'id_usuario');
    }

    public function autenticar(string $contrasena): bool
    {
        return $this->estado === EstadoUsuario::Activo && Hash::check($contrasena, $this->credencial);
    }

    public function cambiarContrasena(string $nueva): void
    {
        $this->forceFill(['credencial' => Hash::make($nueva)])->save();
    }

    public function activar(): void
    {
        $this->update(['estado' => EstadoUsuario::Activo]);
    }

    public function desactivar(): void
    {
        $this->update(['estado' => EstadoUsuario::Inactivo]);
    }
}
