<?php

namespace App\Enums;

enum MedioPago: string
{
    case Transferencia = 'transferencia';
    case Efectivo = 'efectivo';
    case MercadoPago = 'mercado_pago';
    case Uala = 'uala';
    case Otro = 'otro';
}
