@extends('layouts.panel')
@section('titulo', 'Reporte Financiero de Ingresos')
@section('contenido')
<div class="encabezado">
    <div>
        <p class="sobrelinea">Reportes y métricas</p>
        <h1>Reporte Financiero de Ingresos</h1>
        <p>Control de cobranzas, imputaciones de pagos y auditoría contable por período.</p>
    </div>
    @if ($navegacionPermitida['exportar_reportes'] ?? false)
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <a class="boton" href="{{ route('reportes.exportar', ['tipo' => 'ingresos', 'formato' => 'xlsx'] + request()->all()) }}">
            📥 Exportar Excel (.xlsx)
        </a>
        <a class="boton" target="_blank" href="{{ route('reportes.exportar', ['tipo' => 'ingresos', 'formato' => 'pdf'] + request()->all()) }}" style="background: #00838F; color: #fff;">
            🖨️ Descargar PDF
        </a>
    </div>
    @endif
</div>

<nav style="display: flex; gap: 10px; margin-bottom: 24px; border-bottom: 1px solid var(--borde); padding-bottom: 12px; overflow-x: auto;">
    <a href="{{ route('reportes.dashboard') }}" style="padding: 8px 16px; border-radius: 8px; background: var(--blanco); border: 1px solid var(--borde); color: var(--texto); text-decoration: none; font-weight: 500; font-size: 0.88rem;">Tablero Principal</a>
    <a href="{{ route('reportes.morosidad') }}" style="padding: 8px 16px; border-radius: 8px; background: var(--blanco); border: 1px solid var(--borde); color: var(--texto); text-decoration: none; font-weight: 500; font-size: 0.88rem;">Clientes y Morosidad</a>
    <a href="{{ route('reportes.ingresos') }}" style="padding: 8px 16px; border-radius: 8px; background: #00838F; color: #fff; text-decoration: none; font-weight: 600; font-size: 0.88rem;">Cobranzas e Ingresos</a>
    <a href="{{ route('reportes.tickets') }}" style="padding: 8px 16px; border-radius: 8px; background: var(--blanco); border: 1px solid var(--borde); color: var(--texto); text-decoration: none; font-weight: 500; font-size: 0.88rem;">Rendimiento de Tickets</a>
    <a href="{{ route('reportes.alertas') }}" style="padding: 8px 16px; border-radius: 8px; background: var(--blanco); border: 1px solid var(--borde); color: var(--texto); text-decoration: none; font-weight: 500; font-size: 0.88rem;">Alertas de Vencimiento</a>
</nav>

{{-- KPIs de Recaudación --}}
<section class="metricas" aria-label="Resumen de ingresos">
    <article class="metrica" style="border-left: 4px solid #2E7D32;">
        <div class="metrica-cabecera"><span>TOTAL RECAUDADO</span><span class="metrica-icono" style="background: #E8F5E9; color: #2E7D32;">$</span></div>
        <strong style="color: #2E7D32;">${{ number_format($resumen['total_recaudado'], 2, ',', '.') }}</strong>
        <small>ingresos netos en el período</small>
    </article>
    <article class="metrica">
        <div class="metrica-cabecera"><span>CANTIDAD DE COBROS</span><span class="metrica-icono">PG</span></div>
        <strong>{{ $resumen['cantidad_pagos'] }}</strong>
        <small>pagos registrados e imputados</small>
    </article>
    <article class="metrica">
        <div class="metrica-cabecera"><span>TICKET PROMEDIO</span><span class="metrica-icono">AVG</span></div>
        <strong>${{ number_format($resumen['ticket_promedio'], 2, ',', '.') }}</strong>
        <small>importe promedio por cobranza</small>
    </article>
    <article class="metrica">
        <div class="metrica-cabecera"><span>PERÍODO SELECCIONADO</span><span class="metrica-icono">📅</span></div>
        <strong style="font-size: 1.15rem; margin-top: 20px;">{{ date('d/m/Y', strtotime($resumen['desde'])) }}</strong>
        <small>al {{ date('d/m/Y', strtotime($resumen['hasta'])) }}</small>
    </article>
