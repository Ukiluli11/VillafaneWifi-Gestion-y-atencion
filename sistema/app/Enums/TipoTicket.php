<?php

namespace App\Enums;

/** Clasificación funcional de los reclamos registrados. */
enum TipoTicket: string
{
    case Tecnico = 'tecnico';
    case Administrativo = 'administrativo';
    case Consulta = 'consulta';
    case Otro = 'otro';
}
