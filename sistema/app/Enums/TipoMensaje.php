<?php

namespace App\Enums;

enum TipoMensaje: string
{
    case Texto = 'texto';
    case Imagen = 'imagen';
    case Audio = 'audio';
    case Documento = 'documento';
}
