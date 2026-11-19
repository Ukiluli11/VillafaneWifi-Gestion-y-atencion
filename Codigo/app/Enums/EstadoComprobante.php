<?php

namespace App\Enums;

/**
 * Enumera los estados del comprobante dentro de su procesamiento y revisión.
 */
enum EstadoComprobante: string
{
    case Pendiente = 'pendiente';
    case Aprobado = 'aprobado';
    case Rechazado = 'rechazado';
}
