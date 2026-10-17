<?php

namespace App\Enums;

enum TipoMensaje: string
{
    case Texto = 'texto';
    case Imagen = 'imagen';
    case Documento = 'documento';
    case Audio = 'audio';
    case Ubicacion = 'ubicacion';
    case Contacto = 'contacto';
    case Interactivo = 'interactivo';
    case Notificacion = 'notificacion';
    case Desconocido = 'desconocido';
}
