@extends('layouts.panel')
@section('titulo', 'Resumen')
@section('contenido')
@php
    $hoy = now('America/Argentina/Buenos_Aires')->locale('es');
    $puedeVerReportes = $navegacionPermitida['reportes'] ?? false;
    $indicadores = [
        ['titulo' => 'Clientes activos', 'valor' => number_format($clientesActivos, 0, ',', '.'), 'color' => 'verde',
            'enlace' => $navegacionPermitida['clientes'] ? route('clientes.index', ['estado' => 'activo']) : null],
        ['titulo' => 'Servicios activos', 'valor' => number_format($serviciosActivos, 0, ',', '.'), 'color' => 'azul',
            'enlace' => $navegacionPermitida['servicios'] ? route('servicios.index', ['estado' => 'activo']) : null],
        ['titulo' => 'Cuotas por cobrar', 'valor' => number_format(array_sum(array_column($cuotasPorCobrar, 'cantidad')), 0, ',', '.'), 'color' => 'ambar',
            'enlace' => '#estado-cobranza'],
        ['titulo' => 'Deuda vencida', 'valor' => '$'.number_format($cuotasPorCobrar['vencidas']['importe'], 2, ',', '.'), 'color' => 'rojo',
            'enlace' => $puedeVerReportes ? route('reportes.morosidad', ['estado_financiero' => 'en_mora']) : null],
    ];
    $accesos = [
        ['permiso' => 'gestionar_clientes', 'ruta' => 'clientes.create', 'titulo' => 'Registrar cliente', 'detalle' => 'Crear una ficha'],
        ['permiso' => 'gestionar_servicios', 'ruta' => 'servicios.create', 'titulo' => 'Asignar servicio', 'detalle' => 'Vincular un plan a un cliente'],
        ['permiso' => 'clientes', 'ruta' => 'clientes.index', 'titulo' => 'Buscar cliente', 'detalle' => 'Consultar ficha y cuenta corriente'],
        ['permiso' => 'cobranza', 'ruta' => 'cuentas-receptoras.index', 'titulo' => 'Gestionar cobranza', 'detalle' => 'Administrar cuentas receptoras'],
        ['permiso' => 'comprobantes', 'ruta' => 'comprobantes.index', 'titulo' => 'Revisar comprobantes', 'detalle' => 'Validar pagos recibidos'],
        ['permiso' => 'tickets', 'ruta' => 'tickets.index', 'titulo' => 'Ver tickets', 'detalle' => 'Dar seguimiento a reclamos'],
        ['permiso' => 'reportes', 'ruta' => 'reportes.dashboard', 'titulo' => 'Ver reportes', 'detalle' => 'Consultar métricas y exportaciones'],
    ];
@endphp

<div class="resumen-panel">
    <header class="resumen-bienvenida">
        <div>
            <h1>Hola, {{ auth()->user()->nombre_usuario }}</h1>
            <p>Clientes, conexiones y cobranzas al día de hoy.</p>
        </div>
        <time datetime="{{ $hoy->toIso8601String() }}">
            <span>{{ ucfirst($hoy->translatedFormat('l')) }}</span>
            <strong>{{ $hoy->translatedFormat('d \d\e F') }}</strong>
            <span>{{ $hoy->format('H:i') }} h</span>
        </time>
    </header>

    <section class="metricas resumen-indicadores" aria-label="Indicadores principales">
        @foreach ($indicadores as $indicador)
            @php($etiqueta = $indicador['enlace'] ? 'a' : 'article')
            <{{ $etiqueta }} class="metrica resumen-indicador resumen-{{ $indicador['color'] }}"
                @if ($indicador['enlace']) href="{{ $indicador['enlace'] }}" @endif>
                <span class="resumen-indicador-titulo">{{ $indicador['titulo'] }}</span>
                <strong>{{ $indicador['valor'] }}</strong>
                @if ($indicador['enlace'])
                    <span class="resumen-indicador-flecha" aria-hidden="true">↗</span>
                @endif
            </{{ $etiqueta }}>
        @endforeach
    </section>

    <div class="resumen-grilla">
        <section class="tarjeta resumen-cobranza" id="estado-cobranza" aria-labelledby="titulo-cobranza">
            <h2 id="titulo-cobranza">Estado de cobranza</h2>
            <fieldset class="resumen-desglose">
                <legend>Ver por</legend>
                <div class="resumen-selector">
                    <label><input type="radio" name="vista-cobranza" value="cantidad" checked><span>Cuotas</span></label>
                    <label><input type="radio" name="vista-cobranza" value="importe"><span>Importe</span></label>
                </div>
                @foreach (['cantidad', 'importe'] as $medida)
                    @php($total = array_sum(array_column($cuotasPorCobrar, $medida)))
                    <div class="resumen-desglose-{{ $medida }}">
                        @foreach (['pendientes' => 'Pendientes', 'vencidas' => 'Vencidas'] as $estado => $titulo)
                            @php($valor = $cuotasPorCobrar[$estado][$medida])
                            <div class="resumen-barra {{ $estado }}">
                                <div class="resumen-barra-datos">
                                    <span>{{ $titulo }}</span>
                                    <strong>{{ $medida === 'importe' ? '$'.number_format($valor, 2, ',', '.') : number_format($valor, 0, ',', '.') }}</strong>
                                </div>
                                <div class="resumen-barra-pista" aria-hidden="true">
                                    <span style="--avance: {{ $total > 0 ? round(100 * $valor / $total, 2) : 0 }}%"></span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </fieldset>
            @if ($puedeVerReportes)
                <a class="resumen-enlace" href="{{ route('reportes.alertas') }}">Ver alertas de vencimiento <span aria-hidden="true">→</span></a>
            @endif
        </section>

        <section class="tarjeta resumen-accesos" aria-labelledby="titulo-accesos">
            <h2 id="titulo-accesos">Accesos rápidos</h2>
            <div class="acciones-rapidas">
                @foreach ($accesos as $acceso)
                    @if ($navegacionPermitida[$acceso['permiso']] ?? false)
                        <a class="accion-rapida" href="{{ route($acceso['ruta']) }}">
                            <span><strong>{{ $acceso['titulo'] }}</strong><small>{{ $acceso['detalle'] }}</small></span>
                            <span class="resumen-acceso-flecha" aria-hidden="true">→</span>
                        </a>
                    @endif
                @endforeach
            </div>
        </section>
    </div>
</div>
@endsection
