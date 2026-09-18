<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#123f36">
    <title>@yield('titulo', 'Panel') · Villafañe Wifi</title>
    <script>
        (() => {
            const temaGuardado = localStorage.getItem('tema-panel');
            const prefiereOscuro = window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.dataset.tema = temaGuardado ?? (prefiereOscuro ? 'oscuro' : 'claro');

            if (localStorage.getItem('barra-lateral-colapsada') === 'true') {
                document.documentElement.classList.add('lateral-colapsada-inicial');
            }
        })();
    </script>
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

                @if ($navegacionPermitida['clientes'] || $navegacionPermitida['servicios'] || $navegacionPermitida['conversaciones'])
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
                @if ($navegacionPermitida['conversaciones'])
                    <a class="enlace-navegacion {{ request()->routeIs('conversaciones.*') ? 'activo' : '' }}" href="{{ route('conversaciones.index') }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v11H8l-4 4zM8 9h8M8 12h5"/></svg>
                        <span>Conversaciones</span>
                    </a>
                    @if (config('services.whatsapp.modo_simulacion') && $navegacionPermitida['gestionar_conversaciones'])
                        <a class="enlace-navegacion {{ request()->routeIs('simulador-whatsapp.*') ? 'activo' : '' }}" href="{{ route('simulador-whatsapp.create') }}">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h14v16H5zM8 8h8M8 12h5M8 16h3"/></svg>
                            <span>Simulador WhatsApp</span>
                        </a>
                    @endif
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
                @if ($navegacionPermitida['comprobantes'])
                    <a class="enlace-navegacion {{ request()->routeIs('comprobantes.*') ? 'activo' : '' }}" href="{{ route('comprobantes.index') }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h12v18H6zM9 8h6M9 12h6M9 16h4M4 6h2M18 6h2"/></svg>
                        <span>Comprobantes</span>
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
                <button class="alternar-lateral" type="button" data-alternar-lateral aria-controls="barra-lateral" aria-pressed="false" title="Achicar barra lateral" aria-label="Achicar barra lateral">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v14H4zM9 5v14M15 9l-3 3 3 3"/></svg>
                </button>
                <div class="titulo-superior">
                    <small>Panel administrativo</small>
                    <strong>@yield('titulo', 'Resumen')</strong>
                </div>
                <span class="estado-sistema"><i></i>Sistema operativo</span>
                <button class="alternar-tema" type="button" data-alternar-tema aria-pressed="false" title="Activar modo oscuro" aria-label="Activar modo oscuro">
                    <svg class="icono-luna" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.2 15.3A8.5 8.5 0 0 1 8.7 3.8 8.5 8.5 0 1 0 20.2 15.3z"/></svg>
                    <svg class="icono-sol" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
                </button>
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
