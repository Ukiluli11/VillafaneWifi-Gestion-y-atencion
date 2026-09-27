<?php

namespace App\Enums;

enum TipoDocumento: string
{
    case Dni = 'DNI';
    case Cuit = 'CUIT';
    case Cuil = 'CUIL';
}
