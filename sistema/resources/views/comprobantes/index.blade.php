@extends('layouts.panel')
@section('titulo', 'Comprobantes')
@section('contenido')
<div class="encabezado">
    <div>
        <p class="sobrelinea">Pagos informados por WhatsApp</p>
        <h1>Comprobantes</h1>
        <p>Revisá los archivos recibidos por el bot y su estado de validación.</p>
    </div>
</div>

<form class="buscador filtros-conversaciones" method="GET" role="search">
    <input name="buscar" value="{{ request('buscar') }}" aria-label="Buscar comprobantes" placeholder="Buscar por cliente u operación">
    <select name="estado" aria-label="Filtrar por estado">
        <option value="">Todos los estados</option>
        @foreach (['pendiente' => 'Pendientes', 'aprobado' => 'Aprobados', 'rechazado' => 'Rechazados', 'duplicado' => 'Duplicados'] as $valor => $etiqueta)
            <option value="{{ $valor }}" @selected(request('estado') === $valor)>{{ $etiqueta }}</option>
        @endforeach
    </select>
    <button type="submit">Filtrar</button>
</form>

<div class="tabla-contenedor">
    <table>
        <thead><tr><th>Cliente</th><th>Recibido</th><th>Archivo</th><th>Datos extraídos</th><th>Estado</th><th></th></tr></thead>
        <tbody>
        @forelse ($comprobantes as $comprobante)
            @php($conversacion = $comprobante->mensaje->conversacion)
            <tr>
                <td class="celda-principal"><strong>{{ $conversacion->cliente->nombre_razon_social }}</strong><small>{{ $conversacion->numero_whatsapp }}</small></td>
                <td>{{ $comprobante->fecha_recepcion->format('d/m/Y H:i') }}</td>
                <td>{{ $comprobante->mensaje->tipo->value === 'imagen' ? 'Imagen' : 'Documento' }}<br><small>{{ $comprobante->mensaje->archivo_adjunto }}</small></td>
                <td>@if($comprobante->monto_ocr)$ {{ number_format((float) $comprobante->monto_ocr, 2, ',', '.') }}@else Sin procesar @endif</td>
                <td><span class="estado {{ $comprobante->estado_validacion->value }}">{{ ucfirst($comprobante->estado_validacion->value) }}</span></td>
                <td><a class="enlace-tabla" href="{{ route('conversaciones.show', $conversacion) }}">Ver conversación →</a></td>
            </tr>
        @empty
            <tr><td class="vacio" colspan="6">No se encontraron comprobantes con esos criterios.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $comprobantes->links() }}
@endsection
