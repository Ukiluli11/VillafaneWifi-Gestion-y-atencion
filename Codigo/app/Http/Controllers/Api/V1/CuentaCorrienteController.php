<?php

namespace App\Http\Controllers\Api\V1;

use App\Dominio\ServicioCuentaCorriente;
use App\Dominio\ServicioFacturacion;
use App\Http\Controllers\Controller;
use App\Http\Resources\CuotaResource;
use App\Models\Cliente;
use Illuminate\Http\JsonResponse;

class CuentaCorrienteController extends Controller
{
    public function show(
        Cliente $cliente,
        ServicioFacturacion $facturacion,
        ServicioCuentaCorriente $cuentaCorriente,
    ): JsonResponse {
        $facturacion->actualizarEstadosVencidos();

        return response()->json([
            'cliente' => ['id_cliente' => $cliente->id_cliente, 'nombre_razon_social' => $cliente->nombre_razon_social],
            'resumen' => $cuentaCorriente->resumir($cliente),
            'cuotas' => CuotaResource::collection($cuentaCorriente->cuotasDelCliente($cliente)),
        ]);
    }
}
