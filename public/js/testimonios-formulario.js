$(document).ready(function () {
    'use strict';

    const $formulario = $('#formularioTestimonio');
    const $comentario = $('#comentario');
    const $foto = $('#foto');
    const $imagen = $('#imagenTestimonio');
    const $sinImagen = $('#imagenSinContenido');

    let formularioModificado = false;
    let formularioEnviado = false;

    function escaparHtml(texto) {
        return $('<div>').text(texto).html();
    }

    function actualizarContador() {
        $('#cantidadComentario').text(
            $comentario.val().length
        );
    }

    function textoCalificacion(valor) {
        const textos = {
            1: 'Deficiente',
            2: 'Regular',
            3: 'Buena',
            4: 'Muy buena',
            5: 'Excelente'
        };

        return textos[valor] || 'Selecciona una calificación';
    }

    function actualizarCalificacion() {
        const valor = Number(
            $('input[name="calificacion"]:checked').val()
        );

        $('#textoCalificacion').text(
            textoCalificacion(valor)
        );
    }

    function mostrarError($campo, mensaje) {
        const $grupo = $campo.closest('.grupo-testimonio');

        $grupo.addClass('campo-error');
        $grupo.find('.mensaje-error').first().text(mensaje);
    }

    function limpiarError($campo) {
        const $grupo = $campo.closest('.grupo-testimonio');

        $grupo.removeClass('campo-error');
        $grupo.find('.mensaje-error').first().text('');
    }

    function validarFormulario() {
        let formularioValido = true;

        const nombre = $('#nombre').val().trim();
        const comentario = $comentario.val().trim();
        const estado = $('#estado').val();
        const calificacion = Number(
            $('input[name="calificacion"]:checked').val()
        );

        $('.grupo-testimonio')
            .removeClass('campo-error');

        $('.mensaje-error').text('');

        if (!nombre) {
            mostrarError(
                $('#nombre'),
                'Ingresa el nombre del cliente.'
            );

            formularioValido = false;
        } else if (nombre.length > 100) {
            mostrarError(
                $('#nombre'),
                'El nombre no puede superar los 100 caracteres.'
            );

            formularioValido = false;
        }

        if (!comentario) {
            mostrarError(
                $comentario,
                'Ingresa el comentario del cliente.'
            );

            formularioValido = false;
        } else if (comentario.length < 10) {
            mostrarError(
                $comentario,
                'El comentario debe tener al menos 10 caracteres.'
            );

            formularioValido = false;
        } else if (comentario.length > 1000) {
            mostrarError(
                $comentario,
                'El comentario no puede superar los 1000 caracteres.'
            );

            formularioValido = false;
        }

        if (!calificacion || calificacion < 1 || calificacion > 5) {
            $('#errorCalificacion').text(
                'Selecciona una calificación entre una y cinco estrellas.'
            );

            formularioValido = false;
        }

        if (!estado) {
            mostrarError(
                $('#estado'),
                'Selecciona el estado del testimonio.'
            );

            formularioValido = false;
        }

        const orden = $('#orden').val();

        if (
            orden !== '' &&
            (
                Number(orden) < 0 ||
                Number(orden) > 9999 ||
                !Number.isInteger(Number(orden))
            )
        ) {
            mostrarError(
                $('#orden'),
                'Ingresa un número entero entre 0 y 9999.'
            );

            formularioValido = false;
        }

        return formularioValido;
    }

    function validarFotografia(archivo) {
        const formatosPermitidos = [
            'image/jpeg',
            'image/png',
            'image/webp'
        ];

        const tamanoMaximo = 4 * 1024 * 1024;

        if (!formatosPermitidos.includes(archivo.type)) {
            $('#errorFoto').text(
                'Selecciona una imagen JPG, PNG o WEBP.'
            );

            return false;
        }

        if (archivo.size > tamanoMaximo) {
            $('#errorFoto').text(
                'La fotografía no puede superar los 4 MB.'
            );

            return false;
        }

        $('#errorFoto').text('');

        return true;
    }

    $comentario.on('input', function () {
        actualizarContador();
        limpiarError($(this));
    });

    $('input[name="calificacion"]').on('change', function () {
        actualizarCalificacion();
        $('#errorCalificacion').text('');
    });

    $('#nombre, #destino, #orden').on('input', function () {
        limpiarError($(this));
    });

    $('#estado').on('change', function () {
        limpiarError($(this));
    });

    $foto.on('change', function () {
        const archivo = this.files[0];

        $('#errorFoto').text('');

        if (!archivo) {
            return;
        }

        if (!validarFotografia(archivo)) {
            $(this).val('');
            return;
        }

        const lector = new FileReader();

        lector.onload = function (evento) {
            $imagen
                .attr('src', evento.target.result)
                .show();

            $sinImagen.hide();
        };

        lector.readAsDataURL(archivo);
    });

    $formulario.on(
        'input change',
        'input, textarea, select',
        function () {
            formularioModificado = true;
        }
    );

    $formulario.on('submit', function (evento) {
        evento.preventDefault();

        if (!validarFormulario()) {
            const $primerError = $('.campo-error').first();

            Swal.fire({
                icon: 'error',
                title: 'Revisa la información ingresada',
                text: 'Completa correctamente los campos señalados.',
                confirmButtonText: 'Corregir'
            }).then(function () {
                if ($primerError.length) {
                    $('html, body').animate({
                        scrollTop:
                            $primerError.offset().top - 120
                    }, 450);
                }
            });

            return;
        }

        const estado = $('#estado').val();

        let mensaje =
            'El testimonio se guardará con estado pendiente.';

        if (estado === 'publicado') {
            mensaje =
                'El testimonio se mostrará públicamente en la página principal.';
        }

        if (estado === 'oculto') {
            mensaje =
                'El testimonio se guardará, pero no será visible públicamente.';
        }

        Swal.fire({
            icon: 'question',
            title: '¿Guardar testimonio?',
            text: mensaje,
            showCancelButton: true,
            confirmButtonText: 'Sí, guardar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        }).then(function (resultado) {
            if (resultado.isConfirmed) {
                formularioEnviado = true;
                formularioModificado = false;

                $formulario[0].submit();
            }
        });
    });

    $('#cancelarTestimonio').on('click', function (evento) {
        if (!formularioModificado || formularioEnviado) {
            return;
        }

        evento.preventDefault();

        const enlace = $(this).attr('href');

        Swal.fire({
            icon: 'warning',
            title: '¿Salir sin guardar?',
            text: 'Los cambios realizados se perderán.',
            showCancelButton: true,
            confirmButtonText: 'Sí, salir',
            cancelButtonText: 'Continuar editando',
            reverseButtons: true
        }).then(function (resultado) {
            if (resultado.isConfirmed) {
                window.location.href = enlace;
            }
        });
    });

    const erroresServidor =
        $('#erroresServidorTestimonio').data('errores');

    if (
        Array.isArray(erroresServidor) &&
        erroresServidor.length > 0
    ) {
        const listaErrores = erroresServidor
            .map(function (error) {
                return '<li>' + escaparHtml(error) + '</li>';
            })
            .join('');

        Swal.fire({
            icon: 'error',
            title: 'Revisa la información ingresada',
            html:
                '<ul style="text-align:left; padding-left:20px;">' +
                listaErrores +
                '</ul>',
            confirmButtonText: 'Corregir'
        });
    }

    actualizarContador();
    actualizarCalificacion();
});