<?php

namespace App\Http\Controllers\Web;

use App\Dominio\ServicioCuentaCorriente;
use App\Dominio\ServicioFacturacion;
use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\CuentaReceptora;
use Illuminate\View\View;

class CuentaCorrienteController extends Controller
{
    public function show(
        Cliente $cliente,
        ServicioFacturacion $facturacion,
        ServicioCuentaCorriente $cuentaCorriente,
    ): View {
        $facturacion->actualizarEstadosVencidos();

        return view('cuentas.detalle', [
            'cliente' => $cliente,
            'cuotas' => $cuentaCorriente->cuotasDelCliente($cliente),
            'resumen' => $cuentaCorriente->resumir($cliente),
            'cuentasReceptoras' => CuentaReceptora::where('estado', 'activa')->orderBy('nombre')->get(),
        ]);
    }
}
