<?php

namespace App\Providers;

use App\Autorizacion\ServicioAutorizacion;
use App\Enums\AccionSistema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as Vista;
use RuntimeException;
use Throwable;

/**
 * Registra y configura servicios de la aplicación durante el arranque de Laravel.
 */
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

        Event::listen(DiagnosingHealth::class, function (): void {
            try {
                DB::select('SELECT 1');
            } catch (Throwable $error) {
                throw new RuntimeException('La base de datos no está disponible.', previous: $error);
            }
        });

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
                'conversaciones' => $puede(AccionSistema::ConsultarConversaciones),
                'simulador_whatsapp' => $puede(AccionSistema::GestionarConversaciones) && config('services.whatsapp.modo_simulacion'),
                'comprobantes' => $puede(AccionSistema::ConsultarComprobantes),
                'gestionar_comprobantes' => $puede(AccionSistema::GestionarComprobantes),
                'tickets' => $puede(AccionSistema::ConsultarTickets),
                'gestionar_tickets' => $puede(AccionSistema::GestionarTickets),
                'reportes' => $puede(AccionSistema::ConsultarReportes),
                'exportar_reportes' => $puede(AccionSistema::ExportarReportes),
                'usuarios' => $puede(AccionSistema::GestionarUsuarios),
            ]);
        });
    }
}
