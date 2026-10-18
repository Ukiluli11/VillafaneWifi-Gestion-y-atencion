@extends('layouts.panel')
@section('titulo', 'Ticket #'.$ticket->id_ticket)
@section('contenido')
<div class="encabezado">
    <div>
        <p class="sobrelinea">Soporte técnico</p>
        <h1>Ticket #{{ $ticket->id_ticket }}</h1>
        <p>{{ $ticket->servicio->cliente->nombre_razon_social }} · Servicio #{{ $ticket->id_servicio }}</p>
    </div>
    <a class="boton secundario" href="{{ route('tickets.index') }}">Volver</a>
</div>

<section class="grilla-conversacion">
    <div>
        <article class="tarjeta">
            <div class="tarjeta-cabecera">
                <h2>Reclamo</h2>
                <span class="estado {{ $ticket->estado->value }}">{{ ucfirst(str_replace('_', ' ', $ticket->estado->value)) }}</span>
            </div>
            <p>{{ $ticket->descripcion }}</p>
            <p class="texto-suave">Creado el {{ $ticket->fecha_creacion->format('d/m/Y H:i') }} desde la <a href="{{ route('conversaciones.show', $ticket->conversacion) }}">conversación #{{ $ticket->id_conversacion }}</a>.</p>
            <p class="texto-suave">Plan: {{ $ticket->servicio->plan->nombre }} · Responsable: {{ $ticket->usuarioAsignado?->nombre_usuario ?? 'Sin asignar' }}</p>
        </article>

        <article class="tarjeta">
            <h2>Notas internas</h2>
            @forelse ($ticket->notas as $nota)
                <p><strong>{{ $nota->usuario->nombre_usuario }}</strong> · <small class="texto-suave">{{ $nota->fecha_hora->format('d/m/Y H:i') }}</small><br>{{ $nota->contenido }}</p>
            @empty
                <p class="texto-suave">Todavía no hay notas de seguimiento.</p>
            @endforelse

            @if ($navegacionPermitida['conversaciones'] && $puedeGestionar)
                <form class="formulario" method="POST" action="{{ route('tickets.notas.store', $ticket) }}">
                    @csrf
                    <label>Agregar nota interna
                        <textarea name="contenido" rows="3" maxlength="5000" required placeholder="Describí el avance del reclamo...">{{ old('contenido') }}</textarea>
                    </label>
                    <div class="acciones"><button type="submit">Guardar nota</button></div>
                </form>
            @endif
        </article>
    </div>

    <aside>
        @if ($puedeAsignar && $ticket->estado !== \App\Enums\EstadoTicket::Cerrado && $ticket->estado !== \App\Enums\EstadoTicket::Resuelto)
            <article class="tarjeta">
                <h2>Asignación</h2>
                <p class="texto-suave">Asigná el reclamo a un responsable de soporte.</p>
                <form class="formulario" method="POST" action="{{ route('tickets.asignar', $ticket) }}">
                    @csrf
                    <label>Responsable
                        <select name="id_usuario" required>
                            <option value="">Seleccionar</option>
                            @foreach ($responsables as $responsable)
                                @if ($esAdministrador || $responsable->id_usuario === auth()->id())
                                    <option value="{{ $responsable->id_usuario }}" @selected(old('id_usuario', $ticket->id_usuario_asignado) == $responsable->id_usuario)>{{ $responsable->nombre_usuario }}</option>
                                @endif
                            @endforeach
                        </select>
                    </label>
                    <div class="acciones"><button type="submit">Asignar ticket</button></div>
                </form>
            </article>
        @endif

        @if ($puedeGestionar)
            <article class="tarjeta">
                <h2>Estado del ticket</h2>
                @if (in_array($ticket->estado, [\App\Enums\EstadoTicket::Asignado, \App\Enums\EstadoTicket::EnProceso, \App\Enums\EstadoTicket::Reabierto], true))
                    <form method="POST" action="{{ route('tickets.resolver', $ticket) }}">@csrf<button type="submit">Marcar como resuelto</button></form>
                @elseif ($ticket->estado === \App\Enums\EstadoTicket::Resuelto)
                    <form method="POST" action="{{ route('tickets.cerrar', $ticket) }}">@csrf<button type="submit">Cerrar ticket</button></form>
                @endif
                @if (in_array($ticket->estado, [\App\Enums\EstadoTicket::Resuelto, \App\Enums\EstadoTicket::Cerrado], true))
                    <form method="POST" action="{{ route('tickets.reabrir', $ticket) }}">@csrf<button class="secundario" type="submit">Reabrir ticket</button></form>
                @endif
            </article>
        @endif
    </aside>
</section>
@endsection
