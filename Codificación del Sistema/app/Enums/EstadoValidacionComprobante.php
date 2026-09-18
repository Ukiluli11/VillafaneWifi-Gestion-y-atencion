<?php

namespace App\Enums;

/** Resultado de la revisión administrativa de un comprobante. */
enum EstadoValidacionComprobante: string
{
    case Pendiente = 'pendiente';
    case Aprobado = 'aprobado';
    case Rechazado = 'rechazado';
    case Duplicado = 'duplicado';
}
