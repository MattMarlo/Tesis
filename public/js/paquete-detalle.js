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
       FORMULARIO PÚBLICO DE PRERRESERVA
    ===================================================== */

    const $formularioPrerreserva = $('#formularioPrerreserva');
    const $formulario = $('#prerreservaPublicaForm');
    const $campoCantidad = $('#campoCantidadPersonas');
    const $cantidadPersonas = $('#cantidadPersonas');

    $('#irFormularioPrerreserva').on('click', function () {
        if (!$formularioPrerreserva.length) {
            return;
        }

        $('html, body').stop(true).animate({
            scrollTop: $formularioPrerreserva.offset().top - 135
        }, 650, function () {
            $formulario
                .find('input[name="tipo_reserva"]:checked')
                .trigger('focus');
        });
    });

    $formulario
        .find('input[name="tipo_reserva"]')
        .on('change', function () {
            const esGrupal = $(this).val() === 'grupal';

            $('.opcion-tipo-reserva').removeClass('seleccionada');
            $(this)
                .closest('.opcion-tipo-reserva')
                .addClass('seleccionada');

            $campoCantidad.prop('hidden', !esGrupal);

            if (esGrupal) {
                $cantidadPersonas
                    .attr('min', '2')
                    .val(Math.max(
                        2,
                        Number($cantidadPersonas.val()) || 2
                    ));
            } else {
                $cantidadPersonas
                    .attr('min', '1')
                    .val('1');
            }

            limpiarError('tipo_reserva');
            limpiarError('cantidad_personas');
        });

    $('#cedulaPrerreserva').on('input', function () {
        this.value = this.value
            .replace(/\D/g, '')
            .slice(0, 10);
    });

    $formulario.on('submit', function (evento) {
        evento.preventDefault();

        const $boton = $('#botonEnviarPrerreserva');
        const textoBoton = $boton.html();

        limpiarErroresPrerreserva();

        $boton
            .prop('disabled', true)
            .html(
                '<i class="fa fa-spinner fa-spin"></i>' +
                '<span><strong>Enviando...</strong>' +
                '<small>Espera un momento</small></span>'
            );

        $.ajax({
            url: $formulario.attr('action'),
            method: 'POST',
            data: $formulario.serialize(),
            dataType: 'json',
            headers: {
                Accept: 'application/json'
            }
        })
            .done(function (respuesta) {
                $formulario.prop('hidden', true);

                $('#mensajeConfirmacionPrerreserva')
                    .text(respuesta.message);

                $('#numeroPrerreserva')
                    .text('#' + respuesta.pre_reserva_id);

                $('#confirmacionPrerreserva')
                    .prop('hidden', false);

                $('html, body').stop(true).animate({
                    scrollTop: $formularioPrerreserva.offset().top - 135
                }, 450);
            })
            .fail(function (respuesta) {
                if (
                    respuesta.status === 422 &&
                    respuesta.responseJSON &&
                    respuesta.responseJSON.errors
                ) {
                    mostrarErroresPrerreserva(
                        respuesta.responseJSON.errors
                    );

                    return;
                }

                Swal.fire({
                    icon: 'error',
                    title: 'No se pudo enviar la prerreserva',
                    text: 'Inténtalo nuevamente en unos minutos.',
                    confirmButtonText: 'Aceptar'
                });
            })
            .always(function () {
                $boton
                    .prop('disabled', false)
                    .html(textoBoton);
            });
    });

    function limpiarErroresPrerreserva() {
        $formulario
            .find('.mensaje-error-campo')
            .text('');

        $formulario
            .find('.campo-con-error')
            .removeClass('campo-con-error');
    }

    function limpiarError(campo) {
        const $mensaje = $formulario.find(
            '[data-error-for="' + campo + '"]'
        );

        $mensaje.text('');
        $mensaje
            .closest('.campo-prerreserva')
            .removeClass('campo-con-error');
    }

    function mostrarErroresPrerreserva(errores) {
        let $primerCampo = $();

        $.each(errores, function (campo, mensajes) {
            const $mensaje = $formulario.find(
                '[data-error-for="' + campo + '"]'
            );

            if (!$mensaje.length) {
                return;
            }

            $mensaje.text(mensajes[0]);

            const $contenedorCampo = $mensaje
                .closest('.campo-prerreserva');

            $contenedorCampo.addClass('campo-con-error');

            if (!$primerCampo.length) {
                $primerCampo = $contenedorCampo.length
                    ? $contenedorCampo
                    : $mensaje;
            }
        });

        if ($primerCampo.length) {
            $('html, body').stop(true).animate({
                scrollTop: $primerCampo.offset().top - 165
            }, 400);
        }
    }

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
