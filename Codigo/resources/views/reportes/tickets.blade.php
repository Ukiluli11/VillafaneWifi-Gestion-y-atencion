@extends('layouts.panel')
@section('titulo', 'Reporte de Tickets y Eficiencia Técnica')
@section('contenido')
<div class="encabezado">
    <div>
        <p class="sobrelinea">Reportes y métricas</p>
        <h1>Reporte de Tickets y Eficiencia por Técnico</h1>
        <p>Evaluación del servicio de soporte, tasa de resolución, tiempos medios (MTTR) y productividad del equipo.</p>
    </div>
    @if ($navegacionPermitida['exportar_reportes'] ?? false)
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <a class="boton" href="{{ route('reportes.exportar', ['tipo' => 'tickets', 'formato' => 'xlsx'] + request()->all()) }}">
            📥 Exportar Excel (.xlsx)
        </a>
        <a class="boton" target="_blank" href="{{ route('reportes.exportar', ['tipo' => 'tickets', 'formato' => 'pdf'] + request()->all()) }}" style="background: #00838F; color: #fff;">
            🖨️ Descargar PDF
        </a>
    </div>
    @endif
</div>

<nav style="display: flex; gap: 10px; margin-bottom: 24px; border-bottom: 1px solid var(--borde); padding-bottom: 12px; overflow-x: auto;">
    <a href="{{ route('reportes.dashboard') }}" style="padding: 8px 16px; border-radius: 8px; background: var(--blanco); border: 1px solid var(--borde); color: var(--texto); text-decoration: none; font-weight: 500; font-size: 0.88rem;">Tablero Principal</a>
    <a href="{{ route('reportes.morosidad') }}" style="padding: 8px 16px; border-radius: 8px; background: var(--blanco); border: 1px solid var(--borde); color: var(--texto); text-decoration: none; font-weight: 500; font-size: 0.88rem;">Clientes y Morosidad</a>
    <a href="{{ route('reportes.ingresos') }}" style="padding: 8px 16px; border-radius: 8px; background: var(--blanco); border: 1px solid var(--borde); color: var(--texto); text-decoration: none; font-weight: 500; font-size: 0.88rem;">Cobranzas e Ingresos</a>
    <a href="{{ route('reportes.tickets') }}" style="padding: 8px 16px; border-radius: 8px; background: #00838F; color: #fff; text-decoration: none; font-weight: 600; font-size: 0.88rem;">Rendimiento de Tickets</a>
    <a href="{{ route('reportes.alertas') }}" style="padding: 8px 16px; border-radius: 8px; background: var(--blanco); border: 1px solid var(--borde); color: var(--texto); text-decoration: none; font-weight: 500; font-size: 0.88rem;">Alertas de Vencimiento</a>
</nav>

{{-- KPIs de Rendimiento Técnico --}}
<section class="metricas" aria-label="Indicadores de soporte">
    <article class="metrica">
        <div class="metrica-cabecera"><span>TOTAL TICKETS</span><span class="metrica-icono">TK</span></div>
        <strong>{{ $resumen['total_tickets'] }}</strong>
        <small>reclamos generados en el período</small>
    </article>
    <article class="metrica" style="border-left: 4px solid #2E7D32;">
        <div class="metrica-cabecera"><span>RESUELTOS</span><span class="metrica-icono" style="background: #E8F5E9; color: #2E7D32;">✓</span></div>
        <strong style="color: #2E7D32;">{{ $resumen['tickets_resueltos'] }}</strong>
        <small>{{ $resumen['tasa_resolucion'] }}% tasa de resolución</small>
    </article>
    <article class="metrica" style="border-left: 4px solid #1976D2;">
        <div class="metrica-cabecera"><span>TIEMPO PROMEDIO (MTTR)</span><span class="metrica-icono" style="background: #E3F2FD; color: #1976D2;">⏱</span></div>
        <strong style="color: #1976D2;">{{ $resumen['tiempo_promedio_horas'] }} hs</strong>
        <small>tiempo medio hasta solución</small>
    </article>
    <article class="metrica">
        <div class="metrica-cabecera"><span>PERÍODO</span><span class="metrica-icono">📅</span></div>
        <strong style="font-size: 1.15rem; margin-top: 20px;">{{ date('d/m/Y', strtotime($resumen['desde'])) }}</strong>
        <small>al {{ date('d/m/Y', strtotime($resumen['hasta'])) }}</small>
    </article>
</section>

