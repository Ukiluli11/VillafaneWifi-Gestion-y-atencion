<?php

namespace App\Enums;

/**
 * Enumera los medios admitidos para registrar pagos.
 */
enum MedioPago: string
{
    case Transferencia = 'transferencia';
    case Efectivo = 'efectivo';
    case MercadoPago = 'mercado_pago';
    case Uala = 'uala';
    case Otro = 'otro';
}
