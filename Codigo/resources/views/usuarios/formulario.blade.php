@extends('layouts.panel')
@section('titulo', 'Editar usuario')
@section('contenido')
<div class="encabezado"><div><h1>Editar usuario</h1></div></div>
<section class="tarjeta">
    @include('usuarios.campos')
</section>
@unless ($usuario->is(auth()->user()))
    <section class="tarjeta">
        <h2>Restablecer contraseña</h2>
        <p>La nueva contraseña debe tener al menos 8 caracteres. Al guardarla, este usuario deberá volver a iniciar sesión.</p>
        <form method="POST" class="formulario" action="{{ route('usuarios.contrasena.restablecer', $usuario) }}">
            @csrf
            @method('PUT')
            <label>Nueva contraseña<input type="password" name="contrasena_nueva" autocomplete="new-password" minlength="8" required></label>
            <label>Repetir nueva contraseña<input type="password" name="contrasena_nueva_confirmation" autocomplete="new-password" minlength="8" required></label>
            <div class="acciones"><button type="submit">Restablecer contraseña</button></div>
        </form>
    </section>
@endunless
@endsection
