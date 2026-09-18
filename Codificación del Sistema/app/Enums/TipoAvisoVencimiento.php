<?php

namespace App\Enums;

/** Momento de la deuda que originó el aviso automático. */
enum TipoAvisoVencimiento: string
{
    case Proximo = 'proximo';
    case Vencido = 'vencido';
}
