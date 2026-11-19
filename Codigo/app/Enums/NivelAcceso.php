<?php

namespace App\Enums;

/**
 * Enumera los niveles de acceso usados para evaluar permisos.
 */
enum NivelAcceso: string
{
    case Total = 'total';
    case Restringido = 'restringido';
}
