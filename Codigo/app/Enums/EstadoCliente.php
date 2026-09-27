<?php

namespace App\Enums;

enum EstadoCliente: string
{
    case Activo = 'activo';
    case Suspendido = 'suspendido';
    case Baja = 'baja';
}
