@extends('layouts.panel')
@section('titulo', 'Tickets')
@section('contenido')
<div class="encabezado">
    <div>
        <p class="sobrelinea">Atención por WhatsApp</p>
        <h1>Tickets de soporte</h1>
        <p>Seguimiento de los reclamos creados durante las conversaciones.</p>
    </div>
</div>

<form class="buscador" method="GET" action="{{ route('tickets.index') }}">
    <input type="search" name="buscar" value="{{ $busqueda }}" placeholder="Número de ticket o cliente" aria-label="Buscar tickets">
    <select name="estado" aria-label="Filtrar por estado">
        <option value="">Todos los estados</option>
        @foreach ($estados as $estado)
            <option value="{{ $estado->value }}" @selected($estadoSeleccionado === $estado->value)>{{ ucfirst(str_replace('_', ' ', $estado->value)) }}</option>
        @endforeach
    </select>
    <label><input type="checkbox" name="mios" value="1" @checked($mios)> Solo mis tickets</label>
    <button type="submit">Buscar</button>
</form>

<div class="tabla-contenedor">
    <table>
        <thead><tr><th>Ticket</th><th>Cliente</th><th>Estado</th><th>Responsable</th><th>Creado</th><th></th></tr></thead>
        <tbody>
        @forelse ($tickets as $ticket)
            <tr>
                <td class="celda-principal"><strong>#{{ $ticket->id_ticket }}</strong><small>{{ \Illuminate\Support\Str::limit($ticket->descripcion, 80) }}</small></td>
                <td>{{ $ticket->servicio->cliente->nombre_razon_social }}</td>
                <td><span class="estado {{ $ticket->estado->value }}">{{ ucfirst(str_replace('_', ' ', $ticket->estado->value)) }}</span></td>
                <td>{{ $ticket->usuarioAsignado?->nombre_usuario ?? 'Sin asignar' }}</td>
                <td>{{ $ticket->fecha_creacion->format('d/m/Y H:i') }}</td>
                <td><a class="enlace-tabla" href="{{ route('tickets.show', $ticket) }}">Abrir →</a></td>
            </tr>
        @empty
            <tr><td colspan="6" class="vacio">No hay tickets que coincidan con el filtro.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $tickets->links() }}
@endsection
