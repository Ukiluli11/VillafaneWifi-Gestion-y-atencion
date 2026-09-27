<?php

namespace App\Enums;

enum EstadoServicio: string
{
    case Activo = 'activo';
    case Suspendido = 'suspendido';
    case Baja = 'baja';
}
