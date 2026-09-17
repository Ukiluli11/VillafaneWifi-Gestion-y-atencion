<?php

namespace App\Enums;

enum EstadoConversacion: string
{
    case Abierta = 'abierta';
    case Escalada = 'escalada';
    case Cerrada = 'cerrada';
}
