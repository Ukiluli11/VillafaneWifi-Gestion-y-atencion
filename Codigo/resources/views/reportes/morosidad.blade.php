@extends('layouts.panel')
@section('titulo', 'Reporte de Clientes y Morosidad')
@section('contenido')
<div class="encabezado">
    <div>
        <p class="sobrelinea">Reportes y métricas</p>
        <h1>Reporte de Clientes al Día y en Mora</h1>
        <p>Auditoría de cartera de abonados, antigüedad de deuda y situación contable de cobranzas.</p>
    </div>
    @if ($navegacionPermitida['exportar_reportes'] ?? false)
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <a class="boton" href="{{ route('reportes.exportar', ['tipo' => 'morosidad', 'formato' => 'xlsx'] + request()->all()) }}">
            📥 Exportar Excel (.xlsx)
        </a>
        <a class="boton" target="_blank" href="{{ route('reportes.exportar', ['tipo' => 'morosidad', 'formato' => 'pdf'] + request()->all()) }}" style="background: var(--verde-950); color: #faf6ed;">
            🖨️ Descargar PDF
        </a>
    </div>
    @endif
</div>

<nav style="display: flex; gap: 10px; margin-bottom: 24px; border-bottom: 1px solid var(--borde); padding-bottom: 12px; overflow-x: auto;">
    <a href="{{ route('reportes.dashboard') }}" style="padding: 8px 16px; border-radius: 8px; background: var(--blanco); border: 1px solid var(--borde); color: var(--texto); text-decoration: none; font-weight: 500; font-size: 0.88rem;">Tablero Principal</a>
    <a href="{{ route('reportes.morosidad') }}" style="padding: 8px 16px; border-radius: 8px; background: var(--verde-950); color: #faf6ed; text-decoration: none; font-weight: 600; font-size: 0.88rem;">Clientes y Morosidad</a>
    <a href="{{ route('reportes.ingresos') }}" style="padding: 8px 16px; border-radius: 8px; background: var(--blanco); border: 1px solid var(--borde); color: var(--texto); text-decoration: none; font-weight: 500; font-size: 0.88rem;">Cobranzas e Ingresos</a>
    <a href="{{ route('reportes.tickets') }}" style="padding: 8px 16px; border-radius: 8px; background: var(--blanco); border: 1px solid var(--borde); color: var(--texto); text-decoration: none; font-weight: 500; font-size: 0.88rem;">Rendimiento de Tickets</a>
    <a href="{{ route('reportes.alertas') }}" style="padding: 8px 16px; border-radius: 8px; background: var(--blanco); border: 1px solid var(--borde); color: var(--texto); text-decoration: none; font-weight: 500; font-size: 0.88rem;">Alertas de Vencimiento</a>
</nav>

{{-- Tarjetas de Resumen de Morosidad --}}
<section class="metricas" aria-label="Resumen de morosidad">
    <article class="metrica">
        <div class="metrica-cabecera"><span>CLIENTES EVALUADOS</span><span class="metrica-icono">TT</span></div>
        <strong>{{ $resumen['total_evaluados'] }}</strong>
        <small>abonados filtrados</small>
    </article>
    <article class="metrica" style="border-left: 4px solid var(--verde-700);">
        <div class="metrica-cabecera"><span>AL DÍA</span><span class="metrica-icono" style="background: var(--verde-100); color: var(--verde-700);">OK</span></div>
        <strong style="color: var(--verde-700);">{{ $resumen['total_al_dia'] }}</strong>
        <small>{{ $resumen['total_evaluados'] > 0 ? round(($resumen['total_al_dia'] / $resumen['total_evaluados']) * 100, 1) : 0 }}% de la cartera</small>
    </article>
    <article class="metrica" style="border-left: 4px solid var(--peligro);">
        <div class="metrica-cabecera"><span>EN MORA</span><span class="metrica-icono" style="background: var(--fondo-error); color: var(--peligro);">MR</span></div>
        <strong style="color: var(--peligro);">{{ $resumen['total_en_mora'] }}</strong>
        <small>{{ $resumen['porcentaje_mora'] }}% tasa de mora</small>
    </article>
    <article class="metrica" style="border-left: 4px solid #D84315;">
        <div class="metrica-cabecera"><span>DEUDA ACUMULADA</span><span class="metrica-icono" style="background: #FBE9E7; color: #D84315;">$</span></div>
        <strong style="color: #D84315;">${{ number_format($resumen['monto_total_deuda'], 2, ',', '.') }}</strong>
        <small>total pendiente de cobro</small>
    </article>
</section>

