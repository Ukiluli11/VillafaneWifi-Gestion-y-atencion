@extends('layouts.panel')
@section('titulo', 'Métricas y Dashboard Ejecutivo')
@section('contenido')
@php
    $hoy = now('America/Argentina/Buenos_Aires')->toDateString();
@endphp
<div class="encabezado">
    <div>
        <p class="sobrelinea">Reportes y métricas</p>
        <h1>Dashboard Ejecutivo y Métricas</h1>
        <p>Visión global del ISP Villafañe Wifi: clientes, recaudación, salud financiera y soporte técnico en tiempo real.</p>
    </div>
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <a class="boton" href="{{ route('reportes.morosidad') }}">📊 Reporte Morosidad</a>
        <a class="boton" href="{{ route('reportes.ingresos') }}">💵 Reporte Ingresos</a>
        <a class="boton" href="{{ route('reportes.tickets') }}">🎫 Reporte Tickets</a>
    </div>
</div>

<nav style="display: flex; gap: 10px; margin-bottom: 24px; border-bottom: 1px solid var(--borde); padding-bottom: 12px; overflow-x: auto;">
    <a href="{{ route('reportes.dashboard') }}" style="padding: 8px 16px; border-radius: 8px; background: #00838F; color: #fff; text-decoration: none; font-weight: 600; font-size: 0.88rem;">Tablero Principal</a>
    <a href="{{ route('reportes.morosidad') }}" style="padding: 8px 16px; border-radius: 8px; background: var(--blanco); border: 1px solid var(--borde); color: var(--texto); text-decoration: none; font-weight: 500; font-size: 0.88rem;">Clientes y Morosidad</a>
    <a href="{{ route('reportes.ingresos') }}" style="padding: 8px 16px; border-radius: 8px; background: var(--blanco); border: 1px solid var(--borde); color: var(--texto); text-decoration: none; font-weight: 500; font-size: 0.88rem;">Cobranzas e Ingresos</a>
    <a href="{{ route('reportes.tickets') }}" style="padding: 8px 16px; border-radius: 8px; background: var(--blanco); border: 1px solid var(--borde); color: var(--texto); text-decoration: none; font-weight: 500; font-size: 0.88rem;">Rendimiento de Tickets</a>
    <a href="{{ route('reportes.alertas') }}" style="padding: 8px 16px; border-radius: 8px; background: var(--blanco); border: 1px solid var(--borde); color: var(--texto); text-decoration: none; font-weight: 500; font-size: 0.88rem;">Alertas de Vencimiento</a>
</nav>

{{-- Sección de KPIs Ejecutivos Principales --}}
<section class="metricas" aria-label="Indicadores clave de rendimiento">
    <article class="metrica" style="border-left: 4px solid #00838F;">
        <div class="metrica-cabecera">
            <span>CLIENTES ACTIVOS</span>
            <span class="metrica-icono" style="background: #E0F7FA; color: #00838F;">CL</span>
        </div>
        <strong>{{ $metricas['clientes_activos'] }}</strong>
        <small>{{ $metricas['servicios_activos'] }} servicios activos en {{ $metricas['total_clientes'] }} registrados</small>
    </article>

    <article class="metrica" style="border-left: 4px solid #C62828;">
        <div class="metrica-cabecera">
            <span>MOROSIDAD EN CARTERA</span>
            <span class="metrica-icono" style="background: #FFEBEE; color: #C62828;">MR</span>
        </div>
        <strong style="color: #C62828;">{{ $metricas['porcentaje_morosidad'] }}%</strong>
        <small>{{ $metricas['clientes_en_mora'] }} clientes con deuda (${{ number_format($metricas['monto_total_deuda'], 2, ',', '.') }})</small>
    </article>

    <article class="metrica" style="border-left: 4px solid #2E7D32;">
        <div class="metrica-cabecera">
            <span>RECAUDACIÓN DEL MES</span>
            <span class="metrica-icono" style="background: #E8F5E9; color: #2E7D32;">$</span>
        </div>
        <strong style="color: #2E7D32;">${{ number_format($metricas['recaudacion_mes_actual'], 2, ',', '.') }}</strong>
        <small>
            @if ($metricas['variacion_recaudacion_pct'] >= 0)
                <span style="color: #2E7D32; font-weight: bold;">↑ +{{ $metricas['variacion_recaudacion_pct'] }}%</span> vs mes anterior
            @else
                <span style="color: #C62828; font-weight: bold;">↓ {{ $metricas['variacion_recaudacion_pct'] }}%</span> vs mes anterior
            @endif
        </small>
    </article>

    <article class="metrica" style="border-left: 4px solid #F57F17;">
        <div class="metrica-cabecera">
            <span>SOPORTE Y TICKETS</span>
            <span class="metrica-icono" style="background: #FFF9C4; color: #F57F17;">TK</span>
        </div>
        <strong>{{ $metricas['tickets_pendientes'] }} pend.</strong>
        <small>{{ $metricas['tickets_en_proceso'] }} en proceso · {{ $metricas['tickets_resueltos'] }} resueltos</small>
    </article>
    <article class="metrica" style="border-left: 4px solid #00838F;">
        <div class="metrica-cabecera">
            <span>COMPROBANTES POR VALIDAR</span>
            <span class="metrica-icono">CP</span>
        </div>
        <strong>{{ $metricas['comprobantes_pendientes'] }}</strong>
        <small>Pendientes de revisión</small>
    </article>
