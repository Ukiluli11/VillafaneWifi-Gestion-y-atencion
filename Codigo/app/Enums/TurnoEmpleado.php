<?php

namespace App\Enums;

/**
 * Enumera los turnos asignables al personal.
 */
enum TurnoEmpleado: string
{
    case Manana = 'mañana';
    case Tarde = 'tarde';
    case Noche = 'noche';
}
