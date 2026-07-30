$(document).ready(function () {
    'use strict';

    /* =====================================================
       ITINERARIO DESPLEGABLE
    ===================================================== */

    $('.encabezado-dia').on('click', function () {
        const $boton = $(this);
        const $contenido = $boton
            .closest('.dia-itinerario')
            .find('.contenido-dia');

        const estaAbierto = $boton.hasClass('dia-abierto');

        if (estaAbierto) {
            $boton
                .removeClass('dia-abierto')
                .attr('aria-expanded', 'false');

            $contenido.stop(true, true).slideUp(250);
        } else {
            $boton
                .addClass('dia-abierto')
                .attr('aria-expanded', 'true');

            $contenido.stop(true, true).slideDown(250);
        }
    });

    /* =====================================================
       NAVEGACIÓN INTERNA
    ===================================================== */

    const $enlacesDetalle = $('.enlace-detalle');

    /*
     * Eliminamos la interacción general de los enlaces internos
     * para aplicar una posición adecuada en esta página.
     */
    $enlacesDetalle.off('click');

    $enlacesDetalle.on('click', function (evento) {
        evento.preventDefault();

        const identificador = $(this).attr('href');
        const $seccion = $(identificador);

        if (!$seccion.length) {
            return;
        }

        $('html, body').stop(true).animate({
            scrollTop: $seccion.offset().top - 155
        }, 550);

        $enlacesDetalle.removeClass('activo');
        $(this).addClass('activo');
    });

    function actualizarSeccionActiva() {
        const posicionActual = $(window).scrollTop() + 190;

        let identificadorActivo = '';

        $enlacesDetalle.each(function () {
            const identificador = $(this).attr('href');
            const $seccion = $(identificador);

            if (
                $seccion.length &&
                $seccion.offset().top <= posicionActual
            ) {
                identificadorActivo = identificador;
            }
        });

        if (identificadorActivo) {
            $enlacesDetalle.removeClass('activo');

            $enlacesDetalle
                .filter(
                    '[href="' + identificadorActivo + '"]'
                )
                .addClass('activo');
        }
    }

    $(window).on('scroll', actualizarSeccionActiva);

    actualizarSeccionActiva();

    /* =====================================================
       CONSULTAR PAQUETE MEDIANTE TELEGRAM
    ===================================================== */

    $('#consultarPaquete').on('click', function () {
        const $boton = $(this);
        const paquete = String($boton.data('paquete') || '');
        const enlace = String($boton.data('enlace') || '');

        if (!enlace) {
            Swal.fire({
                icon: 'error',
                title: 'No se pudo abrir Telegram',
                text: 'El enlace de atención no está disponible en este momento.',
                confirmButtonText: 'Aceptar'
            });

            return;
        }

        Swal.fire({
            icon: 'question',
            title: '¿Deseas consultar este paquete?',
            html:
                'Te enviaremos a Telegram para solicitar información sobre:<br>' +
                '<strong>' + escaparHtml(paquete) + '</strong>',
            showCancelButton: true,
            confirmButtonText: 'Sí, continuar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
            focusCancel: true
        }).then(function (resultado) {
            if (resultado.isConfirmed) {
                window.open(
                    enlace,
                    '_blank',
                    'noopener,noreferrer'
                );
            }
        });
    });

    /* =====================================================
       BOTONES DIRECTOS DE TELEGRAM
    ===================================================== */

    $('.tarjeta-ayuda a').on('click', function (evento) {
        const enlace = $(this).attr('href');

        if (!enlace || enlace === '#') {
            evento.preventDefault();

            Swal.fire({
                icon: 'info',
                title: 'Atención no disponible',
                text: 'No se encontró el enlace de Telegram.',
                confirmButtonText: 'Aceptar'
            });
        }
    });

    /* =====================================================
       REGRESAR A LOS PAQUETES
    ===================================================== */

    $('.volver-paquetes').on('click', function () {
        sessionStorage.setItem(
            'volverSeccionPaquetes',
            'si'
        );
    });

    /* =====================================================
       PROTEGER TEXTO MOSTRADO EN SWEETALERT
    ===================================================== */

    function escaparHtml(texto) {
        return $('<div>')
            .text(texto)
            .html();
    }
});