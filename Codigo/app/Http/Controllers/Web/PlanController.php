<?php

namespace App\Http\Controllers\Web;

use App\Dominio\ServicioPlanes;
use App\Enums\EstadoPlan;
use App\Http\Controllers\Controller;
use App\Http\Requests\ActualizarPlanRequest;
use App\Http\Requests\GuardarPlanRequest;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
    public function index(Request $request): View
    {
        $busqueda = trim($request->query->getString('buscar'));
        $estado = EstadoPlan::tryFrom($request->query->getString('estado'));
        $uso = $request->query->getString('uso');
        $planes = Plan::query()->withExists('servicios')
            ->when($busqueda !== '', fn ($consulta) => $consulta->where(fn ($filtro) => $filtro
                ->whereLike('nombre', "%{$busqueda}%")
                ->orWhereLike('velocidad', "%{$busqueda}%")))
            ->when($estado, fn ($consulta) => $consulta->where('estado', $estado->value))
            ->when($uso === 'con_servicios', fn ($consulta) => $consulta->has('servicios'))
            ->when($uso === 'sin_servicios', fn ($consulta) => $consulta->doesntHave('servicios'))
            ->orderBy('nombre')->orderBy('id_plan')->paginate(20)->withQueryString();

        return view('planes.index', compact('planes', 'busqueda', 'estado', 'uso'));
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

    public function reactivar(Plan $plan, ServicioPlanes $servicio): RedirectResponse
    {
        $servicio->activar($plan);

        return back()->with('exito', 'Plan reactivado correctamente.');
    }

    public function eliminar(Plan $plan, ServicioPlanes $servicio): RedirectResponse
    {
        $servicio->eliminar($plan);

        return back()->with('exito', 'Plan eliminado correctamente.');
    }
}
