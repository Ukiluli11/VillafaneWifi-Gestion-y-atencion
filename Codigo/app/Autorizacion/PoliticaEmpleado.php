<?php

namespace App\Autorizacion;

use App\Enums\AccionSistema;
use App\Enums\AreaEmpleado;
use App\Models\Empleado;

class PoliticaEmpleado implements PoliticaAcceso
{
    /** @var array<string, list<AccionSistema>> */
    private const ACCIONES_POR_AREA = [
        AreaEmpleado::Administracion->value => [
            AccionSistema::ConsultarClientes, AccionSistema::GestionarClientes,
            AccionSistema::ConsultarPlanes, AccionSistema::GestionarPlanes,
            AccionSistema::ConsultarServicios, AccionSistema::GestionarServicios,
            AccionSistema::ConsultarCuentas, AccionSistema::GestionarCuentas,
            AccionSistema::ConsultarPagos, AccionSistema::GestionarPagos,
        ],
        AreaEmpleado::Soporte->value => [
            AccionSistema::ConsultarClientes, AccionSistema::ConsultarServicios,
        ],
        AreaEmpleado::AtencionCliente->value => [
            AccionSistema::ConsultarClientes, AccionSistema::ConsultarCuentas,
            AccionSistema::ConsultarPagos,
        ],
    ];

    public function __construct(private readonly Empleado $empleado) {}

    public function permite(AccionSistema $accion): bool
    {
        $area = $this->empleado->area?->value;

        return $area !== null && in_array($accion, self::ACCIONES_POR_AREA[$area] ?? [], true);
    }
}
