<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#123f2c">
    <meta name="description" content="Villafañe Wifi: conectamos personas, hogares y oportunidades.">
    <title>Villafañe Wifi · Conectados a lo que importa</title>
    <style>
        :root { --dorado: #b58b52; font-family: Inter, ui-sans-serif, system-ui, sans-serif; color: #173a2b; background: #f3e9d7; }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { margin: 0; min-height: 100vh; }
        a { color: inherit; }
        a:focus-visible { outline: 3px solid #a66a37; outline-offset: 4px; }
        .envoltura { width: min(1160px, calc(100% - 48px)); margin: auto; }
        .cabecera { position: relative; z-index: 2; background: #faf6ed; border-bottom: 1px solid #dccdb4; }
        .cabecera .envoltura { min-height: 84px; display: flex; align-items: center; justify-content: space-between; gap: 20px; }
        .marca { display: inline-flex; align-items: center; gap: 12px; text-decoration: none; }
        .marca-icono { width: 56px; height: 56px; display: grid; place-items: center; overflow: hidden; border-radius: 50%; background: white; }
        .marca-icono img { display: block; width: 100%; height: 100%; object-fit: contain; }
        .marca-texto strong, .marca-texto small { display: block; }
        .marca-texto strong { font-size: 1.1rem; letter-spacing: -.04em; }
        .marca-texto small { color: #536052; font-size: .74rem; }
        .navegacion { display: flex; align-items: center; gap: 28px; font-size: .9rem; font-weight: 600; }
        .navegacion a { text-decoration: none; }
        .navegacion a:not(.boton):hover { color: #176b43; }
        .boton { display: inline-flex; align-items: center; justify-content: center; min-height: 48px; padding: 12px 22px; border-radius: 10px; background: #176b43; color: #faf2e5; text-decoration: none; font-weight: 700; transition: transform .2s, background .2s; }
        .boton:hover { background: #125635; transform: translateY(-2px); }
        .boton-claro { background: #e8d7bd; color: #173a2b; }
        .boton-claro:hover { background: #fff3dc; }
        .hero { position: relative; overflow: hidden; background: linear-gradient(125deg, #103c2a 0%, #176b43 64%, #26865b 100%); color: #faf2e5; }
        .hero::before, .hero::after { content: ''; position: absolute; border: 1px solid color-mix(in srgb, var(--dorado) 36%, transparent); border-radius: 50%; pointer-events: none; }
        .hero::before { width: 760px; height: 760px; right: -280px; top: -330px; }
        .hero::after { width: 510px; height: 510px; right: -155px; top: -200px; }
        .hero .envoltura { position: relative; z-index: 1; min-height: 560px; display: grid; grid-template-columns: 1.1fr .9fr; align-items: center; gap: 60px; padding-top: 70px; padding-bottom: 80px; }
        .etiqueta { display: inline-block; padding: 7px 13px; border: 1px solid rgba(243,226,198,.3); border-radius: 100px; color: #f3e2c6; font-size: .79rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; }
        h1 { max-width: 700px; margin: 24px 0; font-size: clamp(2.7rem, 5.8vw, 5.2rem); line-height: 1.04; letter-spacing: -.065em; }
        .hero p { max-width: 560px; color: #e9ddc6; font-size: clamp(1rem, 1.5vw, 1.2rem); line-height: 1.7; }
        .hero .acciones { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 32px; }
        .enlace-hero { display: inline-flex; align-items: center; padding: 12px 14px; color: #faf2e5; font-weight: 650; text-decoration: none; }
        .enlace-hero:hover { text-decoration: underline; }
        .ilustracion { position: relative; width: min(100%, 400px); aspect-ratio: 1; justify-self: center; display: grid; place-items: center; border-radius: 50%; background: radial-gradient(circle, rgba(243,226,198,.2) 0 29%, rgba(243,226,198,.08) 30% 48%, rgba(243,226,198,.05) 49% 67%, transparent 68%); }
        .ilustracion img { display: block; width: 74%; aspect-ratio: 1; object-fit: cover; border-radius: 50%; box-shadow: 0 25px 65px rgba(4,35,18,.22); }
        .seccion { padding: 85px 0; }
        .seccion .intro { max-width: 650px; margin-bottom: 32px; }
        .sobrelinea { margin: 0 0 10px; color: #176b43; font-size: .78rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
        h2 { margin: 0 0 15px; font-size: clamp(1.8rem, 3vw, 2.5rem); line-height: 1.15; letter-spacing: -.045em; }
        .intro p:last-child { color: #505b4b; line-height: 1.7; }
        .tarjetas { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
        .tarjeta { padding: 26px; border: 1px solid #dfd0b7; border-radius: 18px; background: #faf6ed; box-shadow: 0 12px 35px rgba(21,66,41,.04); }
        .tarjeta span { display: grid; width: 44px; height: 44px; place-items: center; border-radius: 12px; background: #123f2c; color: var(--dorado); font-size: 1.3rem; }
        .tarjeta h3 { margin: 22px 0 8px; font-size: 1.1rem; }
        .tarjeta p { margin: 0; color: #505b4b; line-height: 1.65; font-size: .92rem; }
        .acceso { padding: 58px 0 80px; }
        .acceso-caja { display: flex; align-items: center; justify-content: space-between; gap: 28px; padding: 44px; border-radius: 22px; background: #e8d7bd; }
        .acceso-caja p { max-width: 580px; margin: 0; color: #465445; line-height: 1.6; }
        .pie { padding: 28px 0; background: #123f2c; color: #e9ddc6; font-size: .84rem; }
        .pie .envoltura { display: flex; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
        @media (max-width: 800px) { .hero .envoltura { grid-template-columns: 1fr; gap: 0; min-height: 0; } .ilustracion { width: 230px; margin-top: 30px; } .tarjetas { grid-template-columns: 1fr; } }
        @media (max-width: 600px) { .envoltura { width: min(100% - 32px, 1160px); } .cabecera .envoltura { min-height: 72px; } .navegacion { gap: 12px; } .navegacion > a:first-child { display: none; } .navegacion .boton { min-height: 40px; padding: 9px 12px; font-size: .79rem; } .hero .envoltura { padding-top: 65px; padding-bottom: 55px; } .seccion { padding: 60px 0; } .acceso-caja { padding: 30px; flex-direction: column; align-items: flex-start; } }
        .historia { padding: clamp(70px, 10vw, 140px) 0; }
        .historia .envoltura { max-width: 900px; }
        .historia-con-imagen .envoltura { max-width: 1160px; display: grid; grid-template-columns: 1fr 1fr; align-items: center; gap: clamp(30px, 5vw, 80px); }
        .historia-imagen { display: block; width: 100%; height: auto; border-radius: 20px; box-shadow: 0 18px 45px rgba(21, 66, 41, .12); }
        .historia h2 { font-size: clamp(2rem, 4vw, 3.5rem); max-width: 700px; }
        .historia p { max-width: 650px; line-height: 1.8; font-size: 1.1rem; }
        .historia-clara { background: #faf6ed; }
        .historia-verde { background: #123f2c; color: #faf6ed; }
        .historia-arena { background: #e8d7bd; }
        @media (max-width: 800px) { .historia-con-imagen .envoltura { grid-template-columns: 1fr; } }
        @media (prefers-reduced-motion: no-preference) {
            .js-movimiento .revelar { opacity: 0; transform: translateY(24px); transition: opacity .65s ease, transform .65s ease; }
            .js-movimiento .revelar.visible { opacity: 1; transform: none; }
        }
        @media (prefers-reduced-motion: reduce) { html { scroll-behavior: auto; } .boton { transition: none; } }
        .pasos { padding-left: 2.5rem; }
        .pasos li::marker { color: #176b43; font-weight: 700; }
        .contacto { margin: 0; }
        .contacto dt { font-weight: 700; }
        .contacto dd { margin: 0 0 .75rem; }
    </style>
</head>
<body>
    @php
        $telefonoPublico = preg_replace('/\D/', '', (string) config('portada.whatsapp'));
        $whatsapp = 'https://wa.me/'.$telefonoPublico;
    @endphp
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
                        <a class="enlace-hero" href="#nosotros">Descubrí más <span aria-hidden="true">↓</span></a>
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
        <section class="historia historia-clara historia-con-imagen" aria-labelledby="hogar-titulo"><div class="envoltura"><div><h2 id="hogar-titulo">Una conexión que forma parte de tu día</h2><p>Detrás de cada pantalla hay algo que importa. Una clase, una videollamada con la familia, una jornada de trabajo o un momento para disfrutar.</p><p>En Villafañe Wifi queremos acompañar esos momentos, acercando internet a la vida cotidiana de los hogares.</p></div><img class="historia-imagen" src="{{ asset('images/conexion-en-casa.png') }}" alt="Persona que conversa por videollamada desde su hogar" loading="lazy" width="1672" height="941"></div></section>
        <section class="historia historia-verde historia-con-imagen" aria-labelledby="cerca-titulo">
            <div class="envoltura">
                <div>
                    <h2 id="cerca-titulo">Tecnología con atención cercana</h2>
                    <p>Creemos en una relación simple y directa. Escuchar una consulta, entender lo que necesitás y acompañarte es parte de nuestra forma de trabajar.</p>
                    <p>La conexión también se construye con las personas que están del otro lado.</p>
                </div>
                <img class="historia-imagen" src="{{ asset('images/atencion-cercana.png') }}" alt="Un hombre atiende una consulta con auriculares desde su oficina" loading="lazy" width="1672" height="941">
            </div>
        </section>
        <section class="historia historia-arena historia-con-imagen" aria-labelledby="proyectos-titulo">
            <div class="envoltura">
                <div>
                    <h2 id="proyectos-titulo">Más posibilidades para tus proyectos</h2>
                    <p>Aprender algo nuevo, compartir tu trabajo o darle forma a una idea. Internet abre un espacio para seguir creciendo desde donde estás.</p>
                    <p>Ese es el sentido de nuestro lema: conectados a lo que importa.</p>
                </div>
                <img class="historia-imagen" src="{{ asset('images/proyectos-conectados.png') }}" alt="Una mujer mayor saluda a su familia por videollamada desde su casa" loading="lazy" width="1672" height="941">
            </div>
        </section>
        <section class="seccion" aria-labelledby="contratar-titulo">
            <div class="envoltura">
                <h2 id="contratar-titulo">Cómo contratar</h2>
                <ol class="tarjetas pasos">
                    <li class="tarjeta">
                        <h3>Contactanos por WhatsApp</h3>
                        <p>Escribinos al @if ($telefonoPublico)<a href="{{ $whatsapp }}">+{{ $telefonoPublico }}</a>@else WhatsApp del equipo @endif. Te atendemos y te orientamos para contratar.</p>
                    </li>
                    <li class="tarjeta"><h3>Elegí un plan</h3><p>Consultá los planes disponibles para tu zona y elegí según cómo usás internet.</p></li>
                    <li class="tarjeta"><h3>Coordiná la instalación</h3><p>Una vez confirmado el servicio, acordá una fecha con el equipo.</p></li>
                </ol>
            </div>
        </section>
        <section class="acceso" id="acceso"><div class="envoltura"><div class="acceso-caja"><div><p class="sobrelinea">Acceso al sistema</p><h2>Ingresá a tu espacio de gestión</h2><p>Si formás parte del equipo de Villafañe Wifi, accedé con tus credenciales.</p></div><a class="boton" href="{{ auth()->check() ? route('inicio') : route('sesion.crear') }}">{{ auth()->check() ? 'Ir al panel' : 'Iniciar sesión' }} <span aria-hidden="true">→</span></a></div></div></section>
    </main>
    <footer class="pie" id="contacto">
        <div class="envoltura">
            <div>
                <h2>Contacto</h2>
                <dl class="contacto">
                    <dt>WhatsApp</dt>
                    <dd>@if ($telefonoPublico)<a href="{{ $whatsapp }}">+{{ $telefonoPublico }}</a>@else Por confirmar @endif</dd>
                    <dt>Horarios de atención</dt>
                    <dd>{{ config('portada.horarios') ?: 'Por confirmar' }}</dd>
                    <dt>Zonas de servicio</dt>
                    <dd>{{ config('portada.zonas') ?: 'La disponibilidad se confirma por domicilio.' }}</dd>
                </dl>
            </div>
            <span>© {{ date('Y') }} Villafañe Wifi · Conectados a lo que importa.</span>
        </div>
    </footer>
    <script>
        if ('IntersectionObserver' in window && window.matchMedia('(prefers-reduced-motion: no-preference)').matches) {
            const bloques = document.querySelectorAll('.historia .envoltura');
            const observador = new IntersectionObserver((entradas) => {
                entradas.forEach((entrada) => {
                    if (entrada.isIntersecting) {
                        entrada.target.classList.add('visible');
                        observador.unobserve(entrada.target);
                    }
                });
            }, { threshold: 0.12 });

            bloques.forEach((bloque) => {
                bloque.classList.add('revelar');
                observador.observe(bloque);
            });
            document.documentElement.classList.add('js-movimiento');
        }
    </script>
</body>
</html>
