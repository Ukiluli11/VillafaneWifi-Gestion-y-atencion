<?php

namespace App\Enums;

enum TipoCuentaReceptora: string
{
    case Banco = 'banco';
    case MercadoPago = 'mercado_pago';
    case Uala = 'uala';
    case Otro = 'otro';
}
