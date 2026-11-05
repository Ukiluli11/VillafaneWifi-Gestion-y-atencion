@extends('layouts.panel')
@section('titulo', 'Alertas de Vencimiento y Morosidad (RF-37)')
@section('contenido')
<div class="encabezado">
    <div>
        <p class="sobrelinea">Módulo 5 – Requerimiento Funcional RF-37</p>
        <h1>Bandeja de Alertas de Vencimiento y Cobranza</h1>
        <p>Monitoreo proactivo de vencimientos de cuotas y disparo de notificaciones automáticas por WhatsApp.</p>
    </div>
    <div>
        <a class="boton" href="{{ route('simulador-whatsapp.create') }}" style="background: #25D366; color: #fff;">
            📱 Abrir Simulador WhatsApp
        </a>
    </div>
</div>

<nav style="display: flex; gap: 10px; margin-bottom: 24px; border-bottom: 1px solid var(--borde); padding-bottom: 12px; overflow-x: auto;">
    <a href="{{ route('reportes.dashboard') }}" style="padding: 8px 16px; border-radius: 8px; background: var(--blanco); border: 1px solid var(--borde); color: var(--texto); text-decoration: none; font-weight: 500; font-size: 0.88rem;">Tablero Principal</a>
    <a href="{{ route('reportes.morosidad') }}" style="padding: 8px 16px; border-radius: 8px; background: var(--blanco); border: 1px solid var(--borde); color: var(--texto); text-decoration: none; font-weight: 500; font-size: 0.88rem;">Clientes y Morosidad (RF-34)</a>
    <a href="{{ route('reportes.ingresos') }}" style="padding: 8px 16px; border-radius: 8px; background: var(--blanco); border: 1px solid var(--borde); color: var(--texto); text-decoration: none; font-weight: 500; font-size: 0.88rem;">Cobranzas e Ingresos (RF-35)</a>
    <a href="{{ route('reportes.tickets') }}" style="padding: 8px 16px; border-radius: 8px; background: var(--blanco); border: 1px solid var(--borde); color: var(--texto); text-decoration: none; font-weight: 500; font-size: 0.88rem;">Rendimiento de Tickets (RF-36)</a>
    <a href="{{ route('reportes.alertas') }}" style="padding: 8px 16px; border-radius: 8px; background: #00838F; color: #fff; text-decoration: none; font-weight: 600; font-size: 0.88rem;">Alertas de Vencimiento (RF-37)</a>
</nav>

<section class="metricas" aria-label="Resumen de alertas">
    <article class="metrica" style="border-left: 4px solid #C62828;">
        <div class="metrica-cabecera"><span>CUOTAS VENCIDAS</span><span class="metrica-icono" style="background: #FFEBEE; color: #C62828;">!</span></div>
        <strong style="color: #C62828;">{{ $cuotas->filter(fn($c) => $c->fecha_vencimiento->isPast())->count() }}</strong>
        <small>requieren aviso urgente</small>
    </article>
    <article class="metrica" style="border-left: 4px solid #F57F17;">
        <div class="metrica-cabecera"><span>PRÓXIMAS A VENCER</span><span class="metrica-icono" style="background: #FFF9C4; color: #F57F17;">⏰</span></div>
        <strong style="color: #F57F17;">{{ $cuotas->filter(fn($c) => !$c->fecha_vencimiento->isPast())->count() }}</strong>
        <small>vencen en los próximos 7 días</small>
    </article>
    <article class="metrica" style="border-left: 4px solid #00838F;">
        <div class="metrica-cabecera"><span>MONTO TOTAL EN ALERTA</span><span class="metrica-icono" style="background: #E0F7FA; color: #00838F;">$</span></div>
        <strong>${{ number_format((float) $cuotas->sum('monto'), 2, ',', '.') }}</strong>
        <small>compromiso de cobranza en seguimiento</small>
    </article>
    <article class="metrica" style="border-left: 4px solid #25D366;">
        <div class="metrica-cabecera"><span>CANAL AUTOMATIZADO</span><span class="metrica-icono" style="background: #E8F5E9; color: #25D366;">WA</span></div>
        <strong style="color: #2E7D32;">Meta API</strong>
        <small>Notificación directa vía WhatsApp</small>
    </article>
</section>

<article class="tarjeta" style="margin: 0;">
    <div class="tarjeta-cabecera">
        <div>
            <h2 style="font-size: 1.1rem; margin: 0;">Listado de Alertas Operativas</h2>
            <p>Avisos preventivos y de mora para regularización de cuotas</p>
        </div>
    </div>

    @if ($cuotas->isEmpty())
        <p style="padding: 24px; text-align: center; color: var(--texto-suave);">🎉 No se registran alertas de cuotas impagas en este momento.</p>
    @else
        <div class="tabla-contenedor" style="margin: 0;">
            <table class="tabla" style="width: 100%;">
                <thead>
                    <tr>
                        <th># Cuota</th>
                        <th>Cliente / Titular</th>
                        <th>Contacto WhatsApp</th>
                        <th>Plan Contratado</th>
                        <th>Fecha Vencimiento</th>
                        <th style="text-align: right;">Importe</th>
                        <th>Nivel de Alerta</th>
                        <th style="text-align: right;">Acción de Notificación</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($cuotas as $cuota)
                        @php
                            $esVencida = $cuota->fecha_vencimiento->isPast();
                            $cliente = $cuota->servicio?->cliente;
                        @endphp
                        <tr>
                            <td><strong>#{{ $cuota->id_cuota }}</strong></td>
                            <td>
                                @if ($cliente)
                                    <a href="{{ route('cuentas.show', $cliente->id_cliente) }}" style="color: inherit; font-weight: 600; text-decoration: underline;">
                                        {{ $cliente->nombre_razon_social }}
                                    </a>
                                    <br><small style="color: var(--texto-suave);">DNI: {{ $cliente->numero_documento }}</small>
                                @else
                                    <span>Cliente #{{ $cuota->id_servicio }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($cliente?->telefono_whatsapp)
                                    <strong>{{ $cliente->telefono_whatsapp }}</strong>
                                @else
                                    <span style="color: #C62828;">Sin número</span>
                                @endif
                            </td>
                            <td>{{ $cuota->servicio?->plan?->nombre ?? 'Plan General' }}</td>
                            <td>
                                <strong style="{{ $esVencida ? 'color: #C62828;' : 'color: #F57F17;' }}">
                                    {{ $cuota->fecha_vencimiento->format('d/m/Y') }}
                                </strong>
                            </td>
                            <td style="text-align: right; font-weight: 700;">
                                ${{ number_format((float) $cuota->monto, 2, ',', '.') }}
                            </td>
                            <td>
                                @if ($esVencida)
                                    <span style="background: #FFEBEE; color: #C62828; padding: 3px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 700;">
                                        MORA VENCIDA
                                    </span>
                                @else
                                    <span style="background: #FFF9C4; color: #F57F17; padding: 3px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 700;">
                                        PRÓXIMO VENCIMIENTO
                                    </span>
                                @endif
                            </td>
                            <td style="text-align: right;">
                                <form method="POST" action="{{ route('reportes.alertas.notificar') }}" style="display: inline;">
                                    @csrf
                                    <input type="hidden" name="id_cuota" value="{{ $cuota->id_cuota }}">
                                    <button type="submit" class="boton" style="padding: 4px 10px; font-size: 0.78rem; background: #25D366; color: #fff;">
                                        💬 Disparar Aviso WhatsApp
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</article>
@endsection
