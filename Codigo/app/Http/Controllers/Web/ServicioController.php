<?php

namespace App\Http\Controllers\Web;

use App\Dominio\ServicioContrataciones;
use App\Enums\EstadoServicio;
use App\Http\Controllers\Controller;
use App\Http\Requests\ActualizarServicioRequest;
use App\Http\Requests\GuardarServicioRequest;
use App\Models\Cliente;
use App\Models\Plan;
use App\Models\Servicio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * ServicioController: Controlador web para la administracion de conexiones tecnicas de internet.
 * Corresponde a los casos de uso: 'Asignar plan al servicio', 'Registrar datos de conexion (IP, MAC)'
 * y 'Dar de baja cliente o servicio' / 'Suspender servicio' / 'Reactivar servicio' (Modulo 1).
 * Conecta con Servicio (metodos suspender(), reactivar()) y ServicioContrataciones.
 */
class ServicioController extends Controller
{
    /**
     * Lista los servicios de instalacion activos e historicos con su cliente y plan.
     */
    public function index(Request $request): View
    {
        $busqueda = trim($request->query->getString('buscar'));
        $estado = EstadoServicio::tryFrom($request->query->getString('estado'));
        $planSeleccionado = filter_var($request->query->getString('plan'), FILTER_VALIDATE_INT) ?: 0;
        $idBuscado = filter_var(ltrim($busqueda, '#'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $servicios = Servicio::query()->with(['cliente', 'plan'])
            ->when($busqueda !== '', fn ($consulta) => $consulta->where(fn ($filtro) => $filtro
                ->whereLike('ipv4', "%{$busqueda}%")
                ->orWhereLike('mac', '%'.str_replace('-', ':', $busqueda).'%')
                ->orWhereLike('calle_instalacion', "%{$busqueda}%")
                ->orWhereLike('localidad_instalacion', "%{$busqueda}%")
                ->orWhereHas('cliente', fn ($cliente) => $cliente->whereLike('nombre_razon_social', "%{$busqueda}%"))
                ->when($idBuscado !== false, fn ($coincidencias) => $coincidencias->orWhere('id_servicio', $idBuscado))))
            ->when($estado, fn ($consulta) => $consulta->where('estado', $estado->value))
            ->when($planSeleccionado > 0, fn ($consulta) => $consulta->where('id_plan', $planSeleccionado))
            ->orderByDesc('id_servicio')->paginate(20)->withQueryString();
        $planes = Plan::orderBy('nombre')->get(['id_plan', 'nombre']);

        return view('servicios.index', compact('servicios', 'busqueda', 'estado', 'planes', 'planSeleccionado'));
    }

    /**
     * Muestra el formulario para asociar un nuevo servicio a un cliente y plan.
     */
    public function create(): View
    {
        return view('servicios.formulario', [
            'clientes' => Cliente::where('estado', 'activo')->orderBy('nombre_razon_social')->get(),
            'planes' => Plan::where('estado', 'activo')->orderBy('nombre')->get(),
        ]);
    }

    /**
     * Registra una nueva instalacion de internet asignando parametros de red y plan.
     */
    public function store(GuardarServicioRequest $request, ServicioContrataciones $dominio): RedirectResponse
    {
        $datos = $request->validated();
        $cliente = Cliente::findOrFail($datos['id_cliente']);
        $servicio = $dominio->crear($cliente, $datos);

        return redirect()->route('servicios.index')->with('exito', "Servicio #{$servicio->id_servicio} registrado.");
    }

    /**
     * Muestra el formulario para modificar datos tecnicos o domicilio de instalacion.
     */
    public function edit(Servicio $servicio): View
    {
        return view('servicios.formulario', [
            'servicio' => $servicio,
            'clientes' => Cliente::where('id_cliente', $servicio->id_cliente)->get(),
            'planes' => Plan::orderBy('nombre')->get(),
        ]);
    }

    /**
     * Actualiza la configuracion tecnica del servicio (IP, MAC, domicilio).
     */
    public function update(ActualizarServicioRequest $request, Servicio $servicio, ServicioContrataciones $dominio): RedirectResponse
    {
        $dominio->actualizar($servicio, $request->validated());

        return redirect()->route('servicios.index')->with('exito', 'Servicio actualizado correctamente.');
    }

    /**
     * Interrumpe temporalmente la prestacion tecnica del servicio.
     */
    public function suspender(Servicio $servicio, ServicioContrataciones $dominio): RedirectResponse
    {
        $dominio->suspender($servicio);

        return back()->with('exito', 'Servicio suspendido.');
    }

    /**
     * Restablece la conectividad del servicio tras la regularizacion de deuda o mantenimiento.
     */
    public function reactivar(Servicio $servicio, ServicioContrataciones $dominio): RedirectResponse
    {
        $dominio->reactivar($servicio);

        return back()->with('exito', 'Servicio reactivado.');
    }

    /**
     * Da de baja definitiva el servicio de internet.
     */
    public function destroy(Servicio $servicio, ServicioContrataciones $dominio): RedirectResponse
    {
        $dominio->darDeBajaPorMora($servicio);

        return back()->with('exito', 'Servicio dado de baja.');
    }
}
