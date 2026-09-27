<?php

namespace App\Enums;

enum EstadoConversacion: string
{
    case Abierta = 'abierta';
    case Escalada = 'escalada';
    case EnAtencion = 'en_atencion';
    case Cerrada = 'cerrada';
}
