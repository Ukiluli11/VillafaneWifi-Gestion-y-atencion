@extends('layouts.panel')
@section('titulo', 'Conversación #'.$conversacion->id_conversacion)
@section('contenido')
<div class="encabezado">
    <div>
        <p class="sobrelinea">Atención por WhatsApp</p>
        <h1>{{ $conversacion->cliente?->nombre_razon_social ?? '+'.$conversacion->numero_whatsapp }}</h1>
        <p>Historial completo de la conversación y acciones de atención humana.</p>
    </div>
    <a class="boton secundario" href="{{ route('conversaciones.index') }}">Volver</a>
</div>

<section class="grilla-conversacion">
    <article class="tarjeta chat-panel">
        <div class="tarjeta-cabecera">
            <div><h2>Mensajes</h2><p>{{ $conversacion->mensajes->count() }} elementos registrados</p></div>
            <span class="estado {{ $conversacion->estado->value }}">{{ str_replace('_', ' ', $conversacion->estado->value) }}</span>
        </div>

        <div class="historial-chat">
            @forelse ($conversacion->mensajes as $mensaje)
                <div class="mensaje-chat {{ $mensaje->tipo_emisor->value }}">
                    <div>
                        <strong>{{ match($mensaje->tipo_emisor->value) { 'cliente' => 'Cliente', 'bot' => 'Asistente', 'usuario_interno' => $mensaje->usuario?->nombre_usuario ?? 'Operador', default => 'Sistema' } }}</strong>
                        <small>{{ $mensaje->fecha_hora->format('d/m/Y H:i') }} · {{ $mensaje->estado_envio->value }}</small>
                    </div>
                    <p>{{ $mensaje->contenido ?: '['.ucfirst($mensaje->tipo->value).']' }}</p>
                </div>
            @empty
                <p class="vacio">Todavía no hay mensajes registrados.</p>
            @endforelse
        </div>

        @if ($conversacion->estado->value !== 'cerrada')
            @if ($conversacion->id_usuario_atencion === null)
                <form method="POST" action="{{ route('conversaciones.tomar', $conversacion) }}">
                    @csrf
                    <button type="submit">Tomar conversación</button>
                </form>
            @elseif ($conversacion->id_usuario_atencion === auth()->id())
                <form class="formulario respuesta-chat" method="POST" action="{{ route('conversaciones.responder', $conversacion) }}">
                    @csrf
                    <label>Respuesta
                        <textarea name="contenido" rows="4" maxlength="4096" required placeholder="Escribí la respuesta para el cliente...">{{ old('contenido') }}</textarea>
                    </label>
                    <div class="acciones">
                        <button type="submit">Enviar por WhatsApp</button>
                    </div>
                </form>
            @else
                <div class="alerta error">La conversación está siendo atendida por {{ $conversacion->usuarioAtencion?->nombre_usuario }}.</div>
            @endif
        @endif
    </article>

    <aside>
        <article class="tarjeta">
            <h2>Datos de atención</h2>
            <dl>
                <dt>Teléfono</dt><dd>+{{ $conversacion->numero_whatsapp }}</dd>
                <dt>Cliente</dt><dd>{{ $conversacion->cliente?->nombre_razon_social ?? 'Sin identificar' }}</dd>
                <dt>Responsable</dt><dd>{{ $conversacion->usuarioAtencion?->nombre_usuario ?? 'Sin asignar' }}</dd>
                <dt>Inicio</dt><dd>{{ $conversacion->fecha_hora_inicio->format('d/m/Y H:i') }}</dd>
                <dt>Intención</dt><dd>{{ $conversacion->intencion_actual?->value ?? 'Sin determinar' }}</dd>
            </dl>
        </article>

        @if ($conversacion->comprobantes->isNotEmpty())
            <article class="tarjeta">
                <h2>Comprobantes recibidos</h2>
                @foreach ($conversacion->comprobantes as $comprobante)
                    <p><strong>#{{ $comprobante->id_comprobante }}</strong> · {{ $comprobante->estado_validacion->value }}<br><small class="texto-suave">{{ $comprobante->nombre_original ?? $comprobante->mime_type }} · {{ number_format($comprobante->tamanio_bytes / 1024, 1, ',', '.') }} KB</small></p>
                @endforeach
            </article>
        @endif

        @if ($conversacion->tickets->isNotEmpty())
            <article class="tarjeta">
                <h2>Tickets asociados</h2>
                @foreach ($conversacion->tickets as $ticket)
                    <p><strong>#{{ $ticket->id_ticket }}</strong> · {{ $ticket->estado->value }}<br><small class="texto-suave">{{ $ticket->descripcion }}</small></p>
                @endforeach
            </article>
        @endif

        @if (($conversacion->id_usuario_atencion === auth()->id() || auth()->user()->administrador()->exists()) && $conversacion->estado->value !== 'cerrada')
            <article class="tarjeta">
                <h2>Finalizar atención</h2>
                <p class="texto-suave">Cierra la conversación y registra la fecha de finalización.</p>
                <form method="POST" action="{{ route('conversaciones.cerrar', $conversacion) }}">
                    @csrf
                    <button class="peligro" type="submit">Cerrar conversación</button>
                </form>
            </article>
        @endif
    </aside>
</section>
@endsection
