<?php

namespace App\Http\Controllers\Web;

use App\Dominio\ServicioClientes;
use App\Http\Controllers\Controller;
use App\Http\Requests\ActualizarClienteRequest;
use App\Http\Requests\GuardarClienteRequest;
use App\Models\Cliente;
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
        return view('clientes.index', ['clientes' => $servicio->buscar($request->string('buscar')->toString())]);
    }

    /**
     * Muestra el formulario para registrar un nuevo cliente.
     */
    public function create(): View
    {
        return view('clientes.formulario');
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

    /**
     * Da de baja al cliente y suspende administrativamente sus servicios de conexion.
     */
    public function destroy(Cliente $cliente, ServicioClientes $servicio): RedirectResponse
    {
        $servicio->darDeBaja($cliente);

        return redirect()->route('clientes.index')->with('exito', 'Cliente y servicios dados de baja.');
    }
}
