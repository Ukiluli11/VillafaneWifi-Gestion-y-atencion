@extends('layouts.panel')
@section('titulo', 'Iniciar sesión')
@section('contenido')
<section class="tarjeta acceso">
    <div class="acceso-marca">
        <img class="acceso-logo" src="{{ asset('images/logo-villafane-wifi.jpeg') }}" width="54" height="54" alt="">
        <span><strong>Villafañe Wifi</strong><small>Sistema de gestión</small></span>
    </div>
    <p class="sobrelinea">Acceso interno</p>
    <h1>Bienvenido</h1>
    <p>Ingresá tus credenciales para administrar clientes y servicios.</p>
    <form method="POST" action="{{ route('sesion.guardar') }}" class="formulario">
        @csrf
        <label>Usuario<input name="nombre_usuario" value="{{ old('nombre_usuario') }}" autocomplete="username" spellcheck="false" autocapitalize="none" placeholder="Tu usuario" required></label>
        <div>
            <label for="contrasena">Contraseña</label>
            <div class="acceso-contrasena">
                <input id="contrasena" type="password" name="contrasena" autocomplete="current-password" placeholder="Tu contraseña" required>
                <button class="boton secundario" type="button" data-alternar-contrasena aria-controls="contrasena" hidden>Mostrar contraseña</button>
            </div>
        </div>
        <button type="submit">Ingresar al sistema</button>
    </form>
    <a class="acceso-volver" href="{{ route('portada') }}">← Volver a la portada</a>
</section>
@endsection
