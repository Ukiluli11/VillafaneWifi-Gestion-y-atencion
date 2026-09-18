<?php

namespace App\Providers;

use App\Autorizacion\ServicioAutorizacion;
use App\Contratos\ClasificadorIntencion;
use App\Contratos\DescargadorArchivosWhatsapp;
use App\Contratos\PuertaEnlaceWhatsapp;
use App\Enums\AccionSistema;
use App\Infraestructura\InteligenciaArtificial\ClasificadorIntencionGemini;
use App\Infraestructura\Whatsapp\ClienteMetaWhatsapp;
use App\Infraestructura\Whatsapp\DescargadorArchivosMetaWhatsapp;
use App\Infraestructura\Whatsapp\DescargadorArchivosWhatsappSimulado;
use App\Infraestructura\Whatsapp\PuertaEnlaceWhatsappSimulada;
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
        $this->app->bind(
            PuertaEnlaceWhatsapp::class,
            fn () => config('services.whatsapp.modo_simulacion')
                ? new PuertaEnlaceWhatsappSimulada
                : new ClienteMetaWhatsapp,
        );
        $this->app->bind(ClasificadorIntencion::class, ClasificadorIntencionGemini::class);
        $this->app->bind(
            DescargadorArchivosWhatsapp::class,
            fn () => config('services.whatsapp.modo_simulacion') || ! config('services.whatsapp.descargar_archivos')
                ? new DescargadorArchivosWhatsappSimulado
                : new DescargadorArchivosMetaWhatsapp,
        );
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
                'comprobantes' => $puede(AccionSistema::ConsultarPagos),
                'conversaciones' => $puede(AccionSistema::ConsultarConversaciones),
                'gestionar_conversaciones' => $puede(AccionSistema::GestionarConversaciones),
                'usuarios' => $puede(AccionSistema::GestionarUsuarios),
            ]);
        });
    }
}
