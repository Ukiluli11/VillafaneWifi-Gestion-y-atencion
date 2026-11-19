<?php

namespace App\Enums;

/**
 * Enumera las áreas a las que se puede asignar un empleado.
 */
enum AreaEmpleado: string
{
    case Administracion = 'administracion';
    case Soporte = 'soporte';
    case AtencionCliente = 'atencion_cliente';
}
