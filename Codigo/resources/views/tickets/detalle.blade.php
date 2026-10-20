@extends('layouts.panel')
@section('titulo', 'Ticket #' . $ticket->id_ticket . ' - ' . ucfirst(str_replace('_', ' ', $ticket->estado->value)))
@section('contenido')
<section class="pagina-tickets">
<div class="encabezado">
    <div>
        <p class="sobrelinea">Gestión de Soporte Técnico</p>
        <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
            <h1 style="margin: 0;">Ticket #{{ $ticket->id_ticket }}</h1>
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
            <span style="display: inline-flex; align-items: center; gap: 0.4rem; font-weight: 700; font-size: 0.85rem; padding: 0.35rem 0.8rem; border-radius: 9999px; background: {{ $colorEstado }}20; color: {{ $colorEstado }};">
                <span style="width: 8px; height: 8px; border-radius: 50%; background: {{ $colorEstado }};"></span>
                {{ strtoupper(str_replace('_', ' ', $ticket->estado->value)) }}
            </span>
            <span style="font-size: 0.8rem; text-transform: uppercase; font-weight: 600; padding: 0.25rem 0.6rem; background: rgba(0,0,0,0.06); border-radius: 4px;">
                Tipo: {{ $ticket->tipo }}
            </span>
        </div>
        <p style="margin-top: 0.5rem; color: var(--texto-suave);">
            Creado el {{ $ticket->fecha_creacion?->format('d/m/Y H:i') }} hs
            @if ($ticket->fecha_asignacion)
                · Asignado: {{ $ticket->fecha_asignacion->format('d/m/Y H:i') }} hs
            @endif
            @if ($ticket->fecha_resolucion)
                · Resuelto: {{ $ticket->fecha_resolucion->format('d/m/Y H:i') }} hs
            @endif
        </p>
    </div>
    <a class="boton secundario" href="{{ route('tickets.index') }}">← Volver a la bandeja</a>
</div>

