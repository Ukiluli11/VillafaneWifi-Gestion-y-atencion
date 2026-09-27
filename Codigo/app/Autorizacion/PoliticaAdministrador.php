<?php

namespace App\Autorizacion;

use App\Enums\AccionSistema;
use App\Models\Administrador;

class PoliticaAdministrador implements PoliticaAcceso
{
    public function __construct(private readonly Administrador $administrador) {}

    public function permite(AccionSistema $accion): bool
    {
        if ($accion === AccionSistema::GestionarUsuarios) {
            return $this->administrador->puede_gestionar_usuarios;
        }

        if ($accion === AccionSistema::GestionarPlanes) {
            return $this->administrador->puede_configurar_planes;
        }

        return true;
    }
}