</section>

{{-- Sección de Gráficos Interactivos (Chart.js) --}}
<section style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 20px; margin-bottom: 24px;">
    {{-- Gráfico 1: Evolución de Ingresos --}}
    <article class="tarjeta" style="margin: 0;">
        <div class="tarjeta-cabecera">
            <div>
                <h2 style="font-size: 1.1rem; margin: 0;">Evolución de Ingresos</h2>
                <p>Cobranzas registradas e imputadas (últimos 6 meses)</p>
            </div>
            <span style="font-size: 0.8rem; font-weight: 600; background: #E8F5E9; color: #2E7D32; padding: 4px 8px; border-radius: 6px;">Histórico</span>
        </div>
        <div style="position: relative; height: 240px;">
            <canvas id="graficoIngresos"></canvas>
        </div>
    </article>

    {{-- Gráfico 2: Clientes por Plan --}}
    <article class="tarjeta" style="margin: 0;">
        <div class="tarjeta-cabecera">
            <div>
                <h2 style="font-size: 1.1rem; margin: 0;">Distribución de Planes</h2>
                <p>Conexiones activas según velocidad y tarifa</p>
            </div>
            <span style="font-size: 0.8rem; font-weight: 600; background: #E0F7FA; color: #00838F; padding: 4px 8px; border-radius: 6px;">Comercial</span>
        </div>
        <div style="position: relative; height: 240px;">
            <canvas id="graficoPlanes"></canvas>
        </div>
    </article>

    {{-- Gráfico 3: Estado de Tickets --}}
    <article class="tarjeta" style="margin: 0;">
        <div class="tarjeta-cabecera">
            <div>
                <h2 style="font-size: 1.1rem; margin: 0;">Estado de Tickets de Soporte</h2>
                <p>Carga técnica acumulada en la plataforma</p>
            </div>
            <span style="font-size: 0.8rem; font-weight: 600; background: #FFF9C4; color: #F57F17; padding: 4px 8px; border-radius: 6px;">Operativo</span>
        </div>
        <div style="position: relative; height: 240px;">
            <canvas id="graficoTickets"></canvas>
        </div>
    </article>
</section>