{{-- Disposición en dos columnas: Izquierda (Detalles y Chat) / Derecha (Acciones y Notas) --}}
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 340px), 1fr)); gap: 1.5rem; margin-top: 1rem;">

    {{-- Columna Izquierda: Información técnica del incidente y cliente --}}
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">

        {{-- Tarjeta de Descripción del Problema --}}
        <div class="tarjeta" style="padding: 1.25rem;">
            <h3 style="margin-top: 0; font-size: 1.05rem; display: flex; align-items: center; gap: 0.5rem;">
                <svg viewBox="0 0 24 24" style="width: 20px; height: 20px; fill: none; stroke: currentColor; stroke-width: 2;" aria-hidden="true"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                Motivo del Reclamo / Síntoma
            </h3>
            <div style="background: rgba(0,0,0,0.03); border-left: 3px solid #3b82f6; padding: 1rem; border-radius: 4px; font-size: 0.95rem; line-height: 1.5;">
                {{ $ticket->descripcion }}
            </div>
        </div>

        {{-- Tarjeta de Información del Cliente y Conexión --}}
        <div class="tarjeta" style="padding: 1.25rem;">
            <h3 style="margin-top: 0; font-size: 1.05rem; display: flex; align-items: center; gap: 0.5rem;">
                <svg viewBox="0 0 24 24" style="width: 20px; height: 20px; fill: none; stroke: currentColor; stroke-width: 2;" aria-hidden="true"><path d="M16 20v-1.5a4.5 4.5 0 0 0-4.5-4.5h-4A4.5 4.5 0 0 0 3 18.5V20M9.5 10a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm8-1a3 3 0 0 1 0 6m3.5 5v-1a4 4 0 0 0-3-3.87"/></svg>
                Datos del Cliente y Conexión
            </h3>

            @if ($ticket->servicio && $ticket->servicio->cliente)
                @php $cliente = $ticket->servicio->cliente; $servicio = $ticket->servicio; @endphp
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; font-size: 0.9rem;">
                    <div>
                        <span class="texto-suave" style="font-size: 0.8rem; display: block;">Cliente</span>
                        <strong>{{ $cliente->nombre_razon_social }}</strong>
                    </div>
                    <div>
                        <span class="texto-suave" style="font-size: 0.8rem; display: block;">Documento / CUIT</span>
                        <span>{{ $cliente->dni_cuit ?? $cliente->numero_documento ?? 'S/D' }}</span>
                    </div>
                    <div>
                        <span class="texto-suave" style="font-size: 0.8rem; display: block;">WhatsApp de Contacto</span>
                        @if ($cliente->telefono_whatsapp)
                            <a href="https://wa.me/{{ $cliente->telefono_whatsapp }}" target="_blank" style="color: #16a34a; font-weight: 600; text-decoration: none;">
                                +{{ $cliente->telefono_whatsapp }} ↗
                            </a>
                        @else
                            <span>No registrado</span>
                        @endif
                    </div>
                    <div>
                        <span class="texto-suave" style="font-size: 0.8rem; display: block;">Plan Contratado</span>
                        <strong>{{ $servicio->plan?->nombre ?? 'Plan Estándar' }}</strong>
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <span class="texto-suave" style="font-size: 0.8rem; display: block;">Domicilio de Instalación</span>
                        <span>{{ $servicio->calle_instalacion }} {{ $servicio->numero_instalacion }} ({{ $servicio->localidad_instalacion }})</span>
                    </div>
                    <div>
                        <span class="texto-suave" style="font-size: 0.8rem; display: block;">Dirección IPv4</span>
                        <code>{{ $servicio->ipv4 ?? 'Asignación DHCP' }}</code>
                    </div>
                    <div>
                        <span class="texto-suave" style="font-size: 0.8rem; display: block;">Dirección MAC</span>
                        <code>{{ $servicio->mac ?? 'S/D' }}</code>
                    </div>
                </div>
            @else
                <p class="texto-suave">Sin servicio o cliente vinculado.</p>
            @endif
        </div>

        {{-- Tarjeta de Conversación de WhatsApp Asociada --}}
        <div class="tarjeta" style="padding: 1.25rem;">
            <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                <h3 style="margin: 0; font-size: 1.05rem; display: flex; align-items: center; gap: 0.5rem;">
                    <svg viewBox="0 0 24 24" style="width: 20px; height: 20px; fill: none; stroke: currentColor; stroke-width: 2;" aria-hidden="true"><path d="M4 5h16v11H8l-4 4zM8 9h8M8 12h5"/></svg>
                    Historial de WhatsApp Vinculado
                </h3>
                @if ($ticket->conversacion)
                    <a class="enlace-tabla" href="{{ route('conversaciones.show', $ticket->conversacion) }}" target="_blank">Ver conversación completa ↗</a>
                @endif
            </div>

            @if ($ticket->conversacion)
                <div style="background: rgba(0,0,0,0.02); border-radius: 8px; padding: 0.75rem; max-height: 280px; overflow-y: auto; display: flex; flex-direction: column; gap: 0.5rem;">
                    @forelse ($ticket->conversacion->mensajes as $msg)
                        @php
                            $tipoEmisor = is_object($msg->tipo_emisor) ? $msg->tipo_emisor->value : (string) $msg->tipo_emisor;
                            $esCliente = $tipoEmisor === 'cliente';
                        @endphp
                        <div style="display: flex; flex-direction: column; align-self: {{ $esCliente ? 'flex-start' : 'flex-end' }}; max-width: 80%;">
                            <div style="background: {{ $esCliente ? '#ffffff' : '#dcf8c6' }}; color: #1f2937; padding: 0.5rem 0.75rem; border-radius: 8px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); font-size: 0.85rem;">
                                <small style="display: block; font-weight: 700; color: {{ $esCliente ? '#2563eb' : '#059669' }}; margin-bottom: 0.2rem;">
                                    {{ $esCliente ? 'Cliente' : ($msg->usuario?->nombre_usuario ?? 'Bot de WhatsApp') }}
                                </small>
                                <span>{{ $msg->contenido }}</span>
                            </div>
                            <span style="font-size: 0.7rem; color: #94a3b8; align-self: {{ $esCliente ? 'flex-start' : 'flex-end' }}; margin-top: 0.15rem;">
                                {{ $msg->fecha_hora?->format('H:i') }} hs
                            </span>
                        </div>
                    @empty
                        <p class="texto-suave" style="text-align: center; margin: 1rem 0;">No se registraron mensajes en esta conversación.</p>
                    @endforelse
                </div>
            @else
                <div style="text-align: center; padding: 1.5rem 1rem; background: rgba(0,0,0,0.02); border-radius: 6px;">
                    <p class="texto-suave" style="margin: 0; font-size: 0.9rem;">
                        Este ticket fue generado de forma manual desde el panel técnico o por vía telefónica.
                    </p>
                </div>
            @endif
        </div>

    </div>

    {{-- Columna Derecha: Control de Ciclo de Vida y Bitácora de Notas Internas --}}
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">

        {{-- Tarjeta de Asignación y Ciclo de Vida (RF-29, RF-30, RF-31) --}}
        @if ($navegacionPermitida['gestionar_tickets'] ?? false)
            <div class="tarjeta" style="padding: 1.25rem;">
                <h3 style="margin-top: 0; font-size: 1.05rem; display: flex; align-items: center; gap: 0.5rem;">
                    <svg viewBox="0 0 24 24" style="width: 20px; height: 20px; fill: none; stroke: currentColor; stroke-width: 2;" aria-hidden="true"><path d="M12 6V2M12 22v-4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                    Control y Acciones del Ticket
                </h3>

                {{-- Asignación a técnico --}}
                @if ($puedeAsignar)
                <form method="POST" action="{{ route('tickets.asignar', $ticket) }}" style="margin-bottom: 1.25rem; padding-bottom: 1rem; border-bottom: 1px solid var(--borde);">
                    @csrf
                    <label for="id_usuario_asignado" style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 0.4rem;">
                        Técnico Asignado
                    </label>
                    <div style="display: flex; gap: 0.5rem;">
                        <select id="id_usuario_asignado" name="id_usuario_asignado" required style="flex: 1;">
                            <option value="">-- Seleccionar técnico --</option>
                            @foreach ($tecnicos as $tec)
                                <option value="{{ $tec->id_usuario }}" @selected($ticket->id_usuario_asignado == $tec->id_usuario)>
                                    {{ $tec->nombre_usuario }}
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="boton" style="white-space: nowrap;">
                            {{ $ticket->id_usuario_asignado ? 'Reasignar' : 'Asignar' }}
                        </button>
                    </div>
                </form>
                @endif

                {{-- Botones de Transición de Estados --}}
                @if ($puedeGestionar)
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <span style="font-size: 0.85rem; font-weight: 600;">Transición de Estado:</span>

                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                        @if (in_array($ticket->estado->value, ['asignado', 'reabierto']))
                            <form method="POST" action="{{ route('tickets.estado', $ticket) }}" style="display: inline;">
                                @csrf
                                <input type="hidden" name="estado" value="en_proceso">
                                <button type="submit" class="boton">
                                    ⚙ Iniciar Atención (En Proceso)
                                </button>
                            </form>
                        @endif

                        @if (in_array($ticket->estado->value, ['asignado', 'en_proceso', 'reabierto']))
                            {{-- Formulario para Resolver Ticket con notificación opcional --}}
                            <button type="button" class="boton" onclick="document.getElementById('modal-resolver').style.display='block'">
                                ✓ Marcar Resuelto
                            </button>
                        @endif

                        @if ($ticket->estado->value === 'resuelto')
                            <form method="POST" action="{{ route('tickets.estado', $ticket) }}" style="display: inline;">
                                @csrf
                                <input type="hidden" name="estado" value="cerrado">
                                <button type="submit" class="boton secundario" onclick="return confirm('¿Confirmás el cierre definitivo del ticket?');">
                                    Cerrar Definitivamente
                                </button>
                            </form>
                        @endif

                        @if (in_array($ticket->estado->value, ['resuelto', 'cerrado']))
                            {{-- Botón de Reapertura --}}
                            <button type="button" class="boton peligro" style="border-color: var(--peligro);" onclick="document.getElementById('modal-reabrir').style.display='block'">
                                ↺ Reabrir Ticket
                            </button>
                        @endif
                    </div>
                    @if ($ticket->estado->value === 'pendiente')
                        <p class="texto-suave">Asigná un responsable para iniciar la atención del ticket.</p>
                    @endif
                </div>

                {{-- Modal desplegable para Resolver con detalle --}}
                <div id="modal-resolver" style="display: none; margin-top: 1rem; padding: 1rem; background: rgba(16, 185, 129, 0.08); border: 1px solid #10b981; border-radius: 6px;">
                    <h4 style="margin: 0 0 0.5rem 0; color: var(--verde-700);">Confirmar Solución del Incidente</h4>
                    <form method="POST" action="{{ route('tickets.estado', $ticket) }}">
                        @csrf
                        <input type="hidden" name="estado" value="resuelto">
                        <div class="campo" style="margin-bottom: 0.75rem;">
                            <label for="obs-resolver" style="font-size: 0.8rem; font-weight: 600;">Detalle técnico de la solución:</label>
                            <textarea id="obs-resolver" name="observacion" rows="2" placeholder="Ej: Se reemplazó conector RJ45 y se restableció el enlace óptico..." required></textarea>
                        </div>
                        <div style="margin-bottom: 0.75rem;">
                            <label style="font-size: 0.85rem; display: flex; align-items: center; gap: 0.4rem; cursor: pointer;">
                                <input type="checkbox" name="notificar_whatsapp" value="1" @disabled(! config('services.tickets.notificar_resultado'))>
                                <span>Notificar al cliente por WhatsApp (requiere que haya escrito en las últimas 24 horas)</span>
                            </label>
                            @if (! config('services.tickets.notificar_resultado'))
                                <small class="texto-suave">Los avisos de tickets están desactivados en esta instalación.</small>
                            @endif
                        </div>
                        <div style="display: flex; gap: 0.5rem;">
                            <button type="submit" class="boton">Guardar y Resolver</button>
                            <button type="button" class="boton secundario" onclick="document.getElementById('modal-resolver').style.display='none'">Cancelar</button>
                        </div>
                    </form>
                </div>

                {{-- Modal desplegable para Reabrir ticket --}}
                <div id="modal-reabrir" style="display: none; margin-top: 1rem; padding: 1rem; background: rgba(239, 68, 68, 0.08); border: 1px solid #ef4444; border-radius: 6px;">
                    <h4 style="margin: 0 0 0.5rem 0; color: var(--peligro);">Reapertura de Ticket no Solucionado</h4>
                    <form method="POST" action="{{ route('tickets.reabrir', $ticket) }}">
                        @csrf
                        <div class="campo" style="margin-bottom: 0.75rem;">
                            <label for="motivo-reabrir" style="font-size: 0.8rem; font-weight: 600;">Motivo formal de la reapertura *:</label>
                            <textarea id="motivo-reabrir" name="motivo" rows="2" placeholder="Ej: El cliente manifiesta que luego de 2 horas volvió a sufrir pérdida de paquetes..." required></textarea>
                        </div>
                        <div style="display: flex; gap: 0.5rem;">
                            <button type="submit" class="boton peligro" style="border-color: var(--peligro);">Confirmar Reapertura</button>
                            <button type="button" class="boton secundario" onclick="document.getElementById('modal-reabrir').style.display='none'">Cancelar</button>
                        </div>
                    </form>
                </div>

                @endif
            </div>
        @endif

        {{-- Tarjeta de Notas Internas Técnicas --}}
        <div class="tarjeta" style="padding: 1.25rem;">
            <h3 style="margin-top: 0; font-size: 1.05rem; display: flex; align-items: center; gap: 0.5rem;">
                <svg viewBox="0 0 24 24" style="width: 20px; height: 20px; fill: none; stroke: currentColor; stroke-width: 2;" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                Notas Internas y Bitácora Técnica
            </h3>
            <p class="texto-suave" style="font-size: 0.85rem; margin-top: -0.25rem; margin-bottom: 1rem;">
                Anotaciones confidenciales registradas por el personal operativo para seguimiento interno del incidente.
            </p>

            {{-- Formulario para redactar nueva nota --}}
            @if (($navegacionPermitida['gestionar_tickets'] ?? false) && $puedeGestionar)
                <form method="POST" action="{{ route('tickets.notas.store', $ticket) }}" style="margin-bottom: 1.25rem;">
                    @csrf
                    <div class="campo">
                        <textarea name="contenido" rows="2" placeholder="Escribir una nota interna de seguimiento técnico..." required style="font-size: 0.88rem;"></textarea>
                    </div>
                    <button type="submit" class="boton secundario" style="margin-top: 0.5rem; font-size: 0.85rem; padding: 0.4rem 0.8rem;">
                        + Agregar Nota Interna
                    </button>
                </form>
            @endif

            {{-- Listado cronológico de notas --}}
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                @forelse ($ticket->notas->sortByDesc('fecha_hora') as $nota)
                    <div style="background: rgba(0,0,0,0.02); border-left: 3px solid #64748b; padding: 0.75rem 1rem; border-radius: 4px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem; font-size: 0.8rem;">
                            <strong>{{ $nota->usuario?->nombre_usuario ?? 'Sistema' }}</strong>
                            <span class="texto-suave">{{ $nota->fecha_hora?->format('d/m/Y H:i') }} hs</span>
                        </div>
                        <div style="font-size: 0.88rem; line-height: 1.4; color: var(--texto);">
                            {{ $nota->contenido }}
                        </div>
                    </div>
                @empty
                    <p class="texto-suave" style="font-size: 0.85rem; text-align: center; margin: 1rem 0;">
                        No hay notas internas asentadas en este ticket.
                    </p>
                @endforelse
            </div>
        </div>

    </div>

</div>
</section>
@endsection
