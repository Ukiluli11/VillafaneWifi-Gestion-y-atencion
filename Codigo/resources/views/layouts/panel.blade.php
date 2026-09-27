<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#123f36">
    <title>@yield('titulo', 'Panel') · Villafañe Wifi</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="@auth con-sesion @else sin-sesion @endauth">
    @auth
        <div class="capa-lateral" data-cerrar-menu></div>
        <aside class="barra-lateral" id="barra-lateral" aria-label="Navegación principal">
            <div class="identidad">
                <a class="marca" href="{{ route('inicio') }}" aria-label="Ir al resumen">
                    <span class="marca-simbolo">VW</span>
                    <span><strong>Villafañe</strong><small>Wifi · Gestión</small></span>
                </a>
                <button class="cerrar-menu" type="button" data-cerrar-menu aria-label="Cerrar menú">&times;</button>
            </div>

            <nav class="navegacion">
                <span class="grupo-navegacion">General</span>
                <a class="enlace-navegacion {{ request()->routeIs('inicio') ? 'activo' : '' }}" href="{{ route('inicio') }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 11.5 12 4l9 7.5v8a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/></svg>
                    <span>Resumen</span>
                </a>

                @if ($navegacionPermitida['clientes'] || $navegacionPermitida['servicios'])
                    <span class="grupo-navegacion">Operaciones</span>
                @endif
                @if ($navegacionPermitida['clientes'])
                    <a class="enlace-navegacion {{ request()->routeIs('clientes.*', 'cuentas.show') ? 'activo' : '' }}" href="{{ route('clientes.index') }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 20v-1.5a4.5 4.5 0 0 0-4.5-4.5h-4A4.5 4.5 0 0 0 3 18.5V20M9.5 10a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm8-1a3 3 0 0 1 0 6m3.5 5v-1a4 4 0 0 0-3-3.87"/></svg>
                        <span>Clientes</span>
                    </a>
                @endif
                @if ($navegacionPermitida['servicios'])
                    <a class="enlace-navegacion {{ request()->routeIs('servicios.*') ? 'activo' : '' }}" href="{{ route('servicios.index') }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12.55a11 11 0 0 1 14 0M8.5 16a6 6 0 0 1 7 0M12 20h.01M2 9a16 16 0 0 1 20 0"/></svg>
                        <span>Servicios</span>
                    </a>
                @endif

                @if ($navegacionPermitida['planes'] || $navegacionPermitida['cobranza'])
                    <span class="grupo-navegacion">Administración</span>
                @endif
                @if ($navegacionPermitida['planes'])
                    <a class="enlace-navegacion {{ request()->routeIs('planes.*') ? 'activo' : '' }}" href="{{ route('planes.index') }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5.5A1.5 1.5 0 0 1 5.5 4h13A1.5 1.5 0 0 1 20 5.5v13a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 18.5zM8 9h8M8 13h8M8 17h4"/></svg>
                        <span>Planes</span>
                    </a>
                @endif
                @if ($navegacionPermitida['cobranza'])
                    <a class="enlace-navegacion {{ request()->routeIs('cuentas-receptoras.*') ? 'activo' : '' }}" href="{{ route('cuentas-receptoras.index') }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7h18v12H3zM3 10h18M7 16h4M17 4l-5-2-5 2"/></svg>
                        <span>Cobranza</span>
                    </a>
                @endif
                @if ($navegacionPermitida['usuarios'])
                    <a class="enlace-navegacion {{ request()->routeIs('usuarios.*') ? 'activo' : '' }}" href="{{ route('usuarios.index') }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm7 8a7 7 0 0 0-14 0M19 8v6M22 11h-6"/></svg>
                        <span>Usuarios</span>
                    </a>
                @endif
            </nav>

            <div class="usuario-lateral">
                <span class="avatar">{{ mb_strtoupper(mb_substr(auth()->user()->nombre_usuario, 0, 1)) }}</span>
                <span class="datos-usuario"><strong>{{ auth()->user()->nombre_usuario }}</strong><small>Sesión activa</small></span>
                <form method="POST" action="{{ route('sesion.destruir') }}">
                    @csrf
                    <button class="boton-salir" type="submit" title="Cerrar sesión" aria-label="Cerrar sesión">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17l5-5-5-5M15 12H3M14 4h5a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-5"/></svg>
                    </button>
                </form>
            </div>
        </aside>

        <div class="area-principal">
            <header class="barra-superior">
                <button class="abrir-menu" type="button" data-abrir-menu aria-controls="barra-lateral" aria-expanded="false" aria-label="Abrir menú">
                    <span></span><span></span><span></span>
                </button>
                <div class="titulo-superior">
                    <small>Panel administrativo</small>
                    <strong>@yield('titulo', 'Resumen')</strong>
                </div>
                <span class="estado-sistema"><i></i>Sistema operativo</span>
            </header>
            <main class="contenedor">
                @if (session('exito'))
                    <div class="alerta exito" role="status"><strong>Listo.</strong> {{ session('exito') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alerta error" role="alert">
                        <strong>Revisá los datos ingresados:</strong>
                        <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif
                @yield('contenido')
            </main>
        </div>
    @else
        <main class="pagina-acceso">
            @if ($errors->any())
                <div class="alerta error acceso-alerta" role="alert">{{ $errors->first() }}</div>
            @endif
            @yield('contenido')
        </main>
    @endauth
</body>
</html>
