@extends('layouts.panel')
@section('titulo', 'Simulador de WhatsApp')
@section('contenido')
<div class="encabezado">
    <div>
        <p class="sobrelinea">Entorno local seguro</p>
        <h1>Simulador de WhatsApp</h1>
        <p>Probá el mismo flujo del webhook sin utilizar el número ni la API real de Meta.</p>
    </div>
</div>

<div class="alerta informacion" role="status">
    <strong>Modo simulación activo.</strong> Los mensajes y respuestas se guardan en el historial, pero no salen de esta computadora.
</div>

<section class="tarjeta simulador-whatsapp">
    <div class="tarjeta-cabecera">
        <div><h2>Enviar mensaje de prueba</h2><p>Usá siempre el mismo número para continuar una conversación paso a paso.</p></div>
    </div>
    <form class="formulario" method="POST" action="{{ route('simulador-whatsapp.store') }}">
        @csrf
        <label>Número de WhatsApp
            <input name="numero_whatsapp" list="contactos-demostracion" value="{{ old('numero_whatsapp') }}" placeholder="Ejemplo: 5493718123401" required>
        </label>
        <datalist id="contactos-demostracion">
            @foreach ($contactos as $contacto)
                <option value="{{ $contacto->telefono_whatsapp }}">{{ $contacto->nombre_razon_social }}</option>
            @endforeach
        </datalist>
        <label>Tipo de mensaje
            <select name="tipo" required>
                <option value="text" @selected(old('tipo', 'text') === 'text')>Texto</option>
                <option value="image" @selected(old('tipo') === 'image')>Imagen simulada</option>
                <option value="document" @selected(old('tipo') === 'document')>PDF/documento simulado</option>
            </select>
        </label>
        <label>Contenido o descripción
            <textarea name="contenido" rows="5" maxlength="4096" required placeholder="Ejemplo: Hola, quiero consultar mi deuda">{{ old('contenido') }}</textarea>
        </label>
        <div class="acciones"><button type="submit">Procesar mensaje simulado</button></div>
    </form>
</section>

<section class="tarjeta ayuda-simulador">
    <h2>Escenarios sugeridos</h2>
    <ul>
        <li><strong>Cliente registrado:</strong> usá 5493718123401 y luego respondé con una opción del menú.</li>
        <li><strong>Cliente nuevo:</strong> usá un número distinto y completá el alta guiada.</li>
        <li><strong>Comprobante:</strong> elegí la opción 2 y luego enviá una imagen o documento simulado.</li>
        <li><strong>Reclamo:</strong> elegí la opción 3 y describí el problema.</li>
        <li><strong>Atención humana:</strong> elegí la opción 4.</li>
    </ul>
</section>
@endsection
