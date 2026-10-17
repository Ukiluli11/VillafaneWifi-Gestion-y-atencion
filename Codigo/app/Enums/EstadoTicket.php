<?php

namespace App\Enums;

enum EstadoTicket: string
{
    case Pendiente = 'pendiente';
    case Asignado = 'asignado';
    case EnProceso = 'en_proceso';
    case Resuelto = 'resuelto';
    case Cerrado = 'cerrado';
    case Reabierto = 'reabierto';
}
