<?php

namespace App\Http\Controllers\Web;

use App\Dominio\ServicioConsultaConversaciones;
use App\Http\Controllers\Controller;
use App\Models\Conversacion;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Presenta las conversaciones y sus mensajes en el panel administrativo.
 */
class ConversacionController extends Controller
{
    /**
     * Muestra el listado paginado con búsqueda y filtro por estado.
     */
    public function index(Request $request, ServicioConsultaConversaciones $servicio): View
    {
        return view('conversaciones.index', [
            'conversaciones' => $servicio->buscar(
                $request->string('buscar')->toString(),
                $request->string('estado')->toString(),
            ),
        ]);
    }

    /**
     * Muestra el historial cronológico de una conversación.
     */
    public function show(Conversacion $conversacion, ServicioConsultaConversaciones $servicio): View
    {
        return view('conversaciones.detalle', [
            'conversacion' => $servicio->cargarDetalle($conversacion),
        ]);
    }
}
