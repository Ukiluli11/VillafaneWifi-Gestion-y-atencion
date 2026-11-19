<?php

namespace App\Enums;

/**
 * Enumera los estados del ticket a lo largo de su atención.
 */
enum EstadoTicket: string
{
    case Pendiente = 'pendiente';
    case Asignado = 'asignado';
    case EnProceso = 'en_proceso';
    case Resuelto = 'resuelto';
    case Cerrado = 'cerrado';
    case Reabierto = 'reabierto';
}
