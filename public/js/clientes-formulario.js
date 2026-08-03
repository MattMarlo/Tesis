$(function () {
    const $formulario = $('#formularioCliente');
    const $tipoDocumento = $('#tipo_documento');
    const $documento = $('#documento');
    const $contenedorCaducidad = $('#contenedorCaducidad');
    const $fechaCaducidad = $('#fecha_caducidad_documento');
    const $contactoEmergencia = $('#contacto_emergencia');
    const $telefonoEmergencia = $('#telefono_emergencia');
    const configuracion =
        window.configuracionFormularioCliente || {};

    let enviando = false;

    function textoLimpio(valor) {
        return $.trim(String(valor || ''));
    }

    function mostrarError(idCampo, mensaje) {
        const $campo = $('#' + idCampo);
        const $error = $('#' + idCampo + 'Error');

        $campo.toggleClass('input-error', Boolean(mensaje));
        $error.text(mensaje || '').toggle(Boolean(mensaje));

        return !mensaje;
    }

    function esNacionalidadEcuatoriana() {
        const nacionalidad = textoLimpio(
            $('#nacionalidad').val()
        )
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase();

        return [
            'ecuador',
            'ecuatoriana',
            'ecuatoriano'
        ].includes(nacionalidad);
    }

    function cedulaEcuatorianaValida(cedula) {
        if (!/^\d{10}$/.test(cedula)) {
            return false;
        }

        const provincia = parseInt(
            cedula.substring(0, 2),
            10
        );

        const tercerDigito = parseInt(
            cedula.charAt(2),
            10
        );

        const provinciaValida =
            (provincia >= 1 && provincia <= 24) ||
            provincia === 30;

        if (!provinciaValida || tercerDigito > 5) {
            return false;
        }

        let suma = 0;

        for (let indice = 0; indice < 9; indice++) {
            let digito = parseInt(
                cedula.charAt(indice),
                10
            );

            if (indice % 2 === 0) {
                digito *= 2;

                if (digito > 9) {
                    digito -= 9;
                }
            }

            suma += digito;
        }

        const digitoVerificador =
            (10 - (suma % 10)) % 10;

        return digitoVerificador ===
            parseInt(cedula.charAt(9), 10);
    }

    function validarNombre(idCampo, etiqueta) {
        const valor = textoLimpio($('#' + idCampo).val());
        const formato = /^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ' -]+$/;

        if (!valor) {
            return mostrarError(
                idCampo,
                `Ingresa ${etiqueta}.`
            );
        }

        if (valor.length < 2) {
            return mostrarError(
                idCampo,
                `${etiqueta} debe tener al menos 2 caracteres.`
            );
        }

        if (!formato.test(valor)) {
            return mostrarError(
                idCampo,
                `${etiqueta} solo puede contener letras.`
            );
        }

        return mostrarError(idCampo, '');
    }

    function configurarDocumento() {
        const tipo = $tipoDocumento.val();

        if (tipo === 'cedula') {
            $documento
                .attr({
                    placeholder: 'Ejemplo: 1723456789',
                    maxlength: 10,
                    inputmode: 'numeric'
                });

            $('#documentoAyuda').text(
                'La cédula debe contener exactamente 10 números.'
            );

            $contenedorCaducidad.addClass('campo-oculto');
            $fechaCaducidad.prop('required', false);
        } else if (tipo === 'pasaporte') {
            $documento
                .attr({
                    placeholder: 'Ejemplo: A1234567',
                    maxlength: 20,
                    inputmode: 'text'
                });

            $('#documentoAyuda').text(
                'Utiliza entre 6 y 20 letras, números o guiones.'
            );

            $contenedorCaducidad.removeClass('campo-oculto');
            $fechaCaducidad.prop('required', true);
        } else {
            $documento
                .attr({
                    placeholder:
                        'Selecciona primero el tipo de documento',
                    maxlength: 20,
                    inputmode: 'text'
                });

            $('#documentoAyuda').text(
                'Selecciona el tipo de documento.'
            );

            $contenedorCaducidad.addClass('campo-oculto');
            $fechaCaducidad.prop('required', false);
        }
    }

    function validarTipoDocumento() {
        const tipo = $tipoDocumento.val();

        if (!tipo) {
            return mostrarError(
                'tipo_documento',
                'Selecciona el tipo de documento.'
            );
        }

        return mostrarError('tipo_documento', '');
    }

    function validarDocumento() {
        const tipo = $tipoDocumento.val();
        const valor = textoLimpio($documento.val())
            .toUpperCase();

        if (!tipo) {
            return mostrarError(
                'documento',
                'Selecciona primero el tipo de documento.'
            );
        }

        if (!valor) {
            return mostrarError(
                'documento',
                'Ingresa el número de documento.'
            );
        }

        if (tipo === 'cedula' && !/^\d{10}$/.test(valor)) {
            return mostrarError(
                'documento',
                'La cédula debe contener exactamente 10 números.'
            );
        }

        if (
            tipo === 'cedula' &&
            !cedulaEcuatorianaValida(valor)
        ) {
            return mostrarError(
                'documento',
                'La cédula ecuatoriana ingresada no es válida.'
            );
        }

        if (
            tipo === 'pasaporte' &&
            !/^[A-Z0-9-]{6,20}$/.test(valor)
        ) {
            return mostrarError(
                'documento',
                'El pasaporte debe contener entre 6 y 20 letras, números o guiones.'
            );
        }

        return mostrarError('documento', '');
    }

    function validarFechaNacimiento() {
        const valor = $('#fecha_nacimiento').val();

        if (!valor) {
            return mostrarError(
                'fecha_nacimiento',
                'Ingresa la fecha de nacimiento.'
            );
        }

        const fecha = new Date(valor + 'T00:00:00');
        const hoy = new Date();
        hoy.setHours(0, 0, 0, 0);

        if (fecha >= hoy) {
            return mostrarError(
                'fecha_nacimiento',
                'La fecha de nacimiento debe ser anterior a hoy.'
            );
        }

        return mostrarError('fecha_nacimiento', '');
    }

    function validarNacionalidad() {
        return validarNombre(
            'nacionalidad',
            'la nacionalidad'
        );
    }

    function validarCaducidad() {
        if ($tipoDocumento.val() !== 'pasaporte') {
            return mostrarError(
                'fecha_caducidad_documento',
                ''
            );
        }

        const valor = $fechaCaducidad.val();

        if (!valor) {
            return mostrarError(
                'fecha_caducidad_documento',
                'Ingresa la fecha de caducidad del pasaporte.'
            );
        }

        const fecha = new Date(valor + 'T00:00:00');
        const hoy = new Date();
        hoy.setHours(0, 0, 0, 0);

        if (fecha <= hoy) {
            return mostrarError(
                'fecha_caducidad_documento',
                'El pasaporte debe encontrarse vigente.'
            );
        }

        return mostrarError(
            'fecha_caducidad_documento',
            ''
        );
    }

    function validarCorreo() {
        const valor = textoLimpio($('#email').val());
        const formato = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (!valor) {
            return mostrarError(
                'email',
                'Ingresa el correo electrónico.'
            );
        }

        if (!formato.test(valor)) {
            return mostrarError(
                'email',
                'Ingresa un correo electrónico válido.'
            );
        }

        return mostrarError('email', '');
    }

    function validarTelefono(idCampo, obligatorio) {
        const valor = textoLimpio($('#' + idCampo).val());

        if (!valor && obligatorio) {
            return mostrarError(
                idCampo,
                'Ingresa el número de teléfono.'
            );
        }

        if (!valor && !obligatorio) {
            return mostrarError(idCampo, '');
        }

        if (!/^\d{7,15}$/.test(valor)) {
            return mostrarError(
                idCampo,
                'El teléfono debe contener entre 7 y 15 números.'
            );
        }

        if (
            idCampo === 'telefono' &&
            esNacionalidadEcuatoriana()
        ) {
            const formatoLocal = /^09\d{8}$/;
            const formatoInternacional = /^5939\d{8}$/;

            if (
                !formatoLocal.test(valor) &&
                !formatoInternacional.test(valor)
            ) {
                return mostrarError(
                    idCampo,
                    'El celular ecuatoriano debe tener el formato 09XXXXXXXX o 5939XXXXXXXX.'
                );
            }
        }

        return mostrarError(idCampo, '');
    }

    function validarContactoEmergencia() {
        const contacto = textoLimpio(
            $contactoEmergencia.val()
        );

        const telefono = textoLimpio(
            $telefonoEmergencia.val()
        );

        const formatoNombre =
            /^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ' -]+$/;

        let contactoValido = true;
        let telefonoValido = true;

        if (telefono && !contacto) {
            contactoValido = mostrarError(
                'contacto_emergencia',
                'Ingresa el nombre del contacto de emergencia.'
            );
        } else if (
            contacto &&
            (
                contacto.length < 3 ||
                !formatoNombre.test(contacto)
            )
        ) {
            contactoValido = mostrarError(
                'contacto_emergencia',
                'Ingresa un nombre válido para el contacto.'
            );
        } else {
            contactoValido = mostrarError(
                'contacto_emergencia',
                ''
            );
        }

        if (contacto && !telefono) {
            telefonoValido = mostrarError(
                'telefono_emergencia',
                'Ingresa el teléfono del contacto de emergencia.'
            );
        } else {
            telefonoValido = validarTelefono(
                'telefono_emergencia',
                false
            );
        }

        return contactoValido && telefonoValido;
    }

    function validarEstado() {
        if (!$('#estado').val()) {
            return mostrarError(
                'estado',
                'Selecciona el estado del cliente.'
            );
        }

        return mostrarError('estado', '');
    }

    function validarArchivo() {
        const input = $('#archivo')[0];

        if (!input.files.length) {
            return mostrarError('archivo', '');
        }

        const archivo = input.files[0];
        const extension = archivo.name
            .split('.')
            .pop()
            .toLowerCase();

        const extensionesPermitidas = [
            'pdf',
            'jpg',
            'jpeg',
            'png'
        ];

        if (!extensionesPermitidas.includes(extension)) {
            return mostrarError(
                'archivo',
                'Selecciona un archivo PDF, JPG, JPEG o PNG.'
            );
        }

        if (archivo.size > 5 * 1024 * 1024) {
            return mostrarError(
                'archivo',
                'El archivo no puede superar los 5 MB.'
            );
        }

        return mostrarError('archivo', '');
    }

    function validarFormulario() {
        const resultados = [
            validarNombre('nombres', 'los nombres'),
            validarNombre('apellidos', 'los apellidos'),
            validarTipoDocumento(),
            validarDocumento(),
            validarFechaNacimiento(),
            validarNacionalidad(),
            validarCaducidad(),
            validarCorreo(),
            validarTelefono('telefono', true),
            validarContactoEmergencia(),
            validarEstado(),
            validarArchivo()
        ];

        return resultados.every(Boolean);
    }

    $tipoDocumento.on('change', function () {
        configurarDocumento();

        if ($(this).val() === 'cedula') {
            $documento.val(
                $documento.val().replace(/\D/g, '')
            );

            $fechaCaducidad.val('');
        } else {
            $documento.val(
                $documento.val()
                    .toUpperCase()
                    .replace(/[^A-Z0-9-]/g, '')
            );
        }

        validarTipoDocumento();
        validarDocumento();
        validarCaducidad();
    });

    $documento.on('input', function () {
        if ($tipoDocumento.val() === 'cedula') {
            this.value = this.value.replace(/\D/g, '');
        } else if ($tipoDocumento.val() === 'pasaporte') {
            this.value = this.value
                .toUpperCase()
                .replace(/[^A-Z0-9-]/g, '');
        }
    });

    $('#telefono, #telefono_emergencia').on(
        'input',
        function () {
            this.value = this.value.replace(/\D/g, '');
        }
    );

    $('#nombres').on(
        'blur input',
        function () {
            validarNombre('nombres', 'los nombres');
        }
    );

    $('#apellidos').on(
        'blur input',
        function () {
            validarNombre('apellidos', 'los apellidos');
        }
    );

    $documento.on('blur', validarDocumento);
    $('#fecha_nacimiento').on('change blur', validarFechaNacimiento);
    $('#nacionalidad').on('blur input', function () {
        validarNacionalidad();
        validarTelefono('telefono', true);
    });
    $fechaCaducidad.on('change blur', validarCaducidad);
    $('#email').on('blur input', validarCorreo);

    $('#telefono').on('blur input', function () {
        validarTelefono('telefono', true);
    });

    $contactoEmergencia
        .add($telefonoEmergencia)
        .on('blur input', validarContactoEmergencia);

    $('#estado').on('change', validarEstado);
    $('#archivo').on('change', validarArchivo);

    $formulario.on('submit', function (evento) {
        evento.preventDefault();

        if (enviando) {
            return;
        }

        if (!validarFormulario()) {
            const $primerError = $('.input-error').first();

            if ($primerError.length) {
                $('html, body').animate({
                    scrollTop:
                        $primerError.offset().top - 140
                }, 250);

                $primerError.trigger('focus');
            }

            Swal.fire({
                icon: 'error',
                title: 'Revisa la información',
                text: 'Corrige los campos señalados antes de continuar.',
                confirmButtonText: 'Corregir',
                confirmButtonColor: '#094c90'
            });

            return;
        }

        const accion =
            configuracion.modo === 'editar'
                ? 'actualizar'
                : 'registrar';

        Swal.fire({
            icon: 'question',
            title:
                accion === 'actualizar'
                    ? '¿Guardar los cambios?'
                    : '¿Registrar este cliente?',
            text:
                accion === 'actualizar'
                    ? 'Se actualizará la información del cliente.'
                    : 'El cliente quedará disponible para gestionar reservas.',
            showCancelButton: true,
            confirmButtonText:
                accion === 'actualizar'
                    ? 'Sí, guardar'
                    : 'Sí, registrar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#094c90',
            cancelButtonColor: '#6C7780',
            reverseButtons: true
        }).then(function (resultado) {
            if (!resultado.isConfirmed) {
                return;
            }

            enviando = true;

            const $boton = $formulario.find(
                'button[type="submit"]'
            );

            $boton
                .prop('disabled', true)
                .addClass('cargando');

            $boton.find('span').text(
                accion === 'actualizar'
                    ? 'Guardando cambios...'
                    : 'Registrando cliente...'
            );

            $boton.find('i')
                .removeClass()
                .addClass('bi bi-arrow-repeat');

            $formulario[0].submit();
        });
    });

    function mostrarErroresServidor() {
        const errores = configuracion.errores || {};
        const mensajes = [];

        Object.keys(errores).forEach(function (campo) {
            const mensaje = errores[campo][0];

            mostrarError(campo, mensaje);
            mensajes.push(mensaje);
        });

        if (mensajes.length) {
            Swal.fire({
                icon: 'error',
                title: 'Revisa la información ingresada',
                text: mensajes.join('\n'),
                confirmButtonText: 'Corregir',
                confirmButtonColor: '#094c90'
            });
        } else if (configuracion.mensajeError) {
            Swal.fire({
                icon: 'error',
                title: 'No se pudo completar la acción',
                text: configuracion.mensajeError,
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#094c90'
            });
        }
    }

    configurarDocumento();
    mostrarErroresServidor();
});