<?php

namespace App\Enums;

/**
 * Identifica quién emitió cada mensaje dentro de una conversación.
 */
enum TipoEmisorMensaje: string
{
    case Cliente = 'cliente';
    case Bot = 'bot';
    case UsuarioInterno = 'usuario_interno';
    case Sistema = 'sistema';
}
