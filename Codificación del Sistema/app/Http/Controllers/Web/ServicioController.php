<?php

namespace App\Http\Controllers\Web;

use App\Dominio\ServicioContrataciones;
use App\Http\Controllers\Controller;
use App\Http\Requests\ActualizarServicioRequest;
use App\Http\Requests\GuardarServicioRequest;
use App\Models\Cliente;
use App\Models\Plan;
use App\Models\Servicio;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ServicioController extends Controller
{
    public function index(): View
    {
        return view('servicios.index', ['servicios' => Servicio::with(['cliente', 'plan'])->orderByDesc('id_servicio')->paginate(20)]);
    }

    public function create(): View
    {
        return view('servicios.formulario', [
            'clientes' => Cliente::where('estado', 'activo')->orderBy('nombre_razon_social')->get(),
            'planes' => Plan::where('estado', 'activo')->orderBy('nombre')->get(),
        ]);
    }

    public function store(GuardarServicioRequest $request, ServicioContrataciones $dominio): RedirectResponse
    {
        $datos = $request->validated();
        $cliente = Cliente::findOrFail($datos['id_cliente']);
        $servicio = $dominio->crear($cliente, $datos);

        return redirect()->route('servicios.index')->with('exito', "Servicio #{$servicio->id_servicio} registrado.");
    }

    public function edit(Servicio $servicio): View
    {
        return view('servicios.formulario', [
            'servicio' => $servicio,
            'clientes' => Cliente::where('id_cliente', $servicio->id_cliente)->get(),
            'planes' => Plan::orderBy('nombre')->get(),
        ]);
    }

    public function update(ActualizarServicioRequest $request, Servicio $servicio, ServicioContrataciones $dominio): RedirectResponse
    {
        $dominio->actualizar($servicio, $request->validated());

        return redirect()->route('servicios.index')->with('exito', 'Servicio actualizado correctamente.');
    }

    public function suspender(Servicio $servicio, ServicioContrataciones $dominio): RedirectResponse
    {
        $dominio->suspender($servicio);

        return back()->with('exito', 'Servicio suspendido.');
    }

    public function reactivar(Servicio $servicio, ServicioContrataciones $dominio): RedirectResponse
    {
        $dominio->reactivar($servicio);

        return back()->with('exito', 'Servicio reactivado.');
    }

    public function destroy(Servicio $servicio): RedirectResponse
    {
        $servicio->update(['estado' => 'baja']);

        return back()->with('exito', 'Servicio dado de baja.');
    }
}
