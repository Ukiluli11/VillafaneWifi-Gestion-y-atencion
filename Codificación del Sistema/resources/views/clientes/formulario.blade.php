@extends('layouts.panel')
@php($edicion = isset($cliente))
@section('titulo', $edicion ? 'Editar cliente' : 'Nuevo cliente')
@section('contenido')
<div class="encabezado"><div><p class="sobrelinea">Datos del cliente</p><h1>{{ $edicion ? 'Editar cliente' : 'Registrar cliente' }}</h1></div></div>
<form class="tarjeta formulario grilla" method="POST" action="{{ $edicion ? route('clientes.update', $cliente) : route('clientes.store') }}">
    @csrf @if($edicion) @method('PUT') @endif
    <label>Tipo de documento<select name="tipo_documento">@foreach(['DNI','CUIT','CUIL'] as $opcion)<option @selected(old('tipo_documento', $cliente->tipo_documento->value ?? 'DNI') === $opcion)>{{ $opcion }}</option>@endforeach</select></label>
    <label>Número<input name="numero_documento" value="{{ old('numero_documento', $cliente->numero_documento ?? '') }}" required></label>
    <label class="ancho">Nombre o razón social<input name="nombre_razon_social" value="{{ old('nombre_razon_social', $cliente->nombre_razon_social ?? '') }}" required></label>
    <label>Tipo de cliente<select name="tipo_cliente">@foreach(['particular'=>'Particular','comercio'=>'Comercio'] as $valor=>$etiqueta)<option value="{{ $valor }}" @selected(old('tipo_cliente', $cliente->tipo_cliente->value ?? 'particular') === $valor)>{{ $etiqueta }}</option>@endforeach</select></label>
    <label>WhatsApp<input name="telefono_whatsapp" value="{{ old('telefono_whatsapp', $cliente->telefono_whatsapp ?? '') }}"></label>
    <label>Calle de contacto<input name="calle_contacto" value="{{ old('calle_contacto', $cliente->calle_contacto ?? '') }}"></label>
    <label>Número<input name="numero_contacto" value="{{ old('numero_contacto', $cliente->numero_contacto ?? '') }}"></label>
    <label>Localidad<input name="localidad_contacto" value="{{ old('localidad_contacto', $cliente->localidad_contacto ?? '') }}"></label>
    @if($edicion)<label>Estado<select name="estado">@foreach(['activo','suspendido','baja'] as $estado)<option @selected(old('estado', $cliente->estado->value) === $estado)>{{ $estado }}</option>@endforeach</select></label>@endif
    <div class="acciones ancho"><a class="boton secundario" href="{{ route('clientes.index') }}">Cancelar</a><button>Guardar</button></div>
</form>
@endsection
