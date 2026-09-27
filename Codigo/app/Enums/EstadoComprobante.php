<?php

namespace App\Enums;

enum EstadoComprobante: string
{
    case Pendiente = 'pendiente';
    case Aprobado = 'aprobado';
    case Rechazado = 'rechazado';
}
