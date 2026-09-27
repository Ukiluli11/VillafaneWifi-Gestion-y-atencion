@extends('layouts.panel')
@section('titulo', 'Conversaciones')
@section('contenido')
<div class="encabezado">
    <div>
        <p class="sobrelinea">Atención por WhatsApp</p>
        <h1>Conversaciones</h1>
        <p>Tomá las consultas escaladas por el bot y continuá la atención desde el panel.</p>
    </div>
</div>

<form class="buscador" method="GET" action="{{ route('conversaciones.index') }}">
    <input type="search" name="buscar" value="{{ $busqueda }}" placeholder="Teléfono, documento o cliente" aria-label="Buscar conversaciones">
    <select name="estado" aria-label="Filtrar por estado">
        <option value="">Todos los estados</option>
        @foreach ($estados as $estado)
            <option value="{{ $estado->value }}" @selected($estadoSeleccionado === $estado->value)>{{ ucfirst(str_replace('_', ' ', $estado->value)) }}</option>
        @endforeach
    </select>
    <button type="submit">Buscar</button>
</form>

<div class="tabla-contenedor">
    <table>
        <thead><tr><th>Conversación</th><th>Cliente</th><th>Estado</th><th>Responsable</th><th>Actividad</th><th></th></tr></thead>
        <tbody>
        @forelse ($conversaciones as $conversacion)
            <tr>
                <td class="celda-principal"><strong>#{{ $conversacion->id_conversacion }}</strong><small>+{{ $conversacion->numero_whatsapp }}</small></td>
                <td>{{ $conversacion->cliente?->nombre_razon_social ?? 'Sin identificar' }}</td>
                <td><span class="estado {{ $conversacion->estado->value }}">{{ ucfirst(str_replace('_', ' ', $conversacion->estado->value)) }}</span></td>
                <td>{{ $conversacion->usuarioAtencion?->nombre_usuario ?? 'Sin asignar' }}</td>
                <td class="celda-principal"><strong>{{ $conversacion->mensajes_count }} mensajes</strong><small>{{ $conversacion->mensajes_max_fecha_hora ? \Carbon\CarbonImmutable::parse($conversacion->mensajes_max_fecha_hora)->format('d/m/Y H:i') : 'Sin mensajes' }}</small></td>
                <td><a class="enlace-tabla" href="{{ route('conversaciones.show', $conversacion) }}">Abrir →</a></td>
            </tr>
        @empty
            <tr><td colspan="6" class="vacio">No hay conversaciones que coincidan con el filtro.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $conversaciones->links() }}
@endsection
