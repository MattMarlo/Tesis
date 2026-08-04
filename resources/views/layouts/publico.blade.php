<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="description"
          content="@yield('descripcion', 'Encuentra paquetes turísticos, destinos y experiencias con Passion Travel.')">

    <title>@yield('title', 'Passion Travel')</title>

    <link rel="shortcut icon"
          type="image/png"
          href="{{ asset('assets/logo/logo_passion-removebg-preview.png') }}">

    <link rel="stylesheet"
          href="{{ asset('assets/css/font-awesome.min.css') }}">

    <link rel="stylesheet"
          href="{{ asset('css/publico.css') }}">

    @yield('styles')
</head>

<body>

@php
    /*
    |--------------------------------------------------------------
    | Enlace general de Telegram
    |--------------------------------------------------------------
    | Si el usuario de tu bot es diferente, solamente debes
    | modificar esta dirección.
    */
    $telegramBot = ltrim(
        (string) config('services.telegram.bot_username'),
        '@'
    );

    $telegramGeneral = 'https://t.me/' . $telegramBot;
    $telegramAyuda = $telegramGeneral . '?start=ayuda';
    $telegramAsesor = $telegramGeneral . '?start=asesor';
    @endphp

<header class="encabezado-principal" id="encabezadoPrincipal">
    <div class="contenedor navegacion">

        <a href="{{ url('/') }}" class="logo-publico" aria-label="Passion Travel">
            <span class="logo-icono">
                <img
                    src="{{ asset('assets/logo/logo_passion-removebg-preview.png') }}"
                    alt="Logo de Passion Travel"
                >
            </span>

            <span class="logo-texto">
                Passion<strong>Travel</strong>
            </span>
        </a>

        <button
            type="button"
            class="boton-menu-movil"
            id="botonMenuMovil"
            aria-label="Abrir menú"
            aria-expanded="false"
        >
            <span></span>
            <span></span>
            <span></span>
        </button>

        <nav class="menu-publico" id="menuPublico">
            <a href="{{ url('/') }}" class="enlace-menu">
                Inicio
            </a>

            <a href="{{ url('/#nosotros') }}" class="enlace-menu">
                Quiénes somos
            </a>

            <a href="{{ url('/#paquetes') }}" class="enlace-menu">
                Paquetes
            </a>

            <a href="{{ url('/#destinos') }}" class="enlace-menu">
                Galería
            </a>

            <a href="{{ url('/#testimonios') }}" class="enlace-menu">
                Testimonios
            </a>

            <a href="{{ url('/#ubicacion') }}" class="enlace-menu">
                Ubicación
            </a>

            <a href="{{ route('login') }}" class="boton-iniciar-sesion">
                <i class="fa fa-user"></i>
                Iniciar sesión
            </a>

            <a
                href="{{ $telegramAsesor }}"
                target="_blank"
                rel="noopener noreferrer"
                class="boton-telegram-menu"
            >
                <i class="fa fa-telegram"></i>
                Hablar con un asesor
            </a>
        </nav>

    </div>
</header>

<main class="contenido-publico">
    @yield('content')
</main>

<section class="franja-contacto" id="contacto">
    <div class="contenedor franja-contacto-contenido">

        <div class="franja-contacto-texto">
            <span class="subtitulo-seccion subtitulo-claro">
                ¿Estás listo para viajar?
            </span>

            <h2>Comienza a planificar tu próxima experiencia</h2>

            <p>
                Nuestro asistente virtual te ayudará a consultar paquetes,
                disponibilidad y opciones para tu viaje.
            </p>
        </div>

        <a
            href="{{ $telegramAyuda }}"
            target="_blank"
            rel="noopener noreferrer"
            class="boton-contacto-telegram"
        >
            <i class="fa fa-telegram"></i>

            <span>
                <small>Atención por Telegram</small>
                Consultar ahora
            </span>
        </a>

    </div>
</section>

