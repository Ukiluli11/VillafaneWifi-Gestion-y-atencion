<?php

namespace App\Http\Controllers\Api\V1;

use App\Dominio\ServicioContrataciones;
use App\Http\Controllers\Controller;
use App\Http\Requests\ActualizarServicioRequest;
use App\Http\Requests\GuardarServicioRequest;
use App\Http\Resources\ServicioResource;
use App\Models\Cliente;
use App\Models\Servicio;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ServicioController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return ServicioResource::collection(Servicio::with(['cliente', 'plan'])->orderByDesc('id_servicio')->paginate(20));
    }

    public function store(GuardarServicioRequest $request, ServicioContrataciones $dominio): ServicioResource
    {
        $datos = $request->validated();
        $servicio = $dominio->crear(Cliente::findOrFail($datos['id_cliente']), $datos);

        return new ServicioResource($servicio->load(['cliente', 'plan']));
    }

    public function show(Servicio $servicio): ServicioResource
    {
        return new ServicioResource($servicio->load(['cliente', 'plan']));
    }

    public function update(ActualizarServicioRequest $request, Servicio $servicio, ServicioContrataciones $dominio): ServicioResource
    {
        return new ServicioResource($dominio->actualizar($servicio, $request->validated())->load(['cliente', 'plan']));
    }

    public function destroy(Servicio $servicio): Response
    {
        $servicio->update(['estado' => 'baja']);

        return response()->noContent();
    }
}
