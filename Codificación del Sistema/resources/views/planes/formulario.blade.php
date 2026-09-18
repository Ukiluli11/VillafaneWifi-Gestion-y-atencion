@extends('layouts.panel')
@php($edicion = isset($plan))
@section('titulo', $edicion ? 'Editar plan' : 'Nuevo plan')
@section('contenido')
<div class="encabezado"><div><p class="sobrelinea">Catálogo comercial</p><h1>{{ $edicion ? 'Editar plan' : 'Registrar plan' }}</h1></div></div>
<form class="tarjeta formulario" method="POST" action="{{ $edicion ? route('planes.update',$plan) : route('planes.store') }}">@csrf @if($edicion) @method('PUT') @endif
<label>Nombre<input name="nombre" value="{{ old('nombre',$plan->nombre ?? '') }}" required></label><label>Velocidad<input name="velocidad" placeholder="10/5 Mbps" value="{{ old('velocidad',$plan->velocidad ?? '') }}" required></label><label>Precio vigente<input type="number" step="0.01" min="0.01" name="precio_vigente" value="{{ old('precio_vigente',$plan->precio_vigente ?? '') }}" required></label>
@if($edicion)<label>Estado<select name="estado">@foreach(['activo','inactivo'] as $estado)<option @selected(old('estado',$plan->estado->value)===$estado)>{{ $estado }}</option>@endforeach</select></label>@endif
<div class="acciones"><a class="boton secundario" href="{{ route('planes.index') }}">Cancelar</a><button>Guardar</button></div></form>
@endsection