{{-- Sección Inferior: Alertas Inminentes y Acceso Rápido a Auditorías --}}
<section style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; align-items: start;">
    <article class="tarjeta" style="margin: 0;">
        <div class="tarjeta-cabecera">
            <div>
                <h2 style="font-size: 1.1rem; margin: 0;">Alertas de Vencimiento y Morosidad</h2>
                <p>Cuotas vencidas o con vencimiento en los próximos 5 días que requieren gestión</p>
            </div>
            <a class="boton" href="{{ route('reportes.alertas') }}" style="font-size: 0.8rem; padding: 6px 12px;">Ver todas las alertas</a>
        </div>

        @if ($metricas['alertas_vencimiento']->isEmpty())
            <p style="padding: 16px; color: var(--texto-suave); text-align: center;">🎉 No se registran cuotas vencidas ni próximas a vencer.</p>
        @else
            <div class="tabla-contenedor" style="margin: 0;">
                <table class="tabla" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Plan</th>
                            <th>Vencimiento</th>
                            <th>Monto</th>
                            <th>Estado</th>
                            <th style="text-align: right;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($metricas['alertas_vencimiento'] as $alerta)
                            @php
                                $esVencida = $alerta->fecha_vencimiento->toDateString() < $hoy;
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $alerta->servicio?->cliente?->nombre_razon_social ?? 'Cliente' }}</strong><br>
                                    <small style="color: var(--texto-suave);">Tel: {{ $alerta->servicio?->cliente?->telefono_whatsapp ?? 'Sin número' }}</small>
                                </td>
                                <td>{{ $alerta->servicio?->plan?->nombre ?? 'Plan' }}</td>
                                <td>
                                    <span style="{{ $esVencida ? 'color: #C62828; font-weight: bold;' : 'color: #E65100;' }}">
                                        {{ $alerta->fecha_vencimiento->format('d/m/Y') }}
                                    </span>
                                </td>
                                <td><strong>${{ number_format((float) $alerta->monto, 2, ',', '.') }}</strong></td>
                                <td>
                                    @if ($esVencida)
                                        <span style="background: #FFEBEE; color: #C62828; padding: 3px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 700;">VENCIDA</span>
                                    @else
                                        <span style="background: #FFF9C4; color: #F57F17; padding: 3px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 700;">PRÓXIMA</span>
                                    @endif
                                </td>
                                <td style="text-align: right;">
                                    <form method="POST" action="{{ route('reportes.alertas.notificar') }}" style="display: inline;">
                                        @csrf
                                        <input type="hidden" name="id_cuota" value="{{ $alerta->id_cuota }}">
                                        <button type="submit" class="boton" style="padding: 4px 10px; font-size: 0.78rem; background: #2E7D32; color: #fff;">
                                            💬 Enviar WhatsApp
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </article>

    @if ($navegacionPermitida['exportar_reportes'] ?? false)
    {{-- Tarjeta de Accesos Rápidos a Exportaciones Oficiales --}}
    <article class="tarjeta panel-estado" style="margin: 0; background: linear-gradient(145deg, #006064, #00838F);">
        <div>
            <span class="sello" style="background: rgba(255,255,255,0.2); color: #fff;">Exportación Oficial</span>
            <h2 style="margin-top: 18px; color: #fff; font-size: 1.25rem;">Reportes para Descarga</h2>
            <p style="color: #E0F7FA; font-size: 0.85rem; margin-top: 8px;">
                Descargue las planillas contables estructuradas para Microsoft Excel o genere informes PDF con membrete institucional formal de Villafañe Wifi.
            </p>
            <div style="margin-top: 20px; display: flex; flex-direction: column; gap: 10px;">
                <a href="{{ route('reportes.exportar', ['tipo' => 'morosidad', 'formato' => 'xlsx']) }}" class="boton" style="background: #fff; color: #006064; text-align: center; font-weight: 600;">
                    📥 Exportar Morosidad (Excel)
                </a>
                <a href="{{ route('reportes.exportar', ['tipo' => 'ingresos', 'formato' => 'xlsx']) }}" class="boton" style="background: #fff; color: #006064; text-align: center; font-weight: 600;">
                    📥 Exportar Ingresos (Excel)
                </a>
                <a href="{{ route('reportes.exportar', ['tipo' => 'tickets', 'formato' => 'pdf']) }}" target="_blank" class="boton" style="background: rgba(255,255,255,0.2); color: #fff; border: 1px solid rgba(255,255,255,0.4); text-align: center;">
                    🖨️ Descargar Tickets (PDF)
                </a>
            </div>
        </div>
        <small style="color: #B2EBF2; margin-top: 16px; display: block;">Villafañe Wifi · Sistema de Gestión v2.0</small>
    </article>
    @endif
</section>

{{-- Integración de Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const isDark = document.documentElement.classList.contains('tema-oscuro');
    const textColor = isDark ? '#E0E0E0' : '#455A64';
    const gridColor = isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.06)';

    // Gráfico 1: Evolución de Ingresos
    const ingresosData = @json($metricas['serie_ingresos']);
    const ctxIngresos = document.getElementById('graficoIngresos');
    if (ctxIngresos && window.Chart) {
        new Chart(ctxIngresos, {
            type: 'bar',
            data: {
                labels: ingresosData.map(i => i.mes),
                datasets: [{
                    label: 'Recaudación ($)',
                    data: ingresosData.map(i => i.total),
                    backgroundColor: 'rgba(46, 125, 50, 0.8)',
                    borderColor: '#2E7D32',
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { color: textColor }, grid: { display: false } },
                    y: { ticks: { color: textColor }, grid: { color: gridColor } }
                }
            }
        });
    }

    // Gráfico 2: Planes
    const planesData = @json($metricas['distribucion_planes']);
    const ctxPlanes = document.getElementById('graficoPlanes');
    if (ctxPlanes && window.Chart) {
        new Chart(ctxPlanes, {
            type: 'doughnut',
            data: {
                labels: planesData.map(p => p.nombre),
                datasets: [{
                    data: planesData.map(p => p.cantidad),
                    backgroundColor: ['#00838F', '#00ACC1', '#4DD0E1', '#80DEEA', '#B2EBF2', '#004D40']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right', labels: { color: textColor, font: { size: 11 } } }
                }
            }
        });
    }

    // Gráfico 3: Tickets
    const ctxTickets = document.getElementById('graficoTickets');
    if (ctxTickets && window.Chart) {
        new Chart(ctxTickets, {
            type: 'doughnut',
            data: {
                labels: ['Pendientes', 'En Proceso', 'Resueltos', 'Cerrados'],
                datasets: [{
                    data: [
                        {{ $metricas['tickets_pendientes'] }},
                        {{ $metricas['tickets_en_proceso'] }},
                        {{ $metricas['tickets_resueltos'] }},
                        {{ $metricas['tickets_cerrados'] }}
                    ],
                    backgroundColor: ['#F57F17', '#1976D2', '#2E7D32', '#546E7A']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right', labels: { color: textColor, font: { size: 11 } } }
                }
            }
        });
    }
});
</script>
@endsection
