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
    public function index(): View
    {
        return view('servicios.index', ['servicios' => Servicio::with(['cliente', 'plan'])->orderByDesc('id_servicio')->paginate(20)]);
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
    public function destroy(Servicio $servicio): RedirectResponse
    {
        $servicio->update(['estado' => 'baja']);

        return back()->with('exito', 'Servicio dado de baja.');
    }
}
