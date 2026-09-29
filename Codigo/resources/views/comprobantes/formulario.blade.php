@extends('layouts.panel')
@section('titulo', 'Cargar Comprobante Manual')
@section('contenido')
<div class="encabezado">
    <div>
        <p class="sobrelinea">Recepción de Pagos</p>
        <h1>Cargar Comprobante Manual</h1>
        <p>Adjuntá el comprobante de transferencia o Mercado Pago. El sistema calculará el hash antifraude y extraerá los datos.</p>
    </div>
    <a class="boton secundario" href="{{ route('comprobantes.index') }}">← Volver a la bandeja</a>
</div>

<form class="formulario tarjeta" method="POST" action="{{ route('comprobantes.store') }}" enctype="multipart/form-data" style="max-width: 650px;">
    @csrf

    <div class="campo">
        <label for="id_cliente">Cliente titular *</label>
        <select id="id_cliente" name="id_cliente" required>
            <option value="">-- Seleccionar cliente --</option>
            @foreach ($clientes as $c)
                <option value="{{ $c->id_cliente }}" {{ old('id_cliente', request('cliente')) == $c->id_cliente ? 'selected' : '' }}>
                    {{ $c->nombre_razon_social }} ({{ $c->tipo_documento->value }}: {{ $c->numero_documento }})
                </option>
            @endforeach
        </select>
        <small style="color: #64748b;">Seleccioná el cliente al que se le imputará el pago una vez conciliado.</small>
    </div>

    <div class="campo" style="margin-top: 1rem;">
        <label for="archivo">Archivo del Comprobante (Imagen o PDF) *</label>
        <input type="file" id="archivo" name="archivo" accept="image/jpeg,image/png,image/jpg,application/pdf" required>
        <small style="color: #64748b;">Formatos aceptados: JPG, PNG o PDF. Máximo 10 MB. Se generará un hash SHA-256 para evitar duplicados.</small>
    </div>

    <div class="grilla-dos-campos separacion-superior">
        <div class="campo">
            <label for="monto">Monto pagado ($)</label>
            <input type="number" step="0.01" id="monto" name="monto" value="{{ old('monto') }}" placeholder="Ej: 15000.00">
            <small style="color: #64748b;">Opcional: Si se deja en blanco, el OCR intentará detectarlo.</small>
        </div>

        <div class="campo">
            <label for="fecha">Fecha del pago</label>
            <input type="date" id="fecha" name="fecha" value="{{ old('fecha', date('Y-m-d')) }}">
            <small style="color: #64748b;">Fecha en que se realizó la transferencia.</small>
        </div>
    </div>

    <div class="campo" style="margin-top: 1rem;">
        <label for="numero_operacion">Número de Operación / Transferencia</label>
        <input type="text" id="numero_operacion" name="numero_operacion" value="{{ old('numero_operacion') }}" placeholder="Ej: 12345678901">
        <small style="color: #64748b;">Opcional: Código de operación provisto por Mercado Pago o la entidad bancaria.</small>
    </div>

    <div class="acciones-formulario">
        <button class="boton" type="submit">Registrar Comprobante</button>
        <a class="boton secundario" href="{{ route('comprobantes.index') }}">Cancelar</a>
    </div>
</form>
@endsection
