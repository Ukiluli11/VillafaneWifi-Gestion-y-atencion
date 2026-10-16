<?php

namespace App\Http\Controllers\Web;

use App\Dominio\ServicioFacturacion;
use App\Enums\MedioPago;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegistrarPagoRequest;
use App\Models\CuentaReceptora;
use Illuminate\Http\RedirectResponse;

/**
 * PagoController: Controlador web para el registro manual de cobros e imputacion de cuotas.
 * Corresponde al caso de uso: 'Imputar pago' (Modulo 3).
 * Conecta con el modelo Pago (metodo imputar()), Cuota (marcarComoPagada()) y ServicioFacturacion.
 */
class PagoController extends Controller
{
    /**
     * Registra un pago y lo imputa contra las cuotas adeudadas seleccionadas por el operador.
     */
    public function store(RegistrarPagoRequest $request, ServicioFacturacion $servicio): RedirectResponse
    {
        $datos = $request->validated();
        $pago = $servicio->registrarPago(
            array_map('intval', $datos['cuotas']),
            CuentaReceptora::findOrFail($datos['id_cuenta']),
            MedioPago::from($datos['medio_pago']),
            $datos['fecha'],
        );

        return back()->with('exito', "Pago #{$pago->id_pago} registrado por \${$pago->monto_total}.");
    }
}
