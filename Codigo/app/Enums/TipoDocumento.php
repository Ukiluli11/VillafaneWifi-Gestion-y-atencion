<?php

namespace App\Enums;

/**
 * Enumera los documentos admitidos para identificar clientes.
 */
enum TipoDocumento: string
{
    case Dni = 'DNI';
    case Cuit = 'CUIT';
    case Cuil = 'CUIL';
}
