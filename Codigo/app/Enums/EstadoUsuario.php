<?php

namespace App\Enums;

/**
 * Enumera los estados que determinan si una cuenta de usuario está habilitada.
 */
enum EstadoUsuario: string
{
    case Activo = 'activo';
    case Inactivo = 'inactivo';
}
