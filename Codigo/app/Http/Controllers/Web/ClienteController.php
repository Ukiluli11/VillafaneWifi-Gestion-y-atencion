<?php

namespace App\Http\Controllers\Web;

use App\Dominio\ServicioClientes;
use App\Enums\EstadoCliente;
use App\Enums\EstadoPlan;
use App\Enums\TipoCliente;
use App\Http\Controllers\Controller;
use App\Http\Requests\ActualizarClienteRequest;
use App\Http\Requests\GuardarClienteRequest;
use App\Models\Cliente;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * ClienteController: Controlador web para la gestion comercial y administrativa de clientes.
 * Corresponde a los casos de uso: 'Dar de alta cliente y servicio', 'Buscar cliente',
 * 'Editar datos de cliente' y 'Dar de baja cliente o servicio' (Modulo 1).
 * Conecta con el modelo Cliente (metodos darDeAlta(), darDeBaja()) y ServicioClientes.
 */
class ClienteController extends Controller
{
    /**
     * Lista los clientes registrados con soporte para busqueda por nombre o documento.
     */
    public function index(Request $request, ServicioClientes $servicio): View
    {
        $busqueda = trim($request->query->getString('buscar'));
        $estado = EstadoCliente::tryFrom($request->query->getString('estado'));
        $tipo = TipoCliente::tryFrom($request->query->getString('tipo'));
        $localidad = trim($request->query->getString('localidad'));
        $whatsapp = $request->query->getString('whatsapp');
        $conWhatsapp = match ($whatsapp) {
            'con_numero' => true,
            'sin_numero' => false,
            default => null,
        };
        $clientes = $servicio->buscar($busqueda, $estado, $tipo, $localidad, $conWhatsapp);
        $localidades = $servicio->localidades();

        return view('clientes.index', compact('clientes', 'busqueda', 'estado', 'tipo', 'localidad', 'localidades', 'conWhatsapp'));
    }

    /**
     * Muestra el formulario para registrar un nuevo cliente.
     */
    public function create(): View
    {
        return view('clientes.formulario', ['planes' => Plan::where('estado', EstadoPlan::Activo->value)->orderBy('nombre')->get()]);
    }

    /**
     * Procesa y persiste el alta de un nuevo cliente en el sistema.
     */
    public function store(GuardarClienteRequest $request, ServicioClientes $servicio): RedirectResponse
    {
        $cliente = $servicio->crear($request->validated());

        return redirect()->route('clientes.show', $cliente)->with('exito', 'Cliente registrado correctamente.');
    }

    /**
     * Muestra la ficha detallada del cliente con sus servicios y planes asociados.
     */
    public function show(Cliente $cliente): View
    {
        return view('clientes.detalle', ['cliente' => $cliente->load(['servicios.plan'])]);
    }

    /**
     * Muestra el formulario de edicion para los datos de contacto y domicilio del cliente.
     */
    public function edit(Cliente $cliente): View
    {
        return view('clientes.formulario', compact('cliente'));
    }

    /**
     * Actualiza la informacion personal y de contacto del cliente.
     */
    public function update(ActualizarClienteRequest $request, Cliente $cliente, ServicioClientes $servicio): RedirectResponse
    {
        $servicio->actualizar($cliente, $request->validated());

        return redirect()->route('clientes.show', $cliente)->with('exito', 'Cliente actualizado correctamente.');
    }

    public function configurarRecordatorios(Request $request, Cliente $cliente, ServicioClientes $servicio): RedirectResponse
    {
        $request->validate(['habilitados' => ['required', 'boolean']]);
        $habilitados = $request->boolean('habilitados');
        $servicio->configurarRecordatorios($cliente, $habilitados);

        return redirect()->route('clientes.show', $cliente)
            ->with('exito', $habilitados ? 'Recordatorios de cuotas habilitados.' : 'Recordatorios de cuotas desactivados.');
    }

    /**
     * Da de baja al cliente y suspende administrativamente sus servicios de conexion.
     */
    public function destroy(Cliente $cliente, ServicioClientes $servicio): RedirectResponse
    {
        $servicio->darDeBaja($cliente);

        return redirect()->route('clientes.index')->with('exito', 'Cliente y servicios dados de baja.');
    }
}
