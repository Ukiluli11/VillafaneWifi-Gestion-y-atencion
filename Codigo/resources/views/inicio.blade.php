@extends('layouts.panel')
@section('titulo', 'Resumen')
@section('contenido')
<div class="encabezado">
    <div>
        <p class="sobrelinea">Vista general</p>
        <h1>Hola, {{ auth()->user()->nombre_usuario }}</h1>
        <p>Esta es la situación actual de clientes, conexiones y cobranzas.</p>
    </div>
</div>

<section class="metricas" aria-label="Indicadores principales">
    <article class="metrica">
        <div class="metrica-cabecera"><span>Clientes activos</span><span class="metrica-icono">CL</span></div>
        <strong>{{ $clientesActivos }}</strong>
        <small>con acceso vigente</small>
    </article>
    <article class="metrica">
        <div class="metrica-cabecera"><span>Servicios activos</span><span class="metrica-icono">SV</span></div>
        <strong>{{ $serviciosActivos }}</strong>
        <small>conexiones operativas</small>
    </article>
    <article class="metrica">
        <div class="metrica-cabecera"><span>Cuotas por cobrar</span><span class="metrica-icono">CT</span></div>
        <strong>{{ $cuotasPendientes }}</strong>
        <small>pendientes o vencidas</small>
    </article>
    <article class="metrica">
        <div class="metrica-cabecera"><span>Deuda vencida</span><span class="metrica-icono">$</span></div>
        <strong>${{ number_format((float) $deudaVencida, 2, ',', '.') }}</strong>
        <small>saldo que requiere seguimiento</small>
    </article>
</section>

<section class="grilla-inicio">
    <article class="tarjeta">
        <div class="tarjeta-cabecera">
            <div><h2>Accesos rápidos</h2><p>Las tareas más frecuentes, a un clic.</p></div>
        </div>
        <div class="acciones-rapidas">
            @if ($navegacionPermitida['gestionar_clientes'])
                <a class="accion-rapida" href="{{ route('clientes.create') }}"><span>+</span><span><strong>Registrar cliente</strong><small>Crear una nueva ficha</small></span></a>
            @endif
            @if ($navegacionPermitida['gestionar_servicios'])
                <a class="accion-rapida" href="{{ route('servicios.create') }}"><span>+</span><span><strong>Asignar servicio</strong><small>Conectar cliente y plan</small></span></a>
            @endif
            @if ($navegacionPermitida['clientes'])
                <a class="accion-rapida" href="{{ route('clientes.index') }}"><span>→</span><span><strong>Buscar cliente</strong><small>Ficha y cuenta corriente</small></span></a>
            @endif
            @if ($navegacionPermitida['cobranza'])
                <a class="accion-rapida" href="{{ route('cuentas-receptoras.index') }}"><span>$</span><span><strong>Gestionar cobranza</strong><small>Cuentas receptoras</small></span></a>
            @endif
        </div>
    </article>
    <article class="tarjeta panel-estado">
        <div>
            <span class="sello">● Operación disponible</span>
            <h2 style="margin-top: 22px">Gestión centralizada</h2>
            <p>Clientes, planes, servicios y cobranzas comparten una única base de datos.</p>
        </div>
        <small>Actualizado {{ now()->translatedFormat('d \d\e F, H:i') }}</small>
    </article>
</section>
@endsection
