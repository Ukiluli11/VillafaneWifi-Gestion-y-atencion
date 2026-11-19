<?php

namespace App\Enums;

/**
 * Enumera los estados de una conversación durante su atención.
 */
enum EstadoConversacion: string
{
    case Abierta = 'abierta';
    case Escalada = 'escalada';
    case EnAtencion = 'en_atencion';
    case Cerrada = 'cerrada';
}
