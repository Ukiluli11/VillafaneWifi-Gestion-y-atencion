@extends('layouts.panel')
@section('titulo', 'Conversación de ' . ($conversacion->cliente?->nombre_razon_social ?? 'contacto no identificado'))
@section('contenido')
<div class="encabezado">
    <div>
        <p class="sobrelinea">Historial de WhatsApp</p>
        <h1>{{ $conversacion->cliente?->nombre_razon_social ?? 'Registro en curso' }}</h1>
        <p>{{ $conversacion->numero_whatsapp }} · Iniciada el {{ $conversacion->fecha_hora_inicio->format('d/m/Y H:i') }}</p>
    </div>
    <div class="acciones">
        <span class="estado {{ $conversacion->estado->value }}">{{ ucfirst($conversacion->estado->value) }}</span>
        @if ($navegacionPermitida['gestionar_conversaciones']
            && $conversacion->estado->value !== 'cerrada'
            && ($conversacion->id_usuario_atencion === null
                || $conversacion->id_usuario_atencion === auth()->id()
                || auth()->user()->administrador !== null))
            <form method="POST" action="{{ route('conversaciones.cerrar', $conversacion) }}" onsubmit="return confirm('¿Cerrar esta conversación?');">
                @csrf
                <button class="peligro" type="submit">Cerrar conversación</button>
            </form>
        @endif
        <a class="boton secundario" href="{{ route('conversaciones.index') }}">Volver</a>
    </div>
</div>

<section class="resumen-conversacion" aria-label="Datos de la conversación">
    <div><small>Atención actual</small><strong>{{ $conversacion->modo_atencion->value === 'bot' ? 'Bot automático' : 'Atención humana' }}</strong></div>
    <div><small>Responsable</small><strong>{{ $conversacion->usuarioAtencion?->nombre_usuario ?? 'Sin asignar' }}</strong></div>
    <div><small>Total de mensajes</small><strong>{{ $conversacion->mensajes->count() }}</strong></div>
</section>

@if ($conversacion->estado->value === 'cerrada')
    <div class="alerta informacion" role="status">
        Conversación finalizada
        @if ($conversacion->fecha_hora_cierre)
            el <strong>{{ $conversacion->fecha_hora_cierre->format('d/m/Y H:i') }}</strong>.
        @endif
        Su historial permanece disponible para consulta.
    </div>
@endif

@if ($navegacionPermitida['gestionar_conversaciones'] && $conversacion->estado->value !== 'cerrada')
    @if ($conversacion->id_usuario_atencion === null)
        <section class="tarjeta panel-atencion-humana">
            <div>
                <h2>Atención humana disponible</h2>
                <p>Al tomarla, el bot se detendrá y el cliente recibirá un mensaje con tu usuario.</p>
            </div>
            <form method="POST" action="{{ route('conversaciones.tomar', $conversacion) }}">
                @csrf
                <button type="submit">Tomar conversación</button>
            </form>
        </section>
    @elseif ($conversacion->id_usuario_atencion === auth()->id())
        <section class="tarjeta">
            <div class="tarjeta-cabecera">
                <div><h2>Responder al cliente</h2><p>La respuesta se enviará por WhatsApp y quedará guardada en el historial.</p></div>
            </div>
            <form class="formulario" method="POST" action="{{ route('conversaciones.responder', $conversacion) }}">
                @csrf
                <label>Mensaje
                    <textarea name="contenido" rows="4" maxlength="4096" required placeholder="Escribí tu respuesta...">{{ old('contenido') }}</textarea>
                </label>
                <div class="acciones"><button type="submit">Enviar respuesta</button></div>
            </form>
        </section>
    @else
        <div class="alerta informacion" role="status">
            Esta conversación está siendo atendida por
            <strong>{{ $conversacion->usuarioAtencion?->nombre_usuario ?? 'otro usuario' }}</strong>.
        </div>
    @endif
@endif

@if ($conversacion->tickets->isNotEmpty())
<section class="tarjeta">
    <div class="tarjeta-cabecera"><div><h2>Tickets generados</h2><p>Reclamos registrados por el bot durante esta conversación.</p></div></div>
    <div class="tabla-contenedor">
        <table>
            <thead><tr><th>Ticket</th><th>Servicio</th><th>Tipo</th><th>Descripción</th><th>Fecha</th><th>Estado</th></tr></thead>
            <tbody>
            @foreach ($conversacion->tickets as $ticket)
                <tr>
                    <td><strong>#{{ $ticket->id_ticket }}</strong></td>
                    <td>{{ $ticket->servicio->calle_instalacion }} {{ $ticket->servicio->numero_instalacion }}, {{ $ticket->servicio->localidad_instalacion }}</td>
                    <td>{{ ucfirst($ticket->tipo->value) }}</td>
                    <td>{{ $ticket->descripcion }}</td>
                    <td>{{ $ticket->fecha_creacion->format('d/m/Y H:i') }}</td>
                    <td><span class="estado {{ $ticket->estado->value }}">{{ ucfirst(str_replace('_', ' ', $ticket->estado->value)) }}</span></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</section>
@endif

@if ($conversacion->avisosVencimiento->isNotEmpty())
<section class="tarjeta">
    <div class="tarjeta-cabecera"><div><h2>Avisos de vencimiento</h2><p>Notificaciones automáticas vinculadas con esta conversación.</p></div></div>
    <div class="tabla-contenedor">
        <table>
            <thead><tr><th>Cuota</th><th>Servicio</th><th>Tipo</th><th>Vencimiento</th><th>Estado</th><th>Enviado</th></tr></thead>
            <tbody>
            @foreach ($conversacion->avisosVencimiento as $aviso)
                <tr>
                    <td><strong>{{ $aviso->cuota->periodo }}</strong></td>
                    <td>{{ $aviso->cuota->servicio->plan->nombre }}</td>
                    <td>{{ $aviso->tipo->value === 'proximo' ? 'Próximo a vencer' : 'Cuota vencida' }}</td>
                    <td>{{ $aviso->cuota->fecha_vencimiento->format('d/m/Y') }}</td>
                    <td><span class="estado {{ $aviso->estado->value }}">{{ ucfirst($aviso->estado->value) }}</span></td>
                    <td>{{ $aviso->fecha_hora_envio?->format('d/m/Y H:i') ?? 'Pendiente' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</section>
@endif

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
