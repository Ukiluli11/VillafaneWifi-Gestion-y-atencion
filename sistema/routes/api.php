<?php

use App\Http\Controllers\Api\V1\ClienteController;
use App\Http\Controllers\Api\V1\CuentaCorrienteController;
use App\Http\Controllers\Api\V1\PlanController;
use App\Http\Controllers\Api\V1\ServicioController;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(AuthenticateWithBasicAuth::using('web', 'nombre_usuario'))->group(function (): void {
    Route::get('/clientes', [ClienteController::class, 'index'])->middleware('accion:consultar_clientes');
    Route::get('/clientes/{cliente}', [ClienteController::class, 'show'])->middleware('accion:consultar_clientes');
    Route::post('/clientes', [ClienteController::class, 'store'])->middleware('accion:gestionar_clientes');
    Route::put('/clientes/{cliente}', [ClienteController::class, 'update'])->middleware('accion:gestionar_clientes');
    Route::delete('/clientes/{cliente}', [ClienteController::class, 'destroy'])->middleware('accion:gestionar_clientes');

    Route::get('/planes', [PlanController::class, 'index'])->middleware('accion:consultar_planes');
    Route::get('/planes/{plan}', [PlanController::class, 'show'])->middleware('accion:consultar_planes');
    Route::post('/planes', [PlanController::class, 'store'])->middleware('accion:gestionar_planes');
    Route::put('/planes/{plan}', [PlanController::class, 'update'])->middleware('accion:gestionar_planes');
    Route::delete('/planes/{plan}', [PlanController::class, 'destroy'])->middleware('accion:gestionar_planes');

    Route::get('/servicios', [ServicioController::class, 'index'])->middleware('accion:consultar_servicios');
    Route::get('/servicios/{servicio}', [ServicioController::class, 'show'])->middleware('accion:consultar_servicios');
    Route::post('/servicios', [ServicioController::class, 'store'])->middleware('accion:gestionar_servicios');
    Route::put('/servicios/{servicio}', [ServicioController::class, 'update'])->middleware('accion:gestionar_servicios');
    Route::delete('/servicios/{servicio}', [ServicioController::class, 'destroy'])->middleware('accion:gestionar_servicios');

    Route::get('/clientes/{cliente}/cuenta', [CuentaCorrienteController::class, 'show'])->middleware('accion:consultar_cuentas');
});
