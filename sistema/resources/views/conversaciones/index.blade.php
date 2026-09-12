@extends('layouts.panel')
@section('titulo', 'Conversaciones')
@section('contenido')
<div class="encabezado">
    <div>
        <p class="sobrelinea">Atención por WhatsApp</p>
        <h1>Conversaciones</h1>
        <p>Consultá los contactos recibidos y revisá el historial guardado por el bot.</p>
    </div>
</div>

<form class="buscador filtros-conversaciones" method="GET" role="search">
    <input name="buscar" value="{{ request('buscar') }}" aria-label="Buscar conversaciones" placeholder="Buscar por cliente o número de WhatsApp">
    <select name="estado" aria-label="Filtrar por estado">
        <option value="">Todos los estados</option>
        <option value="abierta" @selected(request('estado') === 'abierta')>Abiertas</option>
        <option value="escalada" @selected(request('estado') === 'escalada')>Escaladas</option>
        <option value="cerrada" @selected(request('estado') === 'cerrada')>Cerradas</option>
    </select>
    <button type="submit">Filtrar</button>
</form>

<div class="tabla-contenedor">
    <table>
        <thead><tr><th>Cliente</th><th>WhatsApp</th><th>Inicio</th><th>Atención</th><th>Mensajes</th><th>Estado</th><th></th></tr></thead>
        <tbody>
        @forelse ($conversaciones as $conversacion)
            <tr>
                <td class="celda-principal"><strong>{{ $conversacion->cliente->nombre_razon_social }}</strong><small>{{ $conversacion->cliente->tipo_documento->value }} {{ $conversacion->cliente->numero_documento }}</small></td>
                <td>{{ $conversacion->numero_whatsapp }}</td>
                <td>{{ $conversacion->fecha_hora_inicio->format('d/m/Y H:i') }}</td>
                <td>{{ $conversacion->modo_atencion->value === 'bot' ? 'Bot' : ($conversacion->usuarioAtencion?->nombre_usuario ?? 'Usuario interno') }}</td>
                <td>{{ $conversacion->mensajes_count }}</td>
                <td><span class="estado {{ $conversacion->estado->value }}">{{ ucfirst($conversacion->estado->value) }}</span></td>
                <td><a class="enlace-tabla" href="{{ route('conversaciones.show', $conversacion) }}">Ver conversación →</a></td>
            </tr>
        @empty
            <tr><td class="vacio" colspan="7">No se encontraron conversaciones con esos criterios.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $conversaciones->links() }}
@endsection
