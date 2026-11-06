<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#123f2c">
    <meta name="description" content="Villafañe Wifi: conectamos personas, hogares y oportunidades.">
    <title>Villafañe Wifi · Conectados a lo que importa</title>
    <style>
        :root { font-family: Inter, ui-sans-serif, system-ui, sans-serif; color: #173a2b; background: #f5f8f4; }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { margin: 0; min-height: 100vh; }
        a { color: inherit; }
        a:focus-visible { outline: 3px solid #f5b54d; outline-offset: 4px; }
        .envoltura { width: min(1160px, calc(100% - 48px)); margin: auto; }
        .cabecera { position: relative; z-index: 2; background: #fff; border-bottom: 1px solid #e6eee7; }
        .cabecera .envoltura { min-height: 84px; display: flex; align-items: center; justify-content: space-between; gap: 20px; }
        .marca { display: inline-flex; align-items: center; gap: 12px; text-decoration: none; }
        .marca-icono { width: 56px; height: 56px; display: grid; place-items: center; overflow: hidden; border-radius: 50%; background: white; }
        .marca-icono img { display: block; width: 100%; height: 100%; object-fit: contain; }
        .marca-texto strong, .marca-texto small { display: block; }
        .marca-texto strong { font-size: 1.1rem; letter-spacing: -.04em; }
        .marca-texto small { color: #64776b; font-size: .74rem; }
        .navegacion { display: flex; align-items: center; gap: 28px; font-size: .9rem; font-weight: 600; }
        .navegacion a { text-decoration: none; }
        .navegacion a:hover { color: #176b43; }
        .boton { display: inline-flex; align-items: center; justify-content: center; min-height: 48px; padding: 12px 22px; border-radius: 10px; background: #176b43; color: #fff; text-decoration: none; font-weight: 700; transition: transform .2s, background .2s; }
        .boton:hover { background: #125635; transform: translateY(-2px); }
        .boton-claro { background: white; color: #176b43; }
        .boton-claro:hover { background: #edf6ee; }
        .hero { position: relative; overflow: hidden; background: linear-gradient(125deg, #103c2a 0%, #176b43 64%, #26865b 100%); color: white; }
        .hero::before, .hero::after { content: ''; position: absolute; border: 1px solid rgba(255,255,255,.14); border-radius: 50%; pointer-events: none; }
        .hero::before { width: 760px; height: 760px; right: -280px; top: -330px; }
        .hero::after { width: 510px; height: 510px; right: -155px; top: -200px; }
        .hero .envoltura { position: relative; z-index: 1; min-height: 560px; display: grid; grid-template-columns: 1.1fr .9fr; align-items: center; gap: 60px; padding-top: 70px; padding-bottom: 80px; }
        .etiqueta { display: inline-block; padding: 7px 13px; border: 1px solid rgba(255,255,255,.3); border-radius: 100px; color: #d9f7e5; font-size: .79rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; }
        h1 { max-width: 700px; margin: 24px 0; font-size: clamp(2.7rem, 5.8vw, 5.2rem); line-height: 1.04; letter-spacing: -.065em; }
        .hero p { max-width: 560px; color: #d8f0e0; font-size: clamp(1rem, 1.5vw, 1.2rem); line-height: 1.7; }
        .hero .acciones { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 32px; }
        .enlace-hero { display: inline-flex; align-items: center; padding: 12px 14px; color: white; font-weight: 650; text-decoration: none; }
        .enlace-hero:hover { text-decoration: underline; }
        .ilustracion { position: relative; width: min(100%, 400px); aspect-ratio: 1; justify-self: center; display: grid; place-items: center; border-radius: 50%; background: radial-gradient(circle, rgba(255,255,255,.2) 0 29%, rgba(255,255,255,.08) 30% 48%, rgba(255,255,255,.05) 49% 67%, transparent 68%); }
        .ilustracion img { display: block; width: 74%; aspect-ratio: 1; object-fit: cover; border-radius: 50%; box-shadow: 0 25px 65px rgba(4,35,18,.22); }
        .seccion { padding: 85px 0; }
        .seccion .intro { max-width: 650px; margin-bottom: 32px; }
        .sobrelinea { margin: 0 0 10px; color: #176b43; font-size: .78rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
        h2 { margin: 0 0 15px; font-size: clamp(1.8rem, 3vw, 2.5rem); line-height: 1.15; letter-spacing: -.045em; }
        .intro p:last-child { color: #617266; line-height: 1.7; }
        .tarjetas { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
        .tarjeta { padding: 26px; border: 1px solid #e2ebe3; border-radius: 18px; background: white; box-shadow: 0 12px 35px rgba(21,66,41,.04); }
        .tarjeta span { display: grid; width: 44px; height: 44px; place-items: center; border-radius: 12px; background: #e6f3e9; color: #176b43; font-size: 1.3rem; }
        .tarjeta h3 { margin: 22px 0 8px; font-size: 1.1rem; }
        .tarjeta p { margin: 0; color: #617266; line-height: 1.65; font-size: .92rem; }
        .acceso { padding: 58px 0 80px; }
        .acceso-caja { display: flex; align-items: center; justify-content: space-between; gap: 28px; padding: 44px; border-radius: 22px; background: #e4f2e7; }
        .acceso-caja p { max-width: 580px; margin: 0; color: #526c5a; line-height: 1.6; }
        .pie { padding: 28px 0; border-top: 1px solid #e2ebe3; color: #64776b; font-size: .84rem; }
        .pie .envoltura { display: flex; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
        @media (max-width: 800px) { .hero .envoltura { grid-template-columns: 1fr; gap: 0; min-height: 0; } .ilustracion { width: 230px; margin-top: 30px; } .tarjetas { grid-template-columns: 1fr; } }
        @media (max-width: 600px) { .envoltura { width: min(100% - 32px, 1160px); } .cabecera .envoltura { min-height: 72px; } .navegacion { gap: 12px; } .navegacion > a:first-child { display: none; } .navegacion .boton { min-height: 40px; padding: 9px 12px; font-size: .79rem; } .hero .envoltura { padding-top: 65px; padding-bottom: 55px; } .seccion { padding: 60px 0; } .acceso-caja { padding: 30px; flex-direction: column; align-items: flex-start; } }
    </style>
</head>
<body>
    <header class="cabecera">
        <div class="envoltura">
            <a class="marca" href="{{ route('portada') }}" aria-label="Villafañe Wifi, ir al inicio">
                <span class="marca-icono"><img src="{{ asset('images/logo-villafane-wifi.jpeg') }}" alt="Logo de Villafañe Wifi"></span>
                <span class="marca-texto"><strong>Villafañe Wifi</strong><small>Siempre conectados</small></span>
            </a>
            <nav class="navegacion" aria-label="Navegación principal">
                <a href="#nosotros">Nosotros</a>
                <a class="boton" href="{{ auth()->check() ? route('inicio') : route('sesion.crear') }}">{{ auth()->check() ? 'Ir al panel' : 'Iniciar sesión' }}</a>
            </nav>
        </div>
    </header>
    <main>
        <section class="hero">
            <div class="envoltura">
                <div>
                    <span class="etiqueta">Bienvenidos a Villafañe Wifi</span>
                    <h1>Conectados a lo que importa.</h1>
                    <p>Internet para estar cerca, trabajar, aprender y disfrutar. En Villafañe Wifi creemos que una buena conexión abre nuevas posibilidades cada día.</p>
                    <div class="acciones">
                        <a class="boton boton-claro" href="{{ auth()->check() ? route('inicio') : route('sesion.crear') }}">{{ auth()->check() ? 'Entrar al panel' : 'Ingresar al sistema' }} <span aria-hidden="true">→</span></a>
                        <a class="enlace-hero" href="#nosotros">Conocé más <span aria-hidden="true">→</span></a>
                    </div>
                </div>
                <div class="ilustracion"><img src="{{ asset('images/logo-villafane-wifi.jpeg') }}" alt="Villafañe Wifi, servicio de internet"></div>
            </div>
        </section>
        <section class="seccion" id="nosotros">
            <div class="envoltura">
                <div class="intro"><p class="sobrelinea">Nuestra conexión</p><h2>Más cerca de cada momento</h2><p>La conectividad es parte de la vida cotidiana. Queremos acompañarte con una experiencia simple, cercana y confiable.</p></div>
                <div class="tarjetas">
                    <article class="tarjeta"><span aria-hidden="true">⌂</span><h3>Para tu hogar</h3><p>Compartí, estudiá y disfrutá de tus momentos favoritos con quienes más querés.</p></article>
                    <article class="tarjeta"><span aria-hidden="true">↗</span><h3>Para tus proyectos</h3><p>Seguí creando oportunidades y avanzando en lo que te apasiona.</p></article>
                    <article class="tarjeta"><span aria-hidden="true">♡</span><h3>Siempre cerca</h3><p>Una atención humana para acompañarte cuando necesitás ayuda.</p></article>
                </div>
            </div>
        </section>
        <section class="acceso" id="acceso"><div class="envoltura"><div class="acceso-caja"><div><p class="sobrelinea">Acceso al sistema</p><h2>Ingresá a tu espacio de gestión</h2><p>Si formás parte del equipo de Villafañe Wifi, accedé con tus credenciales.</p></div><a class="boton" href="{{ auth()->check() ? route('inicio') : route('sesion.crear') }}">{{ auth()->check() ? 'Ir al panel' : 'Iniciar sesión' }} <span aria-hidden="true">→</span></a></div></div></section>
    </main>
    <footer class="pie"><div class="envoltura"><span>© {{ date('Y') }} Villafañe Wifi</span><span>Conectados a lo que importa.</span></div></footer>
</body>
</html>
