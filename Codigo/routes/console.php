<?php

// Registra y programa las tareas de facturación, asignación de tickets y avisos de vencimiento.

use App\Dominio\ServicioFacturacion;
use App\Dominio\ServicioTickets;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('cuotas:generar-mensuales', function (ServicioFacturacion $facturacion): int {
    $periodo = CarbonImmutable::now('America/Argentina/Buenos_Aires')->format('Y-m');
    $creadas = $facturacion->generarParaServiciosActivos($periodo);
    $this->info("Cuotas creadas para {$periodo}: {$creadas}.");

    return Command::SUCCESS;
})->purpose('Genera las cuotas del mes en curso para los servicios activos');

Schedule::command('cuotas:generar-mensuales')
    ->dailyAt('06:00')
    ->timezone('America/Argentina/Buenos_Aires')
    ->withoutOverlapping();

Artisan::command('tickets:asignar-pendientes', function (ServicioTickets $tickets): int {
    $this->info('Tickets asignados automáticamente: '.$tickets->asignarPendientes().'.');

    return Command::SUCCESS;
})->purpose('Asigna los tickets pendientes por orden de llegada a técnicos disponibles');

Schedule::command('tickets:asignar-pendientes')->everyMinute()->withoutOverlapping();

Schedule::command('whatsapp:notificar-vencimientos')
    ->dailyAt('09:00')
    ->timezone('America/Argentina/Buenos_Aires')
    ->withoutOverlapping();
