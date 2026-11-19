<?php

namespace App\Autorizacion;

use App\Enums\AccionSistema;
use App\Enums\NivelAcceso;
use App\Models\Administrador;

/**
 * Determina las acciones permitidas para el perfil de administrador según sus permisos.
 */
class PoliticaAdministrador implements PoliticaAcceso
{
    public function __construct(private readonly Administrador $administrador) {}

    public function permite(AccionSistema $accion): bool
    {
        if ($accion === AccionSistema::GestionarUsuarios) {
            return $this->administrador->nivel_acceso === NivelAcceso::Total && $this->administrador->puede_gestionar_usuarios;
        }

        if ($accion === AccionSistema::GestionarPlanes) {
            return $this->administrador->nivel_acceso === NivelAcceso::Total && $this->administrador->puede_configurar_planes;
        }

        return true;
    }
}
