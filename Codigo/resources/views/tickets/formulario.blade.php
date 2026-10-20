@extends('layouts.panel')
@section('titulo', 'Registrar Ticket de Soporte')
@section('contenido')
<div class="encabezado">
    <div>
        <p class="sobrelinea">Soporte Técnico y Reclamos</p>
        <h1>Registrar Nuevo Ticket</h1>
        <p>Generá manualmente una solicitud de servicio o reporte técnico para un cliente (RF-27).</p>
    </div>
    <a class="boton secundario" href="{{ route('tickets.index') }}">← Volver a la bandeja</a>
</div>

<form class="formulario tarjeta" method="POST" action="{{ route('tickets.store') }}" style="max-width: 720px;">
    @csrf

    <div class="campo">
        <label for="id_servicio">Servicio y Cliente Afectado *</label>
        <select id="id_servicio" name="id_servicio" required>
            <option value="">-- Seleccionar servicio del cliente --</option>
            @foreach ($clientes as $cliente)
                <optgroup label="{{ $cliente->nombre_razon_social }} (DNI/CUIT: {{ $cliente->numero_documento ?? $cliente->dni_cuit ?? 'S/D' }})">
                    @foreach ($cliente->servicios as $servicio)
                        <option value="{{ $servicio->id_servicio }}" @selected(old('id_servicio', $idServicioSeleccionado) == $servicio->id_servicio)>
                            {{ $servicio->plan?->nombre ?? 'Plan' }} — {{ $servicio->calle_instalacion }} {{ $servicio->numero_instalacion }} ({{ $servicio->localidad_instalacion }}) [IP: {{ $servicio->ipv4 }}]
                        </option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
        <small class="texto-suave">Seleccioná la conexión sobre la cual se está reportando el inconveniente.</small>
    </div>

    <div class="grilla-dos-campos separacion-superior" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem;">
        <div class="campo">
            <label for="tipo">Tipo de Incidente *</label>
            <select id="tipo" name="tipo" required>
                <option value="tecnico" @selected(old('tipo') === 'tecnico')>Soporte Técnico (Sin conexión / lentitud)</option>
                <option value="instalacion" @selected(old('tipo') === 'instalacion')>Instalación / Cambio de equipo</option>
                <option value="reclamo" @selected(old('tipo') === 'reclamo')>Reclamo de facturación / servicio</option>
                <option value="administrativo" @selected(old('tipo') === 'administrativo')>Trámite Administrativo</option>
            </select>
            <small class="texto-suave">Categoría para clasificación del ticket.</small>
        </div>

        <div class="campo">
            <label for="id_usuario_asignado">Asignar a Técnico (Opcional)</label>
            <select id="id_usuario_asignado" name="id_usuario_asignado">
                <option value="">-- Dejar pendiente sin asignar --</option>
                @foreach ($tecnicos as $tec)
                    <option value="{{ $tec->id_usuario }}" @selected(old('id_usuario_asignado') == $tec->id_usuario)>
                        {{ $tec->nombre_usuario }}
                    </option>
                @endforeach
            </select>
            <small class="texto-suave">Si se asigna ahora, el ticket pasará automáticamente a estado "Asignado".</small>
        </div>
    </div>

    <div class="campo" style="margin-top: 1rem;">
        <label for="descripcion">Descripción del Problema o Reclamo *</label>
        <textarea id="descripcion" name="descripcion" rows="4" required placeholder="Detallá los síntomas reportados por el cliente (ej: luces rojas en router, microcortes, baja velocidad, etc.)...">{{ old('descripcion') }}</textarea>
        <small class="texto-suave">Explicación clara del síntoma o motivo de la visita técnica.</small>
    </div>

    <div class="campo" style="margin-top: 1rem;">
        <label for="nota_inicial">Nota Interna Inicial (Opcional)</label>
        <textarea id="nota_inicial" name="nota_inicial" rows="2" placeholder="Observaciones internas solo visibles para el equipo técnico...">{{ old('nota_inicial') }}</textarea>
        <small class="texto-suave">Comentario confidencial para el equipo técnico (no visible al cliente).</small>
    </div>

    <div class="acciones-formulario" style="margin-top: 1.5rem; display: flex; gap: 0.75rem;">
        <button class="boton" type="submit">Generar Ticket de Soporte</button>
        <a class="boton secundario" href="{{ route('tickets.index') }}">Cancelar</a>
    </div>
</form>
@endsection
