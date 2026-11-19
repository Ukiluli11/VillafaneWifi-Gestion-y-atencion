<?php

namespace App\Enums;

/**
 * Identifica los canales desde los que puede ingresar un comprobante.
 */
enum OrigenComprobante: string
{
    case Whatsapp = 'whatsapp';
    case PanelManual = 'panel_manual';
}
