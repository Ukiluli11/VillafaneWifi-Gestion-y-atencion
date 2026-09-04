<?php

namespace App\Http\Controllers\Api\V1;

use App\Dominio\ServicioClientes;
use App\Http\Controllers\Controller;
use App\Http\Requests\ActualizarClienteRequest;
use App\Http\Requests\GuardarClienteRequest;
use App\Http\Resources\ClienteResource;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ClienteController extends Controller
{
    public function index(Request $request, ServicioClientes $servicio): AnonymousResourceCollection
    {
        return ClienteResource::collection($servicio->buscar($request->string('buscar')->toString()));
    }

    public function store(GuardarClienteRequest $request, ServicioClientes $servicio): ClienteResource
    {
        return new ClienteResource($servicio->crear($request->validated()));
    }

    public function show(Cliente $cliente): ClienteResource
    {
        return new ClienteResource($cliente->load(['servicios.plan']));
    }

    public function update(ActualizarClienteRequest $request, Cliente $cliente, ServicioClientes $servicio): ClienteResource
    {
        return new ClienteResource($servicio->actualizar($cliente, $request->validated()));
    }

    public function destroy(Cliente $cliente, ServicioClientes $servicio): Response
    {
        $servicio->darDeBaja($cliente);

        return response()->noContent();
    }
}