<footer class="pie-principal">
    <div class="contenedor">

        <div class="pie-columnas">

            <div class="pie-marca">
                <a href="{{ url('/') }}" class="logo-publico logo-pie">
                    <span class="logo-icono">
                        <img
                            src="{{ asset('assets/logo/logo_passion.png') }}"
                            alt="Logo de Passion Travel"
                        >
                    </span>

                    <span class="logo-texto">
                        Passion<strong>Travel</strong>
                    </span>
                </a>

                <p>
                    Creamos experiencias de viaje para que descubras nuevos
                    destinos de una manera segura, cómoda y memorable.
                </p>

                <div class="redes-sociales">
                    <a href="https://www.facebook.com/passiontravelS.A?locale=es_LA"
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label="Facebook de Passion Travel">
                            <i class="fa fa-facebook"></i>
                    </a>

                    <a href="https://www.instagram.com/passiontravel.ec/" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
                        <i class="fa fa-instagram"></i>
                    </a>

                    <a
                        href="{{ $telegramGeneral }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label="Telegram"
                    >
                        <i class="fa fa-telegram"></i>
                    </a>
                </div>
            </div>

            <div class="pie-columna">
                <h3>Explora</h3>

                <a href="{{ url('/') }}">Inicio</a>
                <a href="{{ url('/#paquetes') }}">Paquetes turísticos</a>
                <a href="{{ url('/#destinos') }}">Destinos</a>
                <a href="{{ url('/#beneficios') }}">Nuestros servicios</a>
            </div>

            <div class="pie-columna">
                <h3>Información</h3>

                <a href="{{ url('/#contacto') }}">Contacto</a>
                <a href="{{ $telegramGeneral }}" target="_blank">
                    Atención en Telegram
                </a>
                <a href="{{ route('login') }}">Acceso administrativo</a>
            </div>

            <div class="pie-columna pie-contacto">
                <h3>Contáctanos</h3>

                <p>
                    <i class="fa fa-map-marker"></i>
                    Ecuador
                </p>

                <p>
                    <i class="fa fa-clock-o"></i>
                    Atención personalizada
                </p>

                <p>
                    <i class="fa fa-paper-plane"></i>
                    Passion Travel
                </p>
            </div>

        </div>

        <div class="pie-inferior">
            <p>
                © {{ date('Y') }} Passion Travel. Todos los derechos reservados.
            </p>

            <p>
                Viaja, descubre y crea nuevos recuerdos.
            </p>
        </div>

    </div>
</footer>

<a
    href="{{ $telegramAyuda }}"
    target="_blank"
    rel="noopener noreferrer"
    class="telegram-flotante"
    aria-label="Consultar mediante Telegram"
>
    <i class="fa fa-telegram"></i>
    <span>¿Necesitas ayuda?</span>
</a>

<script src="{{ asset('assets/js/jquery.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function () {
        const $encabezado = $('#encabezadoPrincipal');
        const $menu = $('#menuPublico');
        const $botonMenu = $('#botonMenuMovil');

        function actualizarEncabezado() {
            if ($(window).scrollTop() > 30) {
                $encabezado.addClass('encabezado-con-sombra');
            } else {
                $encabezado.removeClass('encabezado-con-sombra');
            }
        }

        actualizarEncabezado();

        $(window).on('scroll', actualizarEncabezado);

        $botonMenu.on('click', function () {
            const menuAbierto = $menu.hasClass('menu-visible');

            $menu.toggleClass('menu-visible');
            $botonMenu.toggleClass('menu-activo');
            $botonMenu.attr('aria-expanded', !menuAbierto);
        });

        $('.enlace-menu').on('click', function () {
            $menu.removeClass('menu-visible');
            $botonMenu.removeClass('menu-activo');
            $botonMenu.attr('aria-expanded', 'false');
        });

        $(document).on('click', function (evento) {
            if (
                $(window).width() <= 900 &&
                !$(evento.target).closest('#menuPublico, #botonMenuMovil').length
            ) {
                $menu.removeClass('menu-visible');
                $botonMenu.removeClass('menu-activo');
                $botonMenu.attr('aria-expanded', 'false');
            }
        });
    });
</script>

@yield('scripts')

</body>
</html>