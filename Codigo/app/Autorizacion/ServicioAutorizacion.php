<?php

namespace App\Autorizacion;

use App\Enums\AccionSistema;
use App\Enums\EstadoUsuario;
use App\Models\Usuario;

class ServicioAutorizacion
{
    public function puede(Usuario $usuario, AccionSistema $accion): bool
    {
        return $this->obtenerPolitica($usuario)?->permite($accion) ?? false;
    }

    private function obtenerPolitica(Usuario $usuario): ?PoliticaAcceso
    {
        if ($usuario->estado !== EstadoUsuario::Activo) {
            return null;
        }

        $usuario->loadMissing(['administrador', 'empleado']);
        $tieneAdministrador = $usuario->administrador !== null;
        $tieneEmpleado = $usuario->empleado !== null;

        if ($tieneAdministrador === $tieneEmpleado) {
            return null;
        }

        return $tieneAdministrador
            ? new PoliticaAdministrador($usuario->administrador)
            : new PoliticaEmpleado($usuario->empleado);
    }
}
