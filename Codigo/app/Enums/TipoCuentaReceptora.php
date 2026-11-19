<?php

namespace App\Enums;

/**
 * Enumera los tipos de cuenta que pueden configurarse para recibir pagos.
 */
enum TipoCuentaReceptora: string
{
    case Banco = 'banco';
    case MercadoPago = 'mercado_pago';
    case Uala = 'uala';
    case Otro = 'otro';
}
