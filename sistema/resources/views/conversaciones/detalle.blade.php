@extends('layouts.panel')
@section('titulo', 'Conversación de ' . $conversacion->cliente->nombre_razon_social)
@section('contenido')
<div class="encabezado">
    <div>
        <p class="sobrelinea">Historial de WhatsApp</p>
        <h1>{{ $conversacion->cliente->nombre_razon_social }}</h1>
        <p>{{ $conversacion->numero_whatsapp }} · Iniciada el {{ $conversacion->fecha_hora_inicio->format('d/m/Y H:i') }}</p>
    </div>
    <div class="acciones">
        <span class="estado {{ $conversacion->estado->value }}">{{ ucfirst($conversacion->estado->value) }}</span>
        <a class="boton secundario" href="{{ route('conversaciones.index') }}">Volver</a>
    </div>
</div>

<section class="resumen-conversacion" aria-label="Datos de la conversación">
    <div><small>Atención actual</small><strong>{{ $conversacion->modo_atencion->value === 'bot' ? 'Bot automático' : 'Atención humana' }}</strong></div>
    <div><small>Responsable</small><strong>{{ $conversacion->usuarioAtencion?->nombre_usuario ?? 'Sin asignar' }}</strong></div>
    <div><small>Total de mensajes</small><strong>{{ $conversacion->mensajes->count() }}</strong></div>
</section>

<section class="tarjeta historial-conversacion">
    <div class="tarjeta-cabecera"><div><h2>Mensajes</h2><p>Historial ordenado desde el primer contacto.</p></div></div>
    <div class="lista-mensajes">
        @forelse ($conversacion->mensajes as $mensaje)
            <article class="mensaje-chat {{ $mensaje->tipo_emisor->value }}">
                <div class="mensaje-cabecera">
                    <strong>
                        @if ($mensaje->tipo_emisor->value === 'cliente') Cliente
                        @elseif ($mensaje->tipo_emisor->value === 'bot') Bot Villafañe
                        @else {{ $mensaje->usuario?->nombre_usuario ?? 'Usuario interno' }}
                        @endif
                    </strong>
                    <time datetime="{{ $mensaje->fecha_hora->toIso8601String() }}">{{ $mensaje->fecha_hora->format('d/m/Y H:i') }}</time>
                </div>
                @if ($mensaje->contenido)<p>{!! nl2br(e($mensaje->contenido)) !!}</p>@endif
                @if ($mensaje->archivo_adjunto)<small class="archivo-mensaje">Adjunto: {{ $mensaje->archivo_adjunto }}</small>@endif
                <span class="estado-envio">{{ ucfirst($mensaje->estado_envio->value) }}</span>
            </article>
        @empty
            <p class="vacio">La conversación todavía no contiene mensajes.</p>
        @endforelse
    </div>
</section>
@endsection
