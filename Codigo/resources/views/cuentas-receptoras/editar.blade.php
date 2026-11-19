{{-- Muestra el formulario de edición de una cuenta receptora. --}}
@extends('layouts.panel')
@section('titulo', 'Editar cuenta receptora')
@section('contenido')
<div class="encabezado">
    <div><p class="sobrelinea">Cobranza</p><h1>Editar cuenta receptora</h1></div>
</div>

<section class="tarjeta">
    <form class="formulario fila" method="POST" action="{{ route('cuentas-receptoras.update', $cuenta) }}">
        @csrf
        @method('PUT')
        @include('cuentas-receptoras.campos')
        <button type="submit">Guardar cambios</button>
        <a class="boton secundario" href="{{ route('cuentas-receptoras.index') }}">Cancelar</a>
    </form>
</section>
@endsection
