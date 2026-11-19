<?php

namespace App\Enums;

/**
 * Distingue los modos de atención que puede usar una conversación.
 */
enum ModoAtencion: string
{
    case Bot = 'bot';
    case Humano = 'humano';
}
