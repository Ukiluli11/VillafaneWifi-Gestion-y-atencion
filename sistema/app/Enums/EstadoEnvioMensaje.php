<?php

namespace App\Enums;

enum EstadoEnvioMensaje: string
{
    case Recibido = 'recibido';
    case Pendiente = 'pendiente';
    case Enviado = 'enviado';
    case Entregado = 'entregado';
    case Leido = 'leido';
    case Fallido = 'fallido';
}
