<?php

namespace App\Http\Controllers\Web;

use App\Dominio\ServicioConsultaComprobantes;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Presenta los comprobantes informados por WhatsApp. */
class ComprobanteController extends Controller
{
    /** Muestra la cola de comprobantes pendientes y ya revisados. */
    public function index(Request $request, ServicioConsultaComprobantes $servicio): View
    {
        return view('comprobantes.index', [
            'comprobantes' => $servicio->buscar(
                $request->string('buscar')->toString(),
                $request->string('estado')->toString(),
            ),
        ]);
    }
}
