<?php

namespace App\Enums;

/** Estado del intento de notificación de una cuota. */
enum EstadoAvisoVencimiento: string
{
    case Pendiente = 'pendiente';
    case Enviado = 'enviado';
    case Fallido = 'fallido';
}
