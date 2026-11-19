<?php

namespace App\Enums;

/**
 * Enumera los estados de disponibilidad de las cuentas habilitadas para recibir pagos.
 */
enum EstadoCuentaReceptora: string
{
    case Activa = 'activa';
    case Inactiva = 'inactiva';
}
