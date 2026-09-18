<?php

namespace App\Dominio;

use App\Enums\EstadoUsuario;
use App\Models\Administrador;
use App\Models\Empleado;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ServicioUsuarios
{
    /** @param array<string, mixed> $datos */
    public function crear(array $datos): Usuario
    {
        return DB::transaction(function () use ($datos): Usuario {
            $usuario = Usuario::create([
                'nombre_usuario' => trim((string) $datos['nombre_usuario']),
                'credencial' => Hash::make((string) $datos['contrasena']),
                'estado' => EstadoUsuario::Activo,
            ]);

            if ($datos['tipo'] === 'administrador') {
                Administrador::create([
                    'id_administrador' => $usuario->id_usuario,
                    'nivel_acceso' => $datos['nivel_acceso'] ?? 'total',
                    'puede_gestionar_usuarios' => $datos['puede_gestionar_usuarios'] ?? true,
                    'puede_configurar_planes' => $datos['puede_configurar_planes'] ?? true,
                ]);
            } elseif ($datos['tipo'] === 'empleado') {
                Empleado::create([
                    'id_empleado' => $usuario->id_usuario,
                    'area' => $datos['area'] ?? null,
                    'cargo' => $datos['cargo'] ?? null,
                    'turno' => $datos['turno'] ?? null,
                    'fecha_ingreso' => $datos['fecha_ingreso'] ?? null,
                ]);
            } else {
                throw ValidationException::withMessages(['tipo' => 'El tipo de usuario no es válido.']);
            }

            return $usuario->load(['administrador', 'empleado']);
        });
    }
}