</section>

{{-- Desglose por Medio de Pago --}}
@if (!empty($resumen['desglose_medios']))
    <article class="tarjeta" style="margin-bottom: 20px;">
        <div class="tarjeta-cabecera">
            <div>
                <h2 style="font-size: 1.1rem; margin: 0;">Distribución por Medio de Pago</h2>
                <p>Composición de los ingresos según canal de percepción</p>
            </div>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
            @foreach ($resumen['desglose_medios'] as $medio)
                <div style="padding: 14px; border-radius: 12px; background: var(--fondo); border: 1px solid var(--borde);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <span style="font-weight: 700; font-size: 0.9rem; text-transform: capitalize;">{{ str_replace('_', ' ', $medio['medio']) }}</span>
                        <span style="background: #E8F5E9; color: #2E7D32; font-size: 0.75rem; font-weight: 700; padding: 2px 6px; border-radius: 4px;">{{ $medio['porcentaje'] }}%</span>
                    </div>
                    <strong style="font-size: 1.2rem; color: #2E7D32;">${{ number_format($medio['subtotal'], 2, ',', '.') }}</strong>
                    <div style="margin-top: 4px; color: var(--texto-suave); font-size: 0.75rem;">{{ $medio['cantidad'] }} pagos procesados</div>
                </div>
            @endforeach
        </div>
    </article>
@endif

