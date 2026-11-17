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
                <button class="boton secundario acceso-contrasena-boton" type="button" data-alternar-contrasena
                    aria-controls="contrasena" aria-label="Mostrar contraseña" aria-pressed="false" title="Mostrar contraseña" hidden>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/>
                        <circle cx="12" cy="12" r="3"/>
                        <path class="acceso-contrasena-tachado" d="m3 3 18 18"/>
                    </svg>
                </button>
            </div>
        </div>
        <button type="submit">Ingresar al sistema</button>
    </form>
    <a class="acceso-volver" href="{{ route('portada') }}">← Volver a la portada</a>
</section>
@endsection