{{-- Filtros dinámicos --}}
<article class="tarjeta" style="margin-bottom: 20px;">
    <form method="GET" action="{{ route('reportes.morosidad') }}" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) 120px 100px; gap: 14px; align-items: end;">
        <div>
            <label for="estado_financiero" style="font-size: 0.8rem; font-weight: 600; display: block; margin-bottom: 4px;">Situación Financiera</label>
            <select name="estado_financiero" id="estado_financiero" class="campo" style="width: 100%; padding: 8px; border-radius: 8px; border: 1px solid var(--borde);">
                <option value="todos" {{ $filtros['estado_financiero'] === 'todos' ? 'selected' : '' }}>Todos los clientes</option>
                <option value="al_dia" {{ $filtros['estado_financiero'] === 'al_dia' ? 'selected' : '' }}>Únicamente Al Día</option>
                <option value="en_mora" {{ $filtros['estado_financiero'] === 'en_mora' ? 'selected' : '' }}>Únicamente En Mora</option>
            </select>
        </div>

        <div>
            <label for="id_plan" style="font-size: 0.8rem; font-weight: 600; display: block; margin-bottom: 4px;">Plan Contratado</label>
            <select name="id_plan" id="id_plan" class="campo" style="width: 100%; padding: 8px; border-radius: 8px; border: 1px solid var(--borde);">
                <option value="">Todos los planes</option>
                @foreach ($planes as $plan)
                    <option value="{{ $plan->id_plan }}" {{ ($filtros['id_plan'] ?? '') == $plan->id_plan ? 'selected' : '' }}>
                        {{ $plan->nombre }} (${{ number_format((float) $plan->precio_vigente, 2, ',', '.') }})
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="busqueda" style="font-size: 0.8rem; font-weight: 600; display: block; margin-bottom: 4px;">Buscar cliente</label>
            <input type="text" name="busqueda" id="busqueda" class="campo" value="{{ $filtros['busqueda'] ?? '' }}" placeholder="Nombre, DNI o teléfono..." style="width: 100%; padding: 8px; border-radius: 8px; border: 1px solid var(--borde);">
        </div>

        <div>
            <button type="submit" class="boton" style="width: 100%; height: 38px;">🔍 Filtrar</button>
        </div>
        <div>
            <a href="{{ route('reportes.morosidad') }}" class="boton" style="width: 100%; height: 38px; background: transparent; border: 1px solid var(--borde); color: var(--texto); text-align: center; line-height: 22px;">Limpiar</a>
        </div>
    </form>
</article>

{{-- Tabla de Resultados --}}
<article class="tarjeta" style="margin: 0;">
    <div class="tarjeta-cabecera">
        <div>
            <h2 style="font-size: 1.1rem; margin: 0;">Detalle de Abonados</h2>
            <p>Mostrando {{ count($items) }} clientes bajo los filtros seleccionados</p>
        </div>
    </div>

    @if (empty($items))
        <p style="padding: 24px; text-align: center; color: var(--texto-suave);">No se encontraron clientes bajo los filtros aplicados.</p>
    @else
        <div class="tabla-contenedor" style="margin: 0;">
            <table class="tabla" style="width: 100%;">
                <thead>
                    <tr>
                        <th>Cliente / Titular</th>
                        <th>Documento</th>
                        <th>Contacto</th>
                        <th>Plan</th>
                        <th>Situación</th>
                        <th style="text-align: center;">Cuotas Impagas</th>
                        <th style="text-align: right;">Deuda Total</th>
                        <th style="text-align: center;">Días Atraso</th>
                        <th>Último Pago</th>
                        <th style="text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        <tr>
                            <td>
                                <strong>{{ $item['cliente'] }}</strong><br>
                                <small style="color: var(--texto-suave);">{{ $item['localidad'] }}</small>
                            </td>
                            <td>{{ $item['documento'] }}</td>
                            <td>{{ $item['telefono'] }}</td>
                            <td>
                                <strong>{{ $item['plan'] }}</strong><br>
                                <small style="color: var(--texto-suave);">${{ number_format($item['precio_plan'], 2, ',', '.') }}</small>
                            </td>
                            <td>
                                @if ($item['estado_financiero'] === 'en_mora')
                                    <span style="background: var(--fondo-error); color: var(--peligro); padding: 4px 10px; border-radius: 6px; font-size: 0.78rem; font-weight: 700; display: inline-block;">
                                        EN MORA
                                    </span>
                                @else
                                    <span style="background: var(--verde-100); color: var(--verde-700); padding: 4px 10px; border-radius: 6px; font-size: 0.78rem; font-weight: 700; display: inline-block;">
                                        AL DÍA
                                    </span>
                                @endif
                            </td>
                            <td style="text-align: center; font-weight: 600;">
                                {{ $item['cuotas_vencidas'] }}
                            </td>
                            <td style="text-align: right; font-weight: 700; color: {{ $item['deuda_total'] > 0 ? 'var(--peligro)' : 'var(--verde-700)' }};">
                                ${{ number_format($item['deuda_total'], 2, ',', '.') }}
                            </td>
                            <td style="text-align: center;">
                                @if ($item['dias_atraso'] > 0)
                                    <span style="color: #D84315; font-weight: 600;">{{ $item['dias_atraso'] }} días</span>
                                @else
                                    <span style="color: var(--verde-700);">0</span>
                                @endif
                            </td>
                            <td>{{ $item['ultimo_pago'] }}</td>
                            <td style="text-align: right;">
                                <a href="{{ route('cuentas.show', $item['id_cliente']) }}" class="boton" style="font-size: 0.75rem; padding: 4px 8px;" title="Ver cuenta corriente e historial">
                                    Cuenta ↗
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</article>
@endsection
