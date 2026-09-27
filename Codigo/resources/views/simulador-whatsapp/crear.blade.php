@extends('layouts.panel')
@section('titulo', 'Simulador WhatsApp')
@section('contenido')
<div class="encabezado">
    <div>
        <p class="sobrelinea">Entorno local</p>
        <h1>Simulador de WhatsApp</h1>
        <p>Probá los flujos del Módulo 2 sin credenciales, número real ni consumo de OpenAI.</p>
    </div>
</div>

<section class="tarjeta">
    <form class="formulario" method="POST" action="{{ route('simulador-whatsapp.store') }}">
        @csrf
        <label>Número del cliente
            <input name="telefono" value="{{ old('telefono', '5493704000000') }}" required placeholder="5493704000000">
        </label>
        <label>Tipo de mensaje
            <select name="tipo" required>
                <option value="texto" @selected(old('tipo') === 'texto')>Texto</option>
                <option value="imagen" @selected(old('tipo') === 'imagen')>Imagen de comprobante simulada</option>
                <option value="documento" @selected(old('tipo') === 'documento')>PDF de comprobante simulado</option>
            </select>
        </label>
        <label>Contenido
            <textarea name="contenido" rows="5" maxlength="4096" placeholder="Ejemplo: 1, quiero registrar un reclamo, o un DNI">{{ old('contenido') }}</textarea>
        </label>
        <p class="texto-suave">Opciones del menú: 1 cuenta, 2 reclamo, 3 comprobante, 4 operador. Para adjuntos, primero enviá la opción 3.</p>
        <div class="acciones"><button type="submit">Procesar mensaje</button></div>
    </form>
</section>
@endsection
