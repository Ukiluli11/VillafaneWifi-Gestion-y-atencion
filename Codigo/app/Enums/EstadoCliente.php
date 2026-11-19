<?php

namespace App\Enums;

/**
 * Enumera los estados admitidos para un cliente registrado.
 */
enum EstadoCliente: string
{
    case Activo = 'activo';
    case Suspendido = 'suspendido';
    case Baja = 'baja';
}
