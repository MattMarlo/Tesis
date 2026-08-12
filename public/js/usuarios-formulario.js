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
        $campo.attr('aria-invalid', 'true');

        return false;
    }

    function limpiarError($campo) {
        const $contenedor = $campo.closest('.campo-usuario');

        $contenedor.removeClass('campo-invalido');
        $contenedor.find('.error-campo').first().text('');
        $campo.removeAttr('aria-invalid');

        return true;
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

    function validarCampoIndividual($campo) {
        const id = $campo.attr('id');
        const valor = $.trim($campo.val());
        const formatoNombre = /^(?=.*\p{L})[\p{L}\s'-]+$/u;
        const formatoDocumento =
            /^(?=.*[A-Za-z0-9])[A-Za-z0-9-]+$/;
        const formatoTelefono = /^\+?[0-9\s()-]+$/;
        const formatoCorreo = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (id === 'nombres' || id === 'apellidos') {
            const etiqueta = id === 'nombres'
                ? 'Los nombres'
                : 'Los apellidos';

            if (!valor) {
                return mostrarError(
                    $campo,
                    id === 'nombres'
                        ? 'Ingresa los nombres.'
                        : 'Ingresa los apellidos.'
                );
            }

            if (valor.length < 2) {
                return mostrarError(
                    $campo,
                    etiqueta +
                        ' deben tener al menos dos caracteres.'
                );
            }

            if (
                valor.length > 100 ||
                !formatoNombre.test(valor)
            ) {
                return mostrarError(
                    $campo,
                    'Utiliza solamente letras, espacios, apóstrofes o guiones.'
                );
            }

            return limpiarError($campo);
        }

        if (id === 'documento') {
            if (!valor) {
                return mostrarError(
                    $campo,
                    'Ingresa el número de identificación.'
                );
            }

            if (
                valor.length < 5 ||
                valor.length > 30 ||
                !formatoDocumento.test(valor)
            ) {
                return mostrarError(
                    $campo,
                    'Ingresa entre 5 y 30 letras, números o guiones, sin espacios.'
                );
            }

            return limpiarError($campo);
        }

        if (id === 'telefono') {
            if (!valor) {
                return mostrarError(
                    $campo,
                    'Ingresa el teléfono.'
                );
            }

            const cantidadDigitos =
                (valor.match(/\d/g) || []).length;

            if (
                valor.length > 20 ||
                !formatoTelefono.test(valor) ||
                cantidadDigitos < 7 ||
                cantidadDigitos > 15
            ) {
                return mostrarError(
                    $campo,
                    'Ingresa un teléfono con entre 7 y 15 dígitos.'
                );
            }

            return limpiarError($campo);
        }

        if (id === 'email') {
            if (!valor) {
                return mostrarError(
                    $campo,
                    'Ingresa el correo electrónico.'
                );
            }

            if (
                valor.length > 100 ||
                !formatoCorreo.test(valor)
            ) {
                return mostrarError(
                    $campo,
                    'Escribe un correo electrónico válido.'
                );
            }

            return limpiarError($campo);
        }

        if (id === 'rol') {
            if (!['admin', 'agente'].includes(valor)) {
                return mostrarError(
                    $campo,
                    'Selecciona el tipo de usuario.'
                );
            }

            return limpiarError($campo);
        }

        if (id === 'estado') {
            if (!['activo', 'inactivo'].includes(valor)) {
                return mostrarError(
                    $campo,
                    'Selecciona el estado de la cuenta.'
                );
            }

            return limpiarError($campo);
        }

        return true;
    }

    function validarContrasenasEnVivo() {
        const contrasena = $password.val();
        const confirmacion = $confirmacion.val();
        const debeValidar =
            !esEdicion ||
            contrasena.length > 0 ||
            confirmacion.length > 0;

        if (!debeValidar) {
            limpiarError($password);
            limpiarError($confirmacion);

            return true;
        }

        let esValida = true;

        if (!contrasena) {
            mostrarError($password, 'Ingresa la contraseña.');
            esValida = false;
        } else if (contrasena.length < 8) {
            mostrarError(
                $password,
                'La contraseña debe tener al menos ocho caracteres.'
            );
            esValida = false;
        } else if (contrasena.length > 72) {
            mostrarError(
                $password,
                'La contraseña no puede superar los 72 caracteres.'
            );
            esValida = false;
        } else if (
            !/\p{L}/u.test(contrasena) ||
            !/\p{N}/u.test(contrasena)
        ) {
            mostrarError(
                $password,
                'La contraseña debe incluir letras y números.'
            );
            esValida = false;
        } else {
            limpiarError($password);
        }

        if (!confirmacion) {
            mostrarError(
                $confirmacion,
                'Confirma la contraseña.'
            );
            esValida = false;
        } else if (contrasena !== confirmacion) {
            mostrarError(
                $confirmacion,
                'Las contraseñas no coinciden.'
            );
            esValida = false;
        } else {
            limpiarError($confirmacion);
        }

        return esValida;
    }

    function validarFormulario() {
        let esValido = true;

        $('#nombres, #apellidos').each(function () {
            this.value = $.trim(this.value).replace(/\s+/g, ' ');
        });

        $('#email').val(
            $.trim($('#email').val()).toLowerCase()
        );
        $('#telefono').val($.trim($('#telefono').val()));

        const nombres = $.trim($('#nombres').val());
        const apellidos = $.trim($('#apellidos').val());
        const documento = $.trim($('#documento').val());
        const telefono = $.trim($('#telefono').val());
        const email = $.trim($('#email').val());
        const rol = $('#rol').val();
        const estado = $('#estado').val();
        const contrasena = $password.val();
        const confirmarContrasena = $confirmacion.val();

        const formatoNombre = /^(?=.*\p{L})[\p{L}\s'-]+$/u;
        const formatoDocumento =
            /^(?=.*[A-Za-z0-9])[A-Za-z0-9-]+$/;
        const formatoTelefono = /^\+?[0-9\s()-]+$/;
        const formatoCorreo = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        $('.campo-usuario').removeClass('campo-invalido');
        $('.error-campo').text('');
        $('.campo-usuario input, .campo-usuario select')
            .removeAttr('aria-invalid');

        if (!nombres) {
            mostrarError(
                $('#nombres'),
                'Ingresa los nombres.'
            );

            esValido = false;
        } else if (nombres.length < 2) {
            mostrarError(
                $('#nombres'),
                'Los nombres deben tener al menos dos caracteres.'
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
        } else if (apellidos.length < 2) {
            mostrarError(
                $('#apellidos'),
                'Los apellidos deben tener al menos dos caracteres.'
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
        } else if (!formatoTelefono.test(telefono)) {
            mostrarError(
                $('#telefono'),
                'Ingresa un número de teléfono válido.'
            );

            esValido = false;
        } else {
            const cantidadDigitos =
                (telefono.match(/\d/g) || []).length;

            if (
                cantidadDigitos < 7 ||
                cantidadDigitos > 15
            ) {
                mostrarError(
                    $('#telefono'),
                    'El teléfono debe contener entre 7 y 15 dígitos.'
                );

                esValido = false;
            }
        }

        if (!email) {
            mostrarError(
                $('#email'),
                'Ingresa el correo electrónico.'
            );

            esValido = false;
        } else if (
            email.length > 100 ||
            !formatoCorreo.test(email)
        ) {
            mostrarError(
                $('#email'),
                'Escribe un correo electrónico válido.'
            );

            esValido = false;
        }

        if (!['admin', 'agente'].includes(rol)) {
            mostrarError(
                $('#rol'),
                'Selecciona el tipo de usuario.'
            );

            esValido = false;
        }

        if (!['activo', 'inactivo'].includes(estado)) {
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
            } else if (contrasena.length > 72) {
                mostrarError(
                    $password,
                    'La contraseña no puede superar los 72 caracteres.'
                );

                esValido = false;
            } else if (
                !/\p{L}/u.test(contrasena) ||
                !/\p{N}/u.test(contrasena)
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

        validarCampoIndividual($(this));
    });

    $('#documento').on('blur', function () {
        validarCampoIndividual($(this));
    });

    $('#email').on('input', function () {
        this.value = this.value.toLowerCase();
        validarCampoIndividual($(this));
    });

    $('#email').on('blur', function () {
        validarCampoIndividual($(this));
    });

    $(
        '#nombres, #apellidos, #telefono'
    ).on('input blur', function () {
        validarCampoIndividual($(this));
    });

    $password.add($confirmacion).on('input blur', function () {
        validarContrasenasEnVivo();
    });

    $('#rol').on('change', function () {
        actualizarDescripcionRol();
        validarCampoIndividual($(this));
    });

    $('#estado').on('change', function () {
        validarCampoIndividual($(this));
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
    const erroresServidorPorCampo =
        $('#erroresServidorUsuario').data('errores-campos');

    if (
        erroresServidorPorCampo &&
        typeof erroresServidorPorCampo === 'object'
    ) {
        Object.entries(erroresServidorPorCampo).forEach(
            function ([nombreCampo, mensajes]) {
                const $campo = $('[name="' + nombreCampo + '"]');

                if ($campo.length && mensajes.length) {
                    mostrarError($campo, mensajes[0]);
                }
            }
        );
    }

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
