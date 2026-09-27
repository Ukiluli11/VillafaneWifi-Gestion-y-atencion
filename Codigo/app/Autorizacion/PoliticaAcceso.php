<?php

namespace App\Autorizacion;

use App\Enums\AccionSistema;

interface PoliticaAcceso
{
    public function permite(AccionSistema $accion): bool;
}
