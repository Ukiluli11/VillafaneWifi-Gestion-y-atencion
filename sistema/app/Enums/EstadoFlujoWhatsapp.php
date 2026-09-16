<?php

namespace App\Enums;

/** Paso funcional que el bot espera dentro de una conversación. */
enum EstadoFlujoWhatsapp: string
{
    case Menu = 'menu';
    case EsperandoComprobante = 'esperando_comprobante';
}
