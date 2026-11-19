<?php

namespace App\Enums;

/**
 * Enumera los estados de disponibilidad de un plan comercial.
 */
enum EstadoPlan: string
{
    case Activo = 'activo';
    case Inactivo = 'inactivo';
}
