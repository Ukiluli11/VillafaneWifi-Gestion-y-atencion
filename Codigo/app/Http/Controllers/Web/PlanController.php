<?php

namespace App\Http\Controllers\Web;

use App\Dominio\ServicioPlanes;
use App\Http\Controllers\Controller;
use App\Http\Requests\ActualizarPlanRequest;
use App\Http\Requests\GuardarPlanRequest;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * PlanController: Controlador web para la configuracion del catalogo de planes de internet.
 * Corresponde a los casos de uso: 'Configurar planes', 'Registrar y activar plan',
 * 'Cambiar precio de plan' y 'Desactivar plan' (Modulo 1).
 * Conecta con Administrador (configurarPlanes()), Plan (cambiarPrecio(), activar(), desactivar()) y ServicioPlanes.
 */
class PlanController extends Controller
{
    /**
     * Muestra el catalogo completo de planes con sus precios y velocidades.
     */
    public function index(): View
    {
        return view('planes.index', ['planes' => Plan::orderBy('nombre')->paginate(20)]);
    }

    /**
     * Muestra el formulario para registrar un nuevo paquete de conectividad.
     */
    public function create(): View
    {
        return view('planes.formulario');
    }

    /**
     * Registra un nuevo plan en el catalogo comercial en estado activo.
     */
    public function store(GuardarPlanRequest $request, ServicioPlanes $servicio): RedirectResponse
    {
        $servicio->crear($request->validated());

        return redirect()->route('planes.index')->with('exito', 'Plan registrado correctamente.');
    }

    /**
     * Muestra el formulario de edicion tarifaria y parametros tecnicos del plan.
     */
    public function edit(Plan $plan): View
    {
        return view('planes.formulario', compact('plan'));
    }

    /**
     * Actualiza el precio vigente o caracteristicas comerciales del plan.
     */
    public function update(ActualizarPlanRequest $request, Plan $plan, ServicioPlanes $servicio): RedirectResponse
    {
        $servicio->actualizar($plan, $request->validated());

        return redirect()->route('planes.index')->with('exito', 'Plan actualizado correctamente.');
    }

    /**
     * Desactiva el plan del catalogo para impedir nuevas contrataciones.
     */
    public function destroy(Plan $plan, ServicioPlanes $servicio): RedirectResponse
    {
        $servicio->desactivar($plan);

        return back()->with('exito', 'Plan desactivado correctamente.');
    }
}
