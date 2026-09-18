<?php

namespace App\Http\Controllers\Web;

use App\Dominio\ServicioPlanes;
use App\Http\Controllers\Controller;
use App\Http\Requests\ActualizarPlanRequest;
use App\Http\Requests\GuardarPlanRequest;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(): View
    {
        return view('planes.index', ['planes' => Plan::orderBy('nombre')->paginate(20)]);
    }

    public function create(): View
    {
        return view('planes.formulario');
    }

    public function store(GuardarPlanRequest $request, ServicioPlanes $servicio): RedirectResponse
    {
        $servicio->crear($request->validated());

        return redirect()->route('planes.index')->with('exito', 'Plan registrado correctamente.');
    }

    public function edit(Plan $plan): View
    {
        return view('planes.formulario', compact('plan'));
    }

    public function update(ActualizarPlanRequest $request, Plan $plan, ServicioPlanes $servicio): RedirectResponse
    {
        $servicio->actualizar($plan, $request->validated());

        return redirect()->route('planes.index')->with('exito', 'Plan actualizado correctamente.');
    }

    public function destroy(Plan $plan, ServicioPlanes $servicio): RedirectResponse
    {
        $servicio->desactivar($plan);

        return back()->with('exito', 'Plan desactivado correctamente.');
    }
}
