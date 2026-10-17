<?php

namespace App\Http\Controllers\Web;

use App\Dominio\ServicioFacturacion;
use App\Http\Controllers\Controller;
use App\Http\Requests\GenerarCuotasRequest;
use Illuminate\Http\RedirectResponse;

/**
 * CuotaController: Controlador web para la emision masiva de cuotas mensuales de abonados.
 * Corresponde a los casos de uso: 'Generar cuotas mensuales' y 'Marcar cuotas vencidas' (Modulo 1).
 * Conecta con el modelo Cuota y ServicioFacturacion.
 */
class CuotaController extends Controller
{
    /**
     * Dispara el calculo y generacion masiva de obligaciones mensuales para servicios activos.
     */
    public function store(GenerarCuotasRequest $request, ServicioFacturacion $servicio): RedirectResponse
    {
        $cantidad = $servicio->generarParaServiciosActivos($request->string('periodo')->toString());

        return back()->with('exito', "Se generaron {$cantidad} cuotas nuevas.");
    }
}
