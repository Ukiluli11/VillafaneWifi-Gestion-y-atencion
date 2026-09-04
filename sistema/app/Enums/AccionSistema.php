<?php

namespace App\Enums;

enum AccionSistema: string
{
    case ConsultarClientes = 'consultar_clientes';
    case GestionarClientes = 'gestionar_clientes';
    case ConsultarPlanes = 'consultar_planes';
    case GestionarPlanes = 'gestionar_planes';
    case ConsultarServicios = 'consultar_servicios';
    case GestionarServicios = 'gestionar_servicios';
    case ConsultarCuentas = 'consultar_cuentas';
    case GestionarCuentas = 'gestionar_cuentas';
    case ConsultarPagos = 'consultar_pagos';
    case GestionarPagos = 'gestionar_pagos';
    case GestionarUsuarios = 'gestionar_usuarios';
}
