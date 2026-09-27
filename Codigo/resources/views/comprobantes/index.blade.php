@extends('layouts.panel')
@section('titulo', 'Bandeja de Conciliación de Comprobantes')
@section('contenido')
<div class="encabezado">
    <div>
        <p class="sobrelinea">Módulo de Pagos y Comprobantes</p>
        <h1>Bandeja de Conciliación</h1>
        <p>Revisá, auditá y conciliá los comprobantes recibidos para imputar pagos a las cuotas correspondientes.</p>
    </div>
    @if ($navegacionPermitida['gestionar_comprobantes'] ?? false)
        <a class="boton" href="{{ route('comprobantes.create') }}">+ Subir comprobante manual</a>
    @endif
</div>

<div class="cuadricula-metricas" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
    <div class="tarjeta" style="padding: 1.25rem; border-left: 4px solid #d97706;">
        <span style="font-size: 0.85rem; color: #64748b; font-weight: 500;">Pendientes de Conciliación</span>
        <div style="font-size: 1.8rem; font-weight: 700; color: #b45309; margin-top: 0.25rem;">{{ $totalPendientes }}</div>
        <small style="color: #64748b;">Requieren revisión</small>
    </div>
    <div class="tarjeta" style="padding: 1.25rem; border-left: 4px solid #16a34a;">
        <span style="font-size: 0.85rem; color: #64748b; font-weight: 500;">Comprobantes Aprobados</span>
        <div style="font-size: 1.8rem; font-weight: 700; color: #15803d; margin-top: 0.25rem;">{{ $totalAprobados }}</div>
        <small style="color: #64748b;">Acreditados a cuentas</small>
    </div>
    <div class="tarjeta" style="padding: 1.25rem; border-left: 4px solid #dc2626;">
        <span style="font-size: 0.85rem; color: #64748b; font-weight: 500;">Comprobantes Rechazados</span>
        <div style="font-size: 1.8rem; font-weight: 700; color: #b91c1c; margin-top: 0.25rem;">{{ $totalRechazados }}</div>
        <small style="color: #64748b;">Con motivo notificado</small>
    </div>
</div>

<div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 1rem;">
    <div class="filtros-estado" style="display: flex; gap: 0.5rem;">
        <a class="boton {{ !request('estado') ? '' : 'secundario' }}" href="{{ route('comprobantes.index') }}">Todos</a>
        <a class="boton {{ request('estado') === 'pendiente' ? '' : 'secundario' }}" href="{{ route('comprobantes.index', ['estado' => 'pendiente']) }}">
            Pendientes ({{ $totalPendientes }})
        </a>
        <a class="boton {{ request('estado') === 'aprobado' ? '' : 'secundario' }}" href="{{ route('comprobantes.index', ['estado' => 'aprobado']) }}">
            Aprobados ({{ $totalAprobados }})
        </a>
        <a class="boton {{ request('estado') === 'rechazado' ? '' : 'secundario' }}" href="{{ route('comprobantes.index', ['estado' => 'rechazado']) }}">
            Rechazados ({{ $totalRechazados }})
        </a>
    </div>

    <form class="buscador" method="GET" role="search" style="margin: 0; max-width: 400px;">
        @if (request('estado'))
            <input type="hidden" name="estado" value="{{ request('estado') }}">
        @endif
        <input name="buscar" value="{{ request('buscar') }}" aria-label="Buscar comprobantes" placeholder="Buscar por cliente, N° operación o hash">
        <button type="submit">Buscar</button>
    </form>
</div>

<div class="tabla-contenedor">
    <table>
        <thead>
            <tr>
                <th>Comprobante</th>
                <th>Cliente</th>
                <th>Monto Detectado</th>
                <th>Fecha Pago</th>
                <th>N° Operación</th>
                <th>Origen</th>
                <th>Estado</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($comprobantes as $comprobante)
                <tr>
                    <td class="celda-principal">
                        <strong>#{{ $comprobante->id_comprobante }}</strong>
                        <small title="{{ $comprobante->hash_archivo }}">Hash: {{ substr($comprobante->hash_archivo, 0, 10) }}...</small>
                    </td>
                    <td>
                        <strong>{{ $comprobante->cliente->nombre_razon_social }}</strong>
                        <small style="display: block; color: #64748b;">{{ $comprobante->cliente->tipo_documento->value }} {{ $comprobante->cliente->numero_documento }}</small>
                    </td>
                    <td>
                        <strong style="color: #0f172a;">${{ number_format((float) ($comprobante->monto_ocr ?: 0), 2, ',', '.') }}</strong>
                    </td>
                    <td>
                        {{ $comprobante->fecha_ocr ? $comprobante->fecha_ocr->format('d/m/Y') : '—' }}
                    </td>
                    <td>
                        <code>{{ $comprobante->numero_operacion ?: 'Sin detectar' }}</code>
                    </td>
                    <td>
                        <span style="font-size: 0.8rem; text-transform: uppercase; padding: 0.2rem 0.5rem; background: #e2e8f0; border-radius: 4px;">
                            {{ $comprobante->origen === \App\Enums\OrigenComprobante::Whatsapp ? 'WhatsApp' : 'Manual' }}
                        </span>
                    </td>
                    <td>
                        <span class="estado {{ $comprobante->estado_validacion->value }}">
                            {{ ucfirst($comprobante->estado_validacion->value) }}
                        </span>
                    </td>
                    <td>
                        <a class="enlace-tabla" href="{{ route('comprobantes.show', $comprobante) }}">
                            {{ $comprobante->estaPendiente() ? 'Auditar y Conciliar →' : 'Ver Detalle →' }}
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="vacio" colspan="8">No hay comprobantes registrados con ese criterio de búsqueda.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top: 1rem;">
    {{ $comprobantes->links() }}
</div>
@endsection
