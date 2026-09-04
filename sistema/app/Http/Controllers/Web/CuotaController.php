<?php

namespace App\Http\Controllers\Web;

use App\Dominio\ServicioFacturacion;
use App\Http\Controllers\Controller;
use App\Http\Requests\GenerarCuotasRequest;
use Illuminate\Http\RedirectResponse;

class CuotaController extends Controller
{
    public function store(GenerarCuotasRequest $request, ServicioFacturacion $servicio): RedirectResponse
    {
        $cantidad = $servicio->generarParaServiciosActivos($request->string('periodo')->toString());

        return back()->with('exito', "Se generaron {$cantidad} cuotas nuevas.");
    }
}
