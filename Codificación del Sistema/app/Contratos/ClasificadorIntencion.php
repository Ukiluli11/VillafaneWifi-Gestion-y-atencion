<?php

namespace App\Contratos;

use App\Dominio\ResultadoClasificacionIntencion;

/** Define el límite intercambiable con el proveedor de inteligencia artificial. */
interface ClasificadorIntencion
{
    public function clasificar(string $mensaje): ResultadoClasificacionIntencion;
}
