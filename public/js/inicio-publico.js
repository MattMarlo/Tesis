$(document).ready(function () {
    'use strict';

    function normalizarTexto(texto) {
        return String(texto || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .trim();
    }

    /* =====================================================
       CARRUSEL PRINCIPAL
    ===================================================== */

    const $diapositivasHero = $('.hero-diapositiva');
    const $indicadoresHero = $('.indicador-hero');
    const $carruselHero = $('#heroCarrusel');

    let indiceHero = 0;
    let intervaloHero = null;

    function mostrarDiapositiva(indice) {
        if (!$diapositivasHero.length) {
            return;
        }

        if (indice >= $diapositivasHero.length) {
            indice = 0;
        }

        if (indice < 0) {
            indice = $diapositivasHero.length - 1;
        }

        indiceHero = indice;

        $diapositivasHero.removeClass('hero-activa');
        $diapositivasHero.eq(indiceHero).addClass('hero-activa');

        $indicadoresHero.removeClass('indicador-activo');
        $indicadoresHero.eq(indiceHero).addClass('indicador-activo');
    }

    function iniciarCarruselHero() {
        detenerCarruselHero();

        if ($diapositivasHero.length > 1) {
            intervaloHero = setInterval(function () {
                mostrarDiapositiva(indiceHero + 1);
            }, 6500);
        }
    }

    function detenerCarruselHero() {
        if (intervaloHero) {
            clearInterval(intervaloHero);
            intervaloHero = null;
        }
    }

    $('#heroSiguiente').on('click', function () {
        mostrarDiapositiva(indiceHero + 1);
        iniciarCarruselHero();
    });

    $('#heroAnterior').on('click', function () {
        mostrarDiapositiva(indiceHero - 1);
        iniciarCarruselHero();
    });

    $indicadoresHero.on('click', function () {
        mostrarDiapositiva(Number($(this).data('diapositiva')));
        iniciarCarruselHero();
    });

    $carruselHero.on('mouseenter', detenerCarruselHero);
    $carruselHero.on('mouseleave', iniciarCarruselHero);

    $(document).on('visibilitychange', function () {
        if (document.hidden) {
            detenerCarruselHero();
        } else {
            iniciarCarruselHero();
        }
    });

    iniciarCarruselHero();

    /* =====================================================
       BÚSQUEDA Y FILTROS DE PAQUETES
    ===================================================== */

    const $tarjetasPaquetes = $('.tarjeta-paquete-publico');
    const $cantidadResultados = $('#cantidadResultados');
    const $sinResultados = $('#sinResultados');

    function filtrarPaquetes() {
        const textoBusqueda = normalizarTexto($('#buscarDestino').val());
        const ciudadSalida = normalizarTexto($('#buscarSalida').val());
        const categoria = normalizarTexto($('#buscarCategoria').val());

        let cantidadVisible = 0;

        $tarjetasPaquetes.each(function () {
            const $tarjeta = $(this);

            const nombre = normalizarTexto($tarjeta.data('nombre'));
            const destino = normalizarTexto($tarjeta.data('destino'));
            const salida = normalizarTexto($tarjeta.data('salida'));
            const categoriaTarjeta = normalizarTexto(
                $tarjeta.data('categoria')
            );

            const coincideTexto =
                !textoBusqueda ||
                nombre.includes(textoBusqueda) ||
                destino.includes(textoBusqueda);

            const coincideSalida =
                !ciudadSalida ||
                salida.includes(ciudadSalida);

            const coincideCategoria =
                !categoria ||
                categoriaTarjeta === categoria;

            const mostrar =
                coincideTexto &&
                coincideSalida &&
                coincideCategoria;

            $tarjeta.toggleClass('paquete-oculto', !mostrar);

            if (mostrar) {
                cantidadVisible++;
            }
        });

        $cantidadResultados.text(cantidadVisible);

        if (cantidadVisible === 0 && $tarjetasPaquetes.length > 0) {
            $sinResultados.addClass('resultado-visible');
        } else {
            $sinResultados.removeClass('resultado-visible');
        }

        return cantidadVisible;
    }

    $('#formularioBusqueda').on('submit', function (evento) {
        evento.preventDefault();

        const cantidad = filtrarPaquetes();

        $('html, body').animate({
            scrollTop: $('#paquetes').offset().top - 90
        }, 500);

        if (cantidad === 0) {
            Swal.fire({
                icon: 'info',
                title: 'No encontramos coincidencias',
                text: 'Prueba con otro destino, categoría o ciudad de salida.',
                confirmButtonText: 'Entendido'
            });
        }
    });

    $('#buscarDestino, #buscarSalida').on('input', function () {
        filtrarPaquetes();
    });

    $('#buscarCategoria').on('change', function () {
        const categoriaSeleccionada = normalizarTexto($(this).val());

        $('.filtro-rapido').removeClass('filtro-activo');

        $('.filtro-rapido').each(function () {
            if (
                normalizarTexto($(this).data('categoria')) ===
                categoriaSeleccionada
            ) {
                $(this).addClass('filtro-activo');
            }
        });

        filtrarPaquetes();
    });

    $('.filtro-rapido').on('click', function () {
        const categoria = String($(this).data('categoria') || '');

        $('.filtro-rapido').removeClass('filtro-activo');
        $(this).addClass('filtro-activo');

        $('#buscarCategoria').val(categoria);
        filtrarPaquetes();
    });

    $('#limpiarBusqueda').on('click', function () {
        $('#buscarDestino').val('');
        $('#buscarSalida').val('');
        $('#buscarCategoria').val('');

        $('.filtro-rapido').removeClass('filtro-activo');
        $('.filtro-rapido').first().addClass('filtro-activo');

        filtrarPaquetes();

        $('html, body').animate({
            scrollTop: $('#paquetes').offset().top - 90
        }, 400);
    });

    /* =====================================================
       PAQUETES FAVORITOS
    ===================================================== */

    let favoritos = [];

    try {
        favoritos = JSON.parse(
            localStorage.getItem('paquetesFavoritos') || '[]'
        );

        if (!Array.isArray(favoritos)) {
            favoritos = [];
        }
    } catch (error) {
        favoritos = [];
    }

    $('.boton-favorito').each(function () {
        const $boton = $(this);
        const paquete = String($boton.data('paquete'));

        if (favoritos.includes(paquete)) {
            $boton.addClass('favorito-activo');
            $boton.find('i')
                .removeClass('fa-heart-o')
                .addClass('fa-heart');
        }
    });

    $('.boton-favorito').on('click', function () {
        const $boton = $(this);
        const paquete = String($boton.data('paquete'));
        const indice = favoritos.indexOf(paquete);

        if (indice === -1) {
            favoritos.push(paquete);

            $boton.addClass('favorito-activo');
            $boton.find('i')
                .removeClass('fa-heart-o')
                .addClass('fa-heart');

            Swal.fire({
                icon: 'success',
                title: 'Agregado a favoritos',
                text: paquete + ' se guardó en este dispositivo.',
                confirmButtonText: 'Aceptar',
                timer: 1800,
                timerProgressBar: true
            });
        } else {
            favoritos.splice(indice, 1);

            $boton.removeClass('favorito-activo');
            $boton.find('i')
                .removeClass('fa-heart')
                .addClass('fa-heart-o');

            Swal.fire({
                icon: 'info',
                title: 'Eliminado de favoritos',
                text: paquete + ' ya no está en tus favoritos.',
                confirmButtonText: 'Aceptar',
                timer: 1700,
                timerProgressBar: true
            });
        }

        localStorage.setItem(
            'paquetesFavoritos',
            JSON.stringify(favoritos)
        );
    });

    /* =====================================================
       CARRUSEL DE GALERÍA
    ===================================================== */

    const $carrilGaleria = $('#galeriaCarril');
    const $elementosGaleria = $('.galeria-elemento');

    let indiceGaleria = 0;

    function elementosVisiblesGaleria() {
        const anchoVentana = $(window).width();

        if (anchoVentana <= 650) {
            return 1;
        }

        if (anchoVentana <= 850) {
            return 2;
        }

        return 3;
    }

    function actualizarGaleria() {
        if (!$elementosGaleria.length) {
            return;
        }

        const visibles = elementosVisiblesGaleria();
        const limite = Math.max(
            0,
            $elementosGaleria.length - visibles
        );

        if (indiceGaleria > limite) {
            indiceGaleria = limite;
        }

        if (indiceGaleria < 0) {
            indiceGaleria = 0;
        }

        const anchoElemento =
            $elementosGaleria.first().outerWidth(true);

        $carrilGaleria.css(
            'transform',
            'translateX(-' + (indiceGaleria * anchoElemento) + 'px)'
        );
    }

    $('#galeriaSiguiente').on('click', function () {
        const limite = Math.max(
            0,
            $elementosGaleria.length -
            elementosVisiblesGaleria()
        );

        if (indiceGaleria >= limite) {
            indiceGaleria = 0;
        } else {
            indiceGaleria++;
        }

        actualizarGaleria();
    });

    $('#galeriaAnterior').on('click', function () {
        const limite = Math.max(
            0,
            $elementosGaleria.length -
            elementosVisiblesGaleria()
        );

        if (indiceGaleria <= 0) {
            indiceGaleria = limite;
        } else {
            indiceGaleria--;
        }

        actualizarGaleria();
    });

    let temporizadorRedimensionamiento;

    $(window).on('resize', function () {
        clearTimeout(temporizadorRedimensionamiento);

        temporizadorRedimensionamiento = setTimeout(function () {
            actualizarGaleria();
        }, 180);
    });

    actualizarGaleria();

    /* =====================================================
       CARRUSEL DE TESTIMONIOS
    ===================================================== */

    const $testimonios = $('.testimonio');
    const $contenedorIndicadores = $('#indicadoresTestimonios');

    let indiceTestimonio = 0;
    let intervaloTestimonios = null;

    $testimonios.each(function (indice) {
        $('<span>', {
            class:
                'indicador-testimonio' +
                (indice === 0 ? ' activo' : ''),
            'data-indice': indice
        }).appendTo($contenedorIndicadores);
    });

    function mostrarTestimonio(indice) {
        if (!$testimonios.length) {
            return;
        }

        if (indice >= $testimonios.length) {
            indice = 0;
        }

        if (indice < 0) {
            indice = $testimonios.length - 1;
        }

        indiceTestimonio = indice;

        $testimonios.removeClass('testimonio-activo');
        $testimonios.eq(indiceTestimonio)
            .addClass('testimonio-activo');

        $('.indicador-testimonio').removeClass('activo');
        $('.indicador-testimonio').eq(indiceTestimonio)
            .addClass('activo');
    }

    function iniciarTestimonios() {
        detenerTestimonios();

        if ($testimonios.length > 1) {
            intervaloTestimonios = setInterval(function () {
                mostrarTestimonio(indiceTestimonio + 1);
            }, 6000);
        }
    }

    function detenerTestimonios() {
        if (intervaloTestimonios) {
            clearInterval(intervaloTestimonios);
            intervaloTestimonios = null;
        }
    }

    $('#testimonioSiguiente').on('click', function () {
        mostrarTestimonio(indiceTestimonio + 1);
        iniciarTestimonios();
    });

    $('#testimonioAnterior').on('click', function () {
        mostrarTestimonio(indiceTestimonio - 1);
        iniciarTestimonios();
    });

    $(document).on('click', '.indicador-testimonio', function () {
        mostrarTestimonio(Number($(this).data('indice')));
        iniciarTestimonios();
    });

    $('#testimoniosCarrusel')
        .on('mouseenter', detenerTestimonios)
        .on('mouseleave', iniciarTestimonios);

    iniciarTestimonios();

    /* =====================================================
       PREGUNTAS FRECUENTES
    ===================================================== */

    $('.pregunta-boton').on('click', function () {
        const $pregunta = $(this).closest('.pregunta');
        const $respuesta = $pregunta.find('.pregunta-respuesta');
        const estabaAbierta = $pregunta.hasClass('pregunta-abierta');

        $('.pregunta')
            .not($pregunta)
            .removeClass('pregunta-abierta')
            .find('.pregunta-respuesta')
            .stop(true, true)
            .slideUp(220);

        if (estabaAbierta) {
            $pregunta.removeClass('pregunta-abierta');
            $respuesta.stop(true, true).slideUp(220);
        } else {
            $pregunta.addClass('pregunta-abierta');
            $respuesta.stop(true, true).slideDown(220);
        }
    });

    /* =====================================================
       DESPLAZAMIENTO DEL MENÚ
    ===================================================== */

    $('a[href*="#"]').on('click', function (evento) {
        const enlace = $(this).attr('href');

        if (!enlace || enlace === '#') {
            return;
        }

        const partes = enlace.split('#');
        const identificador = partes[1];

        if (!identificador) {
            return;
        }

        const $destino = $('#' + identificador);

        if ($destino.length) {
            evento.preventDefault();

            $('html, body').animate({
                scrollTop: $destino.offset().top - 78
            }, 600);
        }
    });
});