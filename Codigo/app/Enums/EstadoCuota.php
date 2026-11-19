<?php

namespace App\Enums;

/**
 * Enumera los estados de una cuota dentro del proceso de facturación y cobro.
 */
enum EstadoCuota: string
{
    case Pendiente = 'pendiente';
    case Pagada = 'pagada';
    case Vencida = 'vencida';
}