{{-- Distribución por Técnico --}}
@if (!empty($resumen['por_tecnico']))
    <article class="tarjeta" style="margin-bottom: 20px;">
        <div class="tarjeta-cabecera">
            <div>
                <h2 style="font-size: 1.1rem; margin: 0;">Productividad y Carga Operativa por Técnico</h2>
                <p>Distribución de tickets asignados y resueltos por miembro del equipo</p>
            </div>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
            @foreach ($resumen['por_tecnico'] as $tecnico)
                <div style="padding: 14px; border-radius: 12px; background: var(--fondo); border: 1px solid var(--borde);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <span style="font-weight: 700; font-size: 0.95rem;">👤 {{ $tecnico['usuario'] }}</span>
                        <span style="background: #E3F2FD; color: #1565C0; font-size: 0.75rem; font-weight: 700; padding: 2px 6px; border-radius: 4px;">
                            {{ $tecnico['total'] > 0 ? round(($tecnico['resueltos'] / $tecnico['total']) * 100, 0) : 0 }}% efectividad
                        </span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-top: 8px;">
                        <div>
                            <span style="font-size: 0.75rem; color: var(--texto-suave);">Asignados</span>
                            <div style="font-size: 1.2rem; font-weight: 700;">{{ $tecnico['total'] }}</div>
                        </div>
                        <div>
                            <span style="font-size: 0.75rem; color: var(--texto-suave);">Resueltos</span>
                            <div style="font-size: 1.2rem; font-weight: 700; color: #2E7D32;">{{ $tecnico['resueltos'] }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </article>
@endif

{{-- Filtros dinámicos --}}
<article class="tarjeta" style="margin-bottom: 20px;">
    <form method="GET" action="{{ route('reportes.tickets') }}" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)) 120px 100px; gap: 14px; align-items: end;">
        <div>
            <label for="fecha_desde" style="font-size: 0.8rem; font-weight: 600; display: block; margin-bottom: 4px;">Fecha Desde</label>
            <input type="date" name="fecha_desde" id="fecha_desde" class="campo" value="{{ $filtros['fecha_desde'] }}" style="width: 100%; padding: 8px; border-radius: 8px; border: 1px solid var(--borde);">
        </div>

        <div>
            <label for="fecha_hasta" style="font-size: 0.8rem; font-weight: 600; display: block; margin-bottom: 4px;">Fecha Hasta</label>
            <input type="date" name="fecha_hasta" id="fecha_hasta" class="campo" value="{{ $filtros['fecha_hasta'] }}" style="width: 100%; padding: 8px; border-radius: 8px; border: 1px solid var(--borde);">
        </div>

        <div>
            <label for="estado" style="font-size: 0.8rem; font-weight: 600; display: block; margin-bottom: 4px;">Estado del Ticket</label>
            <select name="estado" id="estado" class="campo" style="width: 100%; padding: 8px; border-radius: 8px; border: 1px solid var(--borde);">
                <option value="todos">Todos los estados</option>
                <option value="pendiente" {{ ($filtros['estado'] ?? '') === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                <option value="asignado" {{ ($filtros['estado'] ?? '') === 'asignado' ? 'selected' : '' }}>Asignado</option>
                <option value="en_proceso" {{ ($filtros['estado'] ?? '') === 'en_proceso' ? 'selected' : '' }}>En Proceso</option>
                <option value="resuelto" {{ ($filtros['estado'] ?? '') === 'resuelto' ? 'selected' : '' }}>Resuelto</option>
                <option value="cerrado" {{ ($filtros['estado'] ?? '') === 'cerrado' ? 'selected' : '' }}>Cerrado</option>
                <option value="reabierto" {{ ($filtros['estado'] ?? '') === 'reabierto' ? 'selected' : '' }}>Reabierto</option>
            </select>
        </div>

        <div>
            <label for="id_usuario_asignado" style="font-size: 0.8rem; font-weight: 600; display: block; margin-bottom: 4px;">Técnico / Responsable</label>
            <select name="id_usuario_asignado" id="id_usuario_asignado" class="campo" style="width: 100%; padding: 8px; border-radius: 8px; border: 1px solid var(--borde);">
                <option value="">Todos los técnicos</option>
                <option value="sin_asignar" {{ ($filtros['id_usuario_asignado'] ?? '') === 'sin_asignar' ? 'selected' : '' }}>Sin asignar</option>
                @foreach ($tecnicos as $tecnico)
                    <option value="{{ $tecnico->id_usuario }}" {{ ($filtros['id_usuario_asignado'] ?? '') == $tecnico->id_usuario ? 'selected' : '' }}>
                        {{ $tecnico->nombre_usuario }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <button type="submit" class="boton" style="width: 100%; height: 38px;">🔍 Filtrar</button>
        </div>
        <div>
            <a href="{{ route('reportes.tickets') }}" class="boton" style="width: 100%; height: 38px; background: transparent; border: 1px solid var(--borde); color: var(--texto); text-align: center; line-height: 22px;">Mes actual</a>
        </div>
    </form>
</article>

{{-- Tabla de Tickets --}}
<article class="tarjeta" style="margin: 0;">
    <div class="tarjeta-cabecera">
        <div>
            <h2 style="font-size: 1.1rem; margin: 0;">Detalle de Tickets de Soporte</h2>
            <p>Mostrando {{ $tickets->count() }} reclamos técnicos bajo el filtro</p>
        </div>
    </div>

    @if ($tickets->isEmpty())
        <p style="padding: 24px; text-align: center; color: var(--texto-suave);">No se encontraron tickets en el período seleccionado.</p>
    @else
        <div class="tabla-contenedor" style="margin: 0;">
            <table class="tabla" style="width: 100%;">
                <thead>
                    <tr>
                        <th># Ticket</th>
                        <th>Fecha Creación</th>
                        <th>Cliente / Titular</th>
                        <th>Tipo</th>
                        <th>Responsable</th>
                        <th>Estado</th>
                        <th>Resolución</th>
                        <th style="text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tickets as $ticket)
                        @php
                            $cliente = $ticket->servicio?->cliente;
                            $horas = ($ticket->fecha_creacion && $ticket->fecha_resolucion)
                                ? round($ticket->fecha_creacion->diffInHours($ticket->fecha_resolucion), 1)
                                : null;
                        @endphp
                        <tr>
                            <td><strong>#{{ $ticket->id_ticket }}</strong></td>
                            <td>{{ $ticket->fecha_creacion?->format('d/m/Y H:i') }}</td>
                            <td>
                                @if ($cliente)
                                    <strong>{{ $cliente->nombre_razon_social }}</strong><br>
                                    <small style="color: var(--texto-suave);">Tel: {{ $cliente->telefono_whatsapp ?? 'Sin teléfono' }}</small>
                                @else
                                    <span style="color: var(--texto-suave);">Sin cliente</span>
                                @endif
                            </td>
                            <td>
                                <span style="background: var(--fondo); border: 1px solid var(--borde); padding: 3px 8px; border-radius: 4px; font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">
                                    {{ $ticket->tipo }}
                                </span>
                            </td>
                            <td>
                                @if ($ticket->usuarioAsignado)
                                    👤 {{ $ticket->usuarioAsignado->nombre_usuario }}
                                @else
                                    <span style="color: #E65100; font-weight: 600;">Sin asignar</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $est = $ticket->estado instanceof \BackedEnum ? $ticket->estado->value : (string) $ticket->estado;
                                    $colores = [
                                        'pendiente' => ['#FFF9C4', '#F57F17'],
                                        'asignado' => ['#E3F2FD', '#1565C0'],
                                        'en_proceso' => ['#E1F5FE', '#0288D1'],
                                        'resuelto' => ['#E8F5E9', '#2E7D32'],
                                        'cerrado' => ['#ECEFF1', '#546E7A'],
                                        'reabierto' => ['#FFEBEE', '#C62828'],
                                    ];
                                    $bg = $colores[$est][0] ?? '#ECEFF1';
                                    $fg = $colores[$est][1] ?? '#333';
                                @endphp
                                <span style="background: {{ $bg }}; color: {{ $fg }}; padding: 3px 8px; border-radius: 4px; font-size: 0.78rem; font-weight: 700;">
                                    {{ strtoupper($est) }}
                                </span>
                            </td>
                            <td>
                                @if ($horas !== null)
                                    <strong style="color: #2E7D32;">{{ $horas }} hs</strong><br>
                                    <small style="color: var(--texto-suave);">{{ $ticket->fecha_resolucion?->format('d/m H:i') }}</small>
                                @else
                                    <span style="color: var(--texto-suave);">En curso</span>
                                @endif
                            </td>
                            <td style="text-align: right;">
                                <a href="{{ route('tickets.show', $ticket->id_ticket) }}" class="boton" style="font-size: 0.75rem; padding: 4px 8px;">
                                    Ver Ticket ↗
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
