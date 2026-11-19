<?php

namespace App\Autorizacion;

use App\Enums\AccionSistema;

/**
 * Define el contrato que deben respetar las políticas de autorización del sistema.
 */
interface PoliticaAcceso
{
    public function permite(AccionSistema $accion): bool;
}
