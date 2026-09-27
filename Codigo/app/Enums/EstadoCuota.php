<?php

namespace App\Enums;

enum EstadoCuota: string
{
    case Pendiente = 'pendiente';
    case Pagada = 'pagada';
    case Vencida = 'vencida';
}
