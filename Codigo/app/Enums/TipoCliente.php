<?php

namespace App\Enums;

/**
 * Enumera las categorías de clientes que registra el sistema.
 */
enum TipoCliente: string
{
    case Particular = 'particular';
    case Comercio = 'comercio';
}
