<?php

namespace App\Http\Controllers\Api\V1;

use App\Dominio\ServicioPlanes;
use App\Http\Controllers\Controller;
use App\Http\Requests\ActualizarPlanRequest;
use App\Http\Requests\GuardarPlanRequest;
use App\Http\Resources\PlanResource;
use App\Models\Plan;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class PlanController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return PlanResource::collection(Plan::orderBy('nombre')->paginate(20));
    }

    public function store(GuardarPlanRequest $request, ServicioPlanes $servicio): PlanResource
    {
        return new PlanResource($servicio->crear($request->validated()));
    }

    public function show(Plan $plan): PlanResource
    {
        return new PlanResource($plan);
    }

    public function update(ActualizarPlanRequest $request, Plan $plan, ServicioPlanes $servicio): PlanResource
    {
        return new PlanResource($servicio->actualizar($plan, $request->validated()));
    }

    public function destroy(Plan $plan, ServicioPlanes $servicio): Response
    {
        $servicio->desactivar($plan);

        return response()->noContent();
    }
}
