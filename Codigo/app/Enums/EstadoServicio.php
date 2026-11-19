<?php

namespace App\Enums;

/**
 * Enumera los estados operativos de un servicio contratado.
 */
enum EstadoServicio: string
{
    case Activo = 'activo';
    case Suspendido = 'suspendido';
    case Baja = 'baja';
}