{{-- Filtros dinámicos por rango de fechas y cuentas --}}
<article class="tarjeta" style="margin-bottom: 20px;">
    <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px;">
        @foreach ($periodos as $nombre => [$desde, $hasta])
            <a class="boton" href="{{ route('reportes.ingresos', ['fecha_desde' => $desde, 'fecha_hasta' => $hasta, 'medio_pago' => $filtros['medio_pago'], 'id_cuenta' => $filtros['id_cuenta']]) }}" style="background: transparent; border: 1px solid var(--borde); color: var(--texto);">
                {{ $nombre }}
            </a>
        @endforeach
    </div>
    <form method="GET" action="{{ route('reportes.ingresos') }}" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)) 120px 100px; gap: 14px; align-items: end;">
        <div>
            <label for="fecha_desde" style="font-size: 0.8rem; font-weight: 600; display: block; margin-bottom: 4px;">Fecha Desde</label>
            <input type="date" name="fecha_desde" id="fecha_desde" class="campo" value="{{ $filtros['fecha_desde'] }}" style="width: 100%; padding: 8px; border-radius: 8px; border: 1px solid var(--borde);">
        </div>

        <div>
            <label for="fecha_hasta" style="font-size: 0.8rem; font-weight: 600; display: block; margin-bottom: 4px;">Fecha Hasta</label>
            <input type="date" name="fecha_hasta" id="fecha_hasta" class="campo" value="{{ $filtros['fecha_hasta'] }}" style="width: 100%; padding: 8px; border-radius: 8px; border: 1px solid var(--borde);">
        </div>

        <div>
            <label for="medio_pago" style="font-size: 0.8rem; font-weight: 600; display: block; margin-bottom: 4px;">Medio de Pago</label>
            <select name="medio_pago" id="medio_pago" class="campo" style="width: 100%; padding: 8px; border-radius: 8px; border: 1px solid var(--borde);">
                <option value="todos">Todos los medios</option>
                @foreach ($medios as $medio)
                    <option value="{{ $medio->value }}" {{ ($filtros['medio_pago'] ?? '') === $medio->value ? 'selected' : '' }}>
                        {{ ucfirst(str_replace('_', ' ', $medio->value)) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="id_cuenta" style="font-size: 0.8rem; font-weight: 600; display: block; margin-bottom: 4px;">Cuenta Receptora</label>
            <select name="id_cuenta" id="id_cuenta" class="campo" style="width: 100%; padding: 8px; border-radius: 8px; border: 1px solid var(--borde);">
                <option value="">Todas las cuentas</option>
                @foreach ($cuentas as $cuenta)
                    <option value="{{ $cuenta->id_cuenta }}" {{ ($filtros['id_cuenta'] ?? '') == $cuenta->id_cuenta ? 'selected' : '' }}>
                        {{ $cuenta->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <button type="submit" class="boton" style="width: 100%; height: 38px;">🔍 Filtrar</button>
        </div>
        <div>
            <a href="{{ route('reportes.ingresos') }}" class="boton" style="width: 100%; height: 38px; background: transparent; border: 1px solid var(--borde); color: var(--texto); text-align: center; line-height: 22px;">Mes actual</a>
        </div>
    </form>
</article>

{{-- Tabla de Pagos --}}
<article class="tarjeta" style="margin: 0;">
    <div class="tarjeta-cabecera">
        <div>
            <h2 style="font-size: 1.1rem; margin: 0;">Detalle de Transacciones Imputadas</h2>
            <p>Listado de cobranzas registradas en el período</p>
        </div>
    </div>

    @if ($pagos->isEmpty())
        <p style="padding: 24px; text-align: center; color: var(--texto-suave);">No se registraron cobranzas bajo los filtros especificados.</p>
    @else
        <div class="tabla-contenedor" style="margin: 0;">
            <table class="tabla" style="width: 100%;">
                <thead>
                    <tr>
                        <th># Pago</th>
                        <th>Fecha</th>
                        <th>Cliente / Titular</th>
                        <th>Medio</th>
                        <th>Cuenta Receptora</th>
                        <th>Período / Cuota</th>
                        <th style="text-align: right;">Monto Imputado</th>
                        <th>Comprobante</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pagos as $pago)
                        @php
                            $primeraCuota = $pago->cuotas->first();
                            $cliente = $primeraCuota?->servicio?->cliente;
                        @endphp
                        <tr>
                            <td><strong>#{{ $pago->id_pago }}</strong></td>
                            <td>{{ $pago->fecha?->format('d/m/Y') }}</td>
                            <td>
                                @if ($cliente)
                                    <a href="{{ route('cuentas.show', $cliente->id_cliente) }}" style="color: inherit; font-weight: 600; text-decoration: underline;">
                                        {{ $cliente->nombre_razon_social }}
                                    </a>
                                    <br><small style="color: var(--texto-suave);">DNI: {{ $cliente->numero_documento }}</small>
                                @else
                                    <span style="color: var(--texto-suave);">Abonado general</span>
                                @endif
                            </td>
                            <td>
                                <span style="background: #E8F5E9; color: #2E7D32; padding: 3px 8px; border-radius: 4px; font-size: 0.78rem; font-weight: 600;">
                                    {{ ucfirst(str_replace('_', ' ', $pago->medio_pago instanceof \BackedEnum ? $pago->medio_pago->value : (string) $pago->medio_pago)) }}
                                </span>
                            </td>
                            <td>{{ $pago->cuenta?->nombre ?? 'Caja Central' }}</td>
                            <td>
                                @if ($primeraCuota)
                                    Cuota {{ $primeraCuota->fecha_vencimiento?->format('m/Y') ?? '-' }}
                                @else
                                    <span style="color: var(--texto-suave);">Cobro directo</span>
                                @endif
                            </td>
                            <td style="text-align: right; font-weight: 700; color: #2E7D32; font-size: 1rem;">
                                ${{ number_format((float) $pago->monto_total, 2, ',', '.') }}
                            </td>
                            <td>
                                @if ($pago->comprobante)
                                    <a href="{{ route('comprobantes.show', $pago->comprobante->id_comprobante) }}" style="color: #00838F; font-size: 0.8rem; font-weight: 600;">
                                        Comp #{{ $pago->comprobante->id_comprobante }} ↗
                                    </a>
                                @else
                                    <small style="color: var(--texto-suave);">Manual</small>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</article>
@endsection
