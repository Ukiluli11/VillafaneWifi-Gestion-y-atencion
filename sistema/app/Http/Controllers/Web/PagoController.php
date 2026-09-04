<?php

namespace App\Http\Controllers\Web;

use App\Dominio\ServicioFacturacion;
use App\Enums\MedioPago;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegistrarPagoRequest;
use App\Models\CuentaReceptora;
use Illuminate\Http\RedirectResponse;

class PagoController extends Controller
{
    public function store(RegistrarPagoRequest $request, ServicioFacturacion $servicio): RedirectResponse
    {
        $datos = $request->validated();
        $pago = $servicio->registrarPago(
            array_map('intval', $datos['cuotas']),
            CuentaReceptora::findOrFail($datos['id_cuenta']),
            MedioPago::from($datos['medio_pago']),
            $datos['fecha'],
        );

        return back()->with('exito', "Pago #{$pago->id_pago} registrado por ${$pago->monto_total}.");
    }
}
