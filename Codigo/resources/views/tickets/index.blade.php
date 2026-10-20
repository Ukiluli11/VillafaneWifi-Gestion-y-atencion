@extends('layouts.panel')
@section('titulo', 'Bandeja de Tickets de Soporte')
@section('contenido')
<section class="pagina-tickets">
<div class="encabezado">
    <div>
        <p class="sobrelinea">Soporte Técnico y Reclamos</p>
        <h1>Bandeja de Tickets</h1>
        <p>Gestioná, derivá y resolvé los incidentes y solicitudes de servicio reportados por clientes o generados internamente.</p>
    </div>
    @if ($navegacionPermitida['gestionar_tickets'] ?? false)
        <a class="boton" href="{{ route('tickets.create') }}">+ Nuevo ticket manual</a>
    @endif
</div>

{{-- Tarjetas de métricas rápidas --}}
<div class="cuadricula-metricas" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
    <div class="tarjeta" style="padding: 1rem; border-left: 4px solid #3b82f6;">
        <span class="texto-suave" style="font-size: 0.85rem; font-weight: 500;">Total Incidentes</span>
        <div style="font-size: 1.8rem; font-weight: 700; margin-top: 0.25rem;">{{ $estadisticas['total'] }}</div>
        <small class="texto-suave">En el sistema</small>
    </div>
    <div class="tarjeta" style="padding: 1rem; border-left: 4px solid #f59e0b;">
        <span class="texto-suave" style="font-size: 0.85rem; font-weight: 500;">Pendientes</span>
        <div style="font-size: 1.8rem; font-weight: 700; margin-top: 0.25rem;">{{ $estadisticas['pendientes'] }}</div>
        <small class="texto-suave">Sin asignar / por atender</small>
    </div>
    <div class="tarjeta" style="padding: 1rem; border-left: 4px solid #8b5cf6;">
        <span class="texto-suave" style="font-size: 0.85rem; font-weight: 500;">En Proceso</span>
        <div style="font-size: 1.8rem; font-weight: 700; margin-top: 0.25rem;">{{ $estadisticas['en_proceso'] }}</div>
        <small class="texto-suave">Asignados a técnicos</small>
    </div>
    <div class="tarjeta" style="padding: 1rem; border-left: 4px solid #ef4444;">
        <span class="texto-suave" style="font-size: 0.85rem; font-weight: 500;">Reabiertos</span>
        <div style="font-size: 1.8rem; font-weight: 700; margin-top: 0.25rem;">{{ $estadisticas['reabiertos'] }}</div>
        <small class="texto-suave">Fallas persistentes</small>
    </div>
    <div class="tarjeta" style="padding: 1rem; border-left: 4px solid #10b981;">
        <span class="texto-suave" style="font-size: 0.85rem; font-weight: 500;">Resueltos</span>
        <div style="font-size: 1.8rem; font-weight: 700; margin-top: 0.25rem;">{{ $estadisticas['resueltos'] }}</div>
        <small class="texto-suave">Solucionados</small>
    </div>
</div>

