<?php

use App\Http\Controllers\Web\AutenticacionController;
use App\Http\Controllers\Web\ClienteController;
use App\Http\Controllers\Web\CuentaCorrienteController;
use App\Http\Controllers\Web\CuentaReceptoraController;
use App\Http\Controllers\Web\CuotaController;
use App\Http\Controllers\Web\InicioController;
use App\Http\Controllers\Web\PagoController;
use App\Http\Controllers\Web\PlanController;
use App\Http\Controllers\Web\ServicioController;
use App\Http\Controllers\Web\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/iniciar-sesion', [AutenticacionController::class, 'create'])->name('sesion.crear');
    Route::post('/iniciar-sesion', [AutenticacionController::class, 'store'])->middleware('throttle:5,1')->name('sesion.guardar');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/', InicioController::class)->name('inicio');
    Route::post('/cerrar-sesion', [AutenticacionController::class, 'destroy'])->name('sesion.destruir');

    Route::middleware('accion:consultar_clientes')->group(function (): void {
        Route::get('/clientes', [ClienteController::class, 'index'])->name('clientes.index');
        Route::get('/clientes/{cliente}', [ClienteController::class, 'show'])->name('clientes.show');
    });
    Route::middleware('accion:gestionar_clientes')->group(function (): void {
        Route::get('/clientes-nuevo', [ClienteController::class, 'create'])->name('clientes.create');
        Route::post('/clientes', [ClienteController::class, 'store'])->name('clientes.store');
        Route::get('/clientes/{cliente}/editar', [ClienteController::class, 'edit'])->name('clientes.edit');
        Route::put('/clientes/{cliente}', [ClienteController::class, 'update'])->name('clientes.update');
        Route::delete('/clientes/{cliente}', [ClienteController::class, 'destroy'])->name('clientes.destroy');
    });

    Route::get('/planes', [PlanController::class, 'index'])->middleware('accion:consultar_planes')->name('planes.index');
    Route::middleware('accion:gestionar_planes')->group(function (): void {
        Route::get('/planes-nuevo', [PlanController::class, 'create'])->name('planes.create');
        Route::post('/planes', [PlanController::class, 'store'])->name('planes.store');
        Route::get('/planes/{plan}/editar', [PlanController::class, 'edit'])->name('planes.edit');
        Route::put('/planes/{plan}', [PlanController::class, 'update'])->name('planes.update');
        Route::delete('/planes/{plan}', [PlanController::class, 'destroy'])->name('planes.destroy');
    });

    Route::get('/servicios', [ServicioController::class, 'index'])->middleware('accion:consultar_servicios')->name('servicios.index');
    Route::middleware('accion:gestionar_servicios')->group(function (): void {
        Route::get('/servicios-nuevo', [ServicioController::class, 'create'])->name('servicios.create');
        Route::post('/servicios', [ServicioController::class, 'store'])->name('servicios.store');
        Route::get('/servicios/{servicio}/editar', [ServicioController::class, 'edit'])->name('servicios.edit');
        Route::put('/servicios/{servicio}', [ServicioController::class, 'update'])->name('servicios.update');
        Route::post('/servicios/{servicio}/suspender', [ServicioController::class, 'suspender'])->name('servicios.suspender');
        Route::post('/servicios/{servicio}/reactivar', [ServicioController::class, 'reactivar'])->name('servicios.reactivar');
        Route::delete('/servicios/{servicio}', [ServicioController::class, 'destroy'])->name('servicios.destroy');
    });

    Route::get('/clientes/{cliente}/cuenta', [CuentaCorrienteController::class, 'show'])->middleware('accion:consultar_cuentas')->name('cuentas.show');
    Route::post('/cuotas/generar', [CuotaController::class, 'store'])->middleware('accion:gestionar_cuentas')->name('cuotas.store');
    Route::post('/pagos', [PagoController::class, 'store'])->middleware('accion:gestionar_pagos')->name('pagos.store');

    Route::get('/cuentas-receptoras', [CuentaReceptoraController::class, 'index'])->middleware('accion:consultar_cuentas')->name('cuentas-receptoras.index');
    Route::post('/cuentas-receptoras', [CuentaReceptoraController::class, 'store'])->middleware('accion:gestionar_cuentas')->name('cuentas-receptoras.store');
    Route::put('/cuentas-receptoras/{cuentaReceptora}', [CuentaReceptoraController::class, 'update'])->middleware('accion:gestionar_cuentas')->name('cuentas-receptoras.update');

    Route::get('/usuarios', [UsuarioController::class, 'index'])->middleware('accion:gestionar_usuarios')->name('usuarios.index');
    Route::post('/usuarios', [UsuarioController::class, 'store'])->middleware('accion:gestionar_usuarios')->name('usuarios.store');
    Route::delete('/usuarios/{usuario}', [UsuarioController::class, 'destroy'])->middleware('accion:gestionar_usuarios')->name('usuarios.destroy');
});
