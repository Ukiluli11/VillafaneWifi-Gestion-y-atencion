@extends('layouts.panel')
@section('titulo', 'Editar usuario')
@section('contenido')
<div class="encabezado"><div><h1>Editar usuario</h1></div></div>
<section class="tarjeta">
    @include('usuarios.campos')
</section>
@endsection