{{-- Filtros y buscador --}}
<div class="tarjeta" style="padding: 1rem; margin-bottom: 1.5rem;">
    <form method="GET" action="{{ route('tickets.index') }}" class="filtros-tickets">
        <div>
            <label for="filtro-busqueda" style="font-size: 0.8rem; font-weight: 600; display: block; margin-bottom: 0.25rem;">Buscar</label>
            <input type="search" id="filtro-busqueda" name="busqueda" value="{{ $filtros['busqueda'] ?? '' }}" placeholder="N° ticket, cliente, DNI..." aria-label="Buscar tickets">
        </div>

        <div>
            <label for="filtro-estado" style="font-size: 0.8rem; font-weight: 600; display: block; margin-bottom: 0.25rem;">Estado</label>
            <select id="filtro-estado" name="estado" aria-label="Filtrar por estado">
                <option value="">Todos los estados</option>
                <option value="pendiente" @selected(($filtros['estado'] ?? '') === 'pendiente')>Pendiente</option>
                <option value="asignado" @selected(($filtros['estado'] ?? '') === 'asignado')>Asignado</option>
                <option value="en_proceso" @selected(($filtros['estado'] ?? '') === 'en_proceso')>En Proceso</option>
                <option value="resuelto" @selected(($filtros['estado'] ?? '') === 'resuelto')>Resuelto</option>
                <option value="reabierto" @selected(($filtros['estado'] ?? '') === 'reabierto')>Reabierto</option>
                <option value="cerrado" @selected(($filtros['estado'] ?? '') === 'cerrado')>Cerrado</option>
            </select>
        </div>

        <div>
            <label for="filtro-tecnico" style="font-size: 0.8rem; font-weight: 600; display: block; margin-bottom: 0.25rem;">Técnico Asignado</label>
            <select id="filtro-tecnico" name="id_usuario_asignado" aria-label="Filtrar por técnico">
                <option value="">Todos los técnicos</option>
                <option value="sin_asignar" @selected(($filtros['id_usuario_asignado'] ?? '') === 'sin_asignar')>-- Sin asignar --</option>
                @foreach ($tecnicos as $tec)
                    <option value="{{ $tec->id_usuario }}" @selected(($filtros['id_usuario_asignado'] ?? '') == $tec->id_usuario)>{{ $tec->nombre_usuario }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="filtro-tipo" style="font-size: 0.8rem; font-weight: 600; display: block; margin-bottom: 0.25rem;">Tipo</label>
            <select id="filtro-tipo" name="tipo" aria-label="Filtrar por tipo">
                <option value="">Todos los tipos</option>
                <option value="tecnico" @selected(($filtros['tipo'] ?? '') === 'tecnico')>Técnico</option>
                <option value="administrativo" @selected(($filtros['tipo'] ?? '') === 'administrativo')>Administrativo</option>
                <option value="instalacion" @selected(($filtros['tipo'] ?? '') === 'instalacion')>Instalación</option>
                <option value="reclamo" @selected(($filtros['tipo'] ?? '') === 'reclamo')>Reclamo</option>
            </select>
        </div>

        <div>
            <label for="fecha-desde">Desde</label>
            <input id="fecha-desde" name="fecha_desde" type="date" value="{{ $filtros['fecha_desde'] ?? '' }}">
        </div>
        <div>
            <label for="fecha-hasta">Hasta</label>
            <input id="fecha-hasta" name="fecha_hasta" type="date" value="{{ $filtros['fecha_hasta'] ?? '' }}">
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <button type="submit" class="boton" style="padding: 0.55rem 1rem;">Filtrar</button>
            <a href="{{ route('tickets.index') }}" class="boton secundario" style="padding: 0.55rem 0.8rem;" title="Limpiar filtros">✕</a>
        </div>
    </form>
</div>

{{-- Tabla de tickets --}}
<div class="tabla-contenedor">
    <table>
        <thead>
            <tr>
                <th>Ticket</th>
                <th>Cliente y Servicio</th>
                <th>Tipo</th>
                <th>Estado</th>
                <th>Técnico</th>
                <th>Fecha Creación</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($tickets as $ticket)
                @php
                    $colorEstado = match ($ticket->estado->value) {
                        'pendiente'  => '#f59e0b',
                        'asignado'   => '#3b82f6',
                        'en_proceso' => '#8b5cf6',
                        'resuelto'   => '#10b981',
                        'cerrado'    => '#64748b',
                        'reabierto'  => '#ef4444',
                        default      => '#64748b',
                    };
                @endphp
                <tr>
                    <td class="celda-principal">
                        <strong>#{{ $ticket->id_ticket }}</strong>
                        <small style="max-width: 260px; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            {{ $ticket->descripcion }}
                        </small>
                    </td>
                    <td>
                        <strong>{{ $ticket->servicio?->cliente?->nombre_razon_social ?? 'Cliente no disponible' }}</strong>
                        <small style="display: block; color: var(--texto-suave);">
                            Plan: {{ $ticket->servicio?->plan?->nombre ?? 'N/A' }} | IP: {{ $ticket->servicio?->ipv4 ?? 'S/D' }}
                        </small>
                    </td>
                    <td>
                        <span style="font-size: 0.78rem; text-transform: uppercase; font-weight: 600; padding: 0.2rem 0.5rem; background: rgba(0,0,0,0.05); border-radius: 4px;">
                            {{ $ticket->tipo }}
                        </span>
                    </td>
                    <td>
                        <span style="display: inline-flex; align-items: center; gap: 0.35rem; font-weight: 600; font-size: 0.8rem; padding: 0.25rem 0.6rem; border-radius: 9999px; background: {{ $colorEstado }}20; color: {{ $colorEstado }};">
                            <span style="width: 7px; height: 7px; border-radius: 50%; background: {{ $colorEstado }};"></span>
                            {{ ucfirst(str_replace('_', ' ', $ticket->estado->value)) }}
                        </span>
                    </td>
                    <td>
                        @if ($ticket->usuarioAsignado)
                            <span>{{ $ticket->usuarioAsignado->nombre_usuario }}</span>
                        @else
                            <em style="color: #94a3b8; font-size: 0.85rem;">Sin asignar</em>
                        @endif
                    </td>
                    <td>
                        <span>{{ $ticket->fecha_creacion?->format('d/m/Y') }}</span>
                        <small style="display: block; color: var(--texto-suave);">
                            {{ $ticket->fecha_creacion?->format('H:i') }} hs
                        </small>
                    </td>
                    <td>
                        <a class="enlace-tabla" href="{{ route('tickets.show', $ticket) }}">Ver detalle →</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="vacio" style="text-align: center; padding: 2.5rem 1rem;">
                        <p style="margin: 0; font-weight: 500;">No se encontraron tickets con los criterios seleccionados.</p>
                        @if ($navegacionPermitida['gestionar_tickets'] ?? false)
                            <a class="boton" href="{{ route('tickets.create') }}" style="margin-top: 1rem; display: inline-block;">+ Registrar nuevo ticket</a>
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $tickets->links() }}
</section>
@endsection
