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

    /** Orden utilizado para impedir que un webhook tardío retroceda el estado. */
    public function ordenSeguimiento(): int
    {
        return match ($this) {
            self::Recibido, self::Pendiente => 0,
            self::Enviado => 1,
            self::Entregado => 2,
            self::Leido => 3,
            self::Fallido => 4,
        };
    }
}
