<?php

namespace App\Dominio;

use App\Models\Conversacion;

/**
 * Informa el resultado de intentar tomar una conversación.
 */
final readonly class ResultadoTomaConversacion
{
    public function __construct(
        public Conversacion $conversacion,
        public bool $nuevaAsignacion,
    ) {}
}
