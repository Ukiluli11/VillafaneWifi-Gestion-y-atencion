<?php

namespace App\Enums;

enum TipoEmisorMensaje: string
{
    case Cliente = 'cliente';
    case Bot = 'bot';
    case UsuarioInterno = 'usuario_interno';
}
