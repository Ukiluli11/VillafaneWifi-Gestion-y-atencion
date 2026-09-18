<?php

namespace App\Enums;

/** Estados por los que puede pasar un ticket de soporte. */
enum EstadoTicket: string
{
    case Abierto = 'abierto';
    case EnAtencion = 'en_atencion';
    case Resuelto = 'resuelto';
    case Cerrado = 'cerrado';
}
