$(document).ready(function () {
    'use strict';

    const $formulario = $('#formularioUsuario');
    const $password = $('#password');
    const $confirmacion = $('#password_confirmation');

    const esEdicion = !$password.prop('required');

    let formularioModificado = false;
    let formularioEnviado = false;

    function mostrarError($campo, mensaje) {
        const $contenedor = $campo.closest('.campo-usuario');

        $contenedor.addClass('campo-invalido');
        $contenedor.find('.error-campo').first().text(mensaje);
    }

    function limpiarError($campo) {
        const $contenedor = $campo.closest('.campo-usuario');

        $contenedor.removeClass('campo-invalido');
        $contenedor.find('.error-campo').first().text('');
    }

    function escaparHtml(texto) {
        return $('<div>').text(texto).html();
    }

    function actualizarDescripcionRol() {
        const rol = $('#rol').val();

        if (rol === 'admin') {
            $('#descripcionRol').text(
                'Tendrá acceso completo, incluido el módulo de usuarios.'
            );

            return;
        }

        if (rol === 'agente') {
            $('#descripcionRol').text(
                'Podrá utilizar el sistema, pero no tendrá acceso a la administración de usuarios.'
            );

            return;
        }

        $('#descripcionRol').text(
            'Selecciona el acceso que tendrá esta persona.'
        );
    }

    function validarFormulario() {
        let esValido = true;

        const nombres = $.trim($('#nombres').val());
        const apellidos = $.trim($('#apellidos').val());
        const documento = $.trim($('#documento').val());
        const telefono = $.trim($('#telefono').val());
        const email = $.trim($('#email').val());
        const rol = $('#rol').val();
        const estado = $('#estado').val();
        const contrasena = $password.val();
        const confirmarContrasena = $confirmacion.val();

        const formatoNombre = /^[\p{L}\s'-]+$/u;
        const formatoDocumento = /^[A-Za-z0-9-]+$/;
        const formatoTelefono = /^[0-9+\s()-]+$/;
        const formatoCorreo = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        $('.campo-usuario').removeClass('campo-invalido');
        $('.error-campo').text('');

        if (!nombres) {
            mostrarError(
                $('#nombres'),
                'Ingresa los nombres.'
            );

            esValido = false;
        } else if (!formatoNombre.test(nombres)) {
            mostrarError(
                $('#nombres'),
                'Utiliza solamente letras, espacios, apóstrofes o guiones.'
            );

            esValido = false;
        }

        if (!apellidos) {
            mostrarError(
                $('#apellidos'),
                'Ingresa los apellidos.'
            );

            esValido = false;
        } else if (!formatoNombre.test(apellidos)) {
            mostrarError(
                $('#apellidos'),
                'Utiliza solamente letras, espacios, apóstrofes o guiones.'
            );

            esValido = false;
        }

        if (!documento) {
            mostrarError(
                $('#documento'),
                'Ingresa el número de identificación.'
            );

            esValido = false;
        } else if (
            documento.length < 5 ||
            !formatoDocumento.test(documento)
        ) {
            mostrarError(
                $('#documento'),
                'Ingresa una identificación válida, sin espacios.'
            );

            esValido = false;
        }

        if (!telefono) {
            mostrarError(
                $('#telefono'),
                'Ingresa el teléfono.'
            );

            esValido = false;
        } else if (
            telefono.length < 7 ||
            !formatoTelefono.test(telefono)
        ) {
            mostrarError(
                $('#telefono'),
                'Ingresa un número de teléfono válido.'
            );

            esValido = false;
        }

        if (!email) {
            mostrarError(
                $('#email'),
                'Ingresa el correo electrónico.'
            );

            esValido = false;
        } else if (!formatoCorreo.test(email)) {
            mostrarError(
                $('#email'),
                'Escribe un correo electrónico válido.'
            );

            esValido = false;
        }

        if (!rol) {
            mostrarError(
                $('#rol'),
                'Selecciona el tipo de usuario.'
            );

            esValido = false;
        }

        if (!estado) {
            mostrarError(
                $('#estado'),
                'Selecciona el estado de la cuenta.'
            );

            esValido = false;
        }

        const debeValidarContrasena =
            !esEdicion ||
            contrasena.length > 0 ||
            confirmarContrasena.length > 0;

        if (debeValidarContrasena) {
            if (!contrasena) {
                mostrarError(
                    $password,
                    'Ingresa la contraseña.'
                );

                esValido = false;
            } else if (contrasena.length < 8) {
                mostrarError(
                    $password,
                    'La contraseña debe tener al menos ocho caracteres.'
                );

                esValido = false;
            } else if (
                !/[A-Za-zÁÉÍÓÚáéíóúÑñ]/.test(contrasena) ||
                !/[0-9]/.test(contrasena)
            ) {
                mostrarError(
                    $password,
                    'La contraseña debe incluir letras y números.'
                );

                esValido = false;
            }

            if (!confirmarContrasena) {
                mostrarError(
                    $confirmacion,
                    'Confirma la contraseña.'
                );

                esValido = false;
            } else if (contrasena !== confirmarContrasena) {
                mostrarError(
                    $confirmacion,
                    'Las contraseñas no coinciden.'
                );

                esValido = false;
            }
        }

        return esValido;
    }

    $('.mostrar-contrasena').on('click', function () {
        const $campo = $($(this).data('campo'));
        const mostrar = $campo.attr('type') === 'password';

        $campo.attr(
            'type',
            mostrar ? 'text' : 'password'
        );

        $(this)
            .attr(
                'aria-label',
                mostrar
                    ? 'Ocultar contraseña'
                    : 'Mostrar contraseña'
            )
            .find('i')
            .toggleClass('bi-eye bi-eye-slash');
    });

    $('#documento').on('input', function () {
        this.value = this.value
            .toUpperCase()
            .replace(/\s/g, '');

        limpiarError($(this));
    });

    $('#email').on('input', function () {
        this.value = this.value.toLowerCase();
        limpiarError($(this));
    });

    $(
        '#nombres, #apellidos, #telefono, ' +
        '#password, #password_confirmation'
    ).on('input', function () {
        limpiarError($(this));
    });

    $('#rol').on('change', function () {
        actualizarDescripcionRol();
        limpiarError($(this));
    });

    $('#estado').on('change', function () {
        limpiarError($(this));
    });

    $formulario.on(
        'input change',
        'input, select',
        function () {
            formularioModificado = true;
        }
    );

    $formulario.on('submit', function (evento) {
        evento.preventDefault();

        if (!validarFormulario()) {
            const $primerError = $('.campo-invalido').first();

            Swal.fire({
                icon: 'error',
                title: 'Revisa la información',
                text: 'Corrige los campos señalados antes de continuar.',
                confirmButtonText: 'Corregir'
            }).then(function () {
                if ($primerError.length) {
                    $('html, body').animate({
                        scrollTop:
                            $primerError.offset().top - 120
                    }, 400);
                }
            });

            return;
        }

        const nombreCompleto =
            $.trim($('#nombres').val()) +
            ' ' +
            $.trim($('#apellidos').val());

        const estado = $('#estado').val();

        let mensaje =
            'Se guardará la información de ' +
            nombreCompleto +
            '.';

        if (estado === 'inactivo') {
            mensaje =
                'La cuenta se guardará como inactiva y no podrá iniciar sesión.';
        }

        Swal.fire({
            icon: 'question',
            title: esEdicion
                ? '¿Guardar los cambios?'
                : '¿Registrar usuario?',
            text: mensaje,
            showCancelButton: true,
            confirmButtonText: esEdicion
                ? 'Sí, guardar'
                : 'Sí, registrar',
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

    $('#cancelarUsuario').on('click', function (evento) {
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
        $('#erroresServidorUsuario').data('errores');

    if (
        Array.isArray(erroresServidor) &&
        erroresServidor.length > 0
    ) {
        const lista = erroresServidor
            .map(function (error) {
                return '<li>' +
                    escaparHtml(error) +
                    '</li>';
            })
            .join('');

        Swal.fire({
            icon: 'error',
            title: 'Revisa la información',
            html:
                '<ul style="text-align:left;padding-left:20px;">' +
                lista +
                '</ul>',
            confirmButtonText: 'Corregir'
        });
    }

    actualizarDescripcionRol();
});