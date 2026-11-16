@extends('layouts.panel')
@section('titulo', 'Cambiar contraseña')
@section('contenido')
<div class="encabezado"><div><h1>Cambiar contraseña</h1></div></div>
<section class="tarjeta">
    <form method="POST" class="formulario" action="{{ route('cuenta.contrasena.actualizar') }}">
        @csrf
        @method('PUT')
        <label>Contraseña actual<input type="password" name="contrasena_actual" autocomplete="current-password" required></label>
        <label>Nueva contraseña<input type="password" name="contrasena_nueva" autocomplete="new-password" minlength="8" required></label>
        <label>Repetir nueva contraseña<input type="password" name="contrasena_nueva_confirmation" autocomplete="new-password" required></label>
        <button type="submit">Actualizar contraseña</button>
    </form>
</section>
@endsection
