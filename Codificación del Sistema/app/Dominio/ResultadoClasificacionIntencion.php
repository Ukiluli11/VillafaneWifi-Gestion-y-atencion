<?php

namespace App\Dominio;

use App\Enums\IntencionWhatsapp;

/** Resultado validado que puede consumir la lógica del bot. */
final readonly class ResultadoClasificacionIntencion
{
    public function __construct(
        public IntencionWhatsapp $intencion,
        public float $confianza,
        public string $origen,
    ) {}
}
