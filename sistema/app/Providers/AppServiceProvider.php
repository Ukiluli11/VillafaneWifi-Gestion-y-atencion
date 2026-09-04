<?php

namespace App\Providers;

use App\Autorizacion\ServicioAutorizacion;
use App\Enums\AccionSistema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as Vista;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(ServicioAutorizacion $servicioAutorizacion): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());

        View::composer('*', function (Vista $vista) use ($servicioAutorizacion): void {
            $usuario = Auth::user();
            $puede = fn (AccionSistema $accion): bool => $usuario !== null
                && $servicioAutorizacion->puede($usuario, $accion);

            $vista->with('navegacionPermitida', [
                'clientes' => $puede(AccionSistema::ConsultarClientes),
                'gestionar_clientes' => $puede(AccionSistema::GestionarClientes),
                'planes' => $puede(AccionSistema::ConsultarPlanes),
                'gestionar_planes' => $puede(AccionSistema::GestionarPlanes),
                'servicios' => $puede(AccionSistema::ConsultarServicios),
                'gestionar_servicios' => $puede(AccionSistema::GestionarServicios),
                'cobranza' => $puede(AccionSistema::ConsultarCuentas),
                'gestionar_cobranza' => $puede(AccionSistema::GestionarCuentas),
                'gestionar_pagos' => $puede(AccionSistema::GestionarPagos),
                'usuarios' => $puede(AccionSistema::GestionarUsuarios),
            ]);
        });
    }
}
