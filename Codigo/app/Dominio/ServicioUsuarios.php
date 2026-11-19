<?php

namespace App\Dominio;

use App\Enums\EstadoUsuario;
use App\Enums\NivelAcceso;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Gestiona las cuentas de administradores y empleados, sus perfiles y el estado de acceso.
 */
class ServicioUsuarios
{
    public function __construct(private readonly ServicioSeguridad $servicioSeguridad) {}

    /** @param array<string, mixed> $datos */
    public function crear(array $datos): Usuario
    {
        return DB::transaction(function () use ($datos): Usuario {
            $usuario = Usuario::create([
                'nombre_usuario' => trim((string) $datos['nombre_usuario']),
                'credencial' => $this->servicioSeguridad->hashearPassword((string) $datos['contrasena']),
                'estado' => EstadoUsuario::Activo,
            ]);

            $this->guardarPerfil($usuario, $datos);

            return $usuario->load(['administrador', 'empleado']);
        });
    }

    /** @param array<string, mixed> $datos */
    public function actualizar(Usuario $usuario, array $datos, Usuario $editor): void
    {
        DB::transaction(function () use ($usuario, $datos, $editor): void {
            $actual = Usuario::query()->lockForUpdate()->findOrFail($usuario->getKey());
            if ($actual->is($editor) && ($datos['tipo'] !== 'administrador'
                || ($datos['nivel_acceso'] ?? NivelAcceso::Total->value) !== NivelAcceso::Total->value
                || ! ($datos['puede_gestionar_usuarios'] ?? true))) {
                throw ValidationException::withMessages(['tipo' => 'No podés quitarte el permiso para gestionar usuarios.']);
            }

            $actual->update(['nombre_usuario' => trim((string) $datos['nombre_usuario'])]);
            $this->guardarPerfil($actual, $datos);
        }, 3);
    }

    public function cambiarContrasena(Usuario $usuario, string $actual, string $nueva): void
    {
        DB::transaction(function () use ($usuario, $actual, $nueva): void {
            $cuenta = Usuario::query()->lockForUpdate()->findOrFail($usuario->getKey());
            if (! $cuenta->autenticar($actual)) {
                throw ValidationException::withMessages(['contrasena_actual' => 'La contraseña actual es incorrecta.']);
            }

            $cuenta->cambiarContrasena($nueva);
        }, 3);
    }

    /** @param array<string, mixed> $datos */
    private function guardarPerfil(Usuario $usuario, array $datos): void
    {
        if ($datos['tipo'] === 'administrador') {
            $usuario->empleado()->delete();
            $usuario->administrador()->updateOrCreate([], [
                'nivel_acceso' => $datos['nivel_acceso'] ?? NivelAcceso::Total->value,
                'puede_gestionar_usuarios' => $datos['puede_gestionar_usuarios'] ?? true,
                'puede_configurar_planes' => $datos['puede_configurar_planes'] ?? true,
            ]);

            return;
        }

        if ($datos['tipo'] !== 'empleado') {
            throw ValidationException::withMessages(['tipo' => 'El tipo de usuario no es válido.']);
        }

        $usuario->administrador()->delete();
        $usuario->empleado()->updateOrCreate([], [
            'area' => $datos['area'] ?? null,
            'cargo' => $datos['cargo'] ?? null,
            'turno' => $datos['turno'] ?? null,
            'fecha_ingreso' => $datos['fecha_ingreso'] ?? null,
        ]);
    }
}
