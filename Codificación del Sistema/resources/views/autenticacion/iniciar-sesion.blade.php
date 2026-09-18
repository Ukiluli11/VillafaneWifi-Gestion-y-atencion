@extends('layouts.panel')
@section('titulo', 'Iniciar sesión')
@section('contenido')
<section class="tarjeta acceso">
    <div class="acceso-marca">
        <span class="marca-simbolo">VW</span>
        <span><strong>Villafañe Wifi</strong><small>Sistema de gestión</small></span>
    </div>
    <p class="sobrelinea">Acceso interno</p>
    <h1>Bienvenido</h1>
    <p>Ingresá tus credenciales para administrar clientes y servicios.</p>
    <form method="POST" action="{{ route('sesion.guardar') }}" class="formulario">
        @csrf
        <label>Usuario<input name="nombre_usuario" value="{{ old('nombre_usuario') }}" autocomplete="username" placeholder="Tu usuario" required autofocus></label>
        <label>Contraseña<input type="password" name="contrasena" autocomplete="current-password" placeholder="Tu contraseña" required></label>
        <button type="submit">Ingresar al sistema</button>
    </form>
</section>
@endsection
