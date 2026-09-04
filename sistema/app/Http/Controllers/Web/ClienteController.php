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

class ClienteController extends Controller
{
    public function index(Request $request, ServicioClientes $servicio): View
    {
        return view('clientes.index', ['clientes' => $servicio->buscar($request->string('buscar')->toString())]);
    }

    public function create(): View
    {
        return view('clientes.formulario');
    }

    public function store(GuardarClienteRequest $request, ServicioClientes $servicio): RedirectResponse
    {
        $cliente = $servicio->crear($request->validated());

        return redirect()->route('clientes.show', $cliente)->with('exito', 'Cliente registrado correctamente.');
    }

    public function show(Cliente $cliente): View
    {
        return view('clientes.detalle', ['cliente' => $cliente->load(['servicios.plan'])]);
    }

    public function edit(Cliente $cliente): View
    {
        return view('clientes.formulario', compact('cliente'));
    }

    public function update(ActualizarClienteRequest $request, Cliente $cliente, ServicioClientes $servicio): RedirectResponse
    {
        $servicio->actualizar($cliente, $request->validated());

        return redirect()->route('clientes.show', $cliente)->with('exito', 'Cliente actualizado correctamente.');
    }

    public function destroy(Cliente $cliente, ServicioClientes $servicio): RedirectResponse
    {
        $servicio->darDeBaja($cliente);

        return redirect()->route('clientes.index')->with('exito', 'Cliente y servicios dados de baja.');
    }
}
