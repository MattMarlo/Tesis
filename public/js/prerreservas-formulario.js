$(function () {
    const formulario = $('#formPrerreserva').length
        ? $('#formPrerreserva')
        : $('form[action*="prereservas"]').first();

    const nombre = $('[name="cliente_nombre"]');
    const correo = $('[name="email"]');
    const telefono = $('[name="telefono"]');
    const cedula = $('[name="cedula"]');
    const destino = $('[name="destino_id"]');
    const fechaViaje = $('[name="fecha_viaje"]');
    const cantidad = $('[name="cantidad_personas"]');
    const estado = $('[name="estado"]');

    let envioConfirmado = false;

    function mostrarError(campo, mensaje) {
        if (!campo.length) {
            return !mensaje;
        }

        const nombreCampo = campo.attr('name');
        let contenedor = $(
            '[data-error-for="' + nombreCampo + '"]'
        );

        if (!contenedor.length) {
            contenedor = $('#' + nombreCampo + 'Error');
        }

        campo.toggleClass('is-invalid', Boolean(mensaje));

        contenedor
            .text(mensaje || '')
            .toggle(Boolean(mensaje));

        return !mensaje;
    }

    function validarNombre() {
        const valor = $.trim(nombre.val());

        if (!valor) {
            return mostrarError(
                nombre,
                'Ingresa el nombre completo del cliente.'
            );
        }

        if (valor.length < 5) {
            return mostrarError(
                nombre,
                'Escribe el nombre completo del cliente.'
            );
        }

        if (
            !/^[a-záéíóúüñ\s.'-]+$/i.test(valor)
        ) {
            return mostrarError(
                nombre,
                'El nombre solo puede contener letras.'
            );
        }

        return mostrarError(nombre, '');
    }

    function validarCorreo() {
        const valor = $.trim(correo.val());

        if (!valor) {
            return mostrarError(
                correo,
                'Ingresa el correo electrónico del cliente.'
            );
        }

        const formatoCorreo =
            /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (!formatoCorreo.test(valor)) {
            return mostrarError(
                correo,
                'Escribe un correo electrónico válido.'
            );
        }

        return mostrarError(correo, '');
    }

    function validarTelefono() {
        const valor = telefono
            .val()
            .replace(/\D/g, '');

        if (!valor) {
            return mostrarError(
                telefono,
                'Ingresa el número de teléfono.'
            );
        }

        if (!/^09\d{8}$/.test(valor)) {
            return mostrarError(
                telefono,
                'Ingresa un celular ecuatoriano válido de 10 dígitos.'
            );
        }

        return mostrarError(telefono, '');
    }

    function validarCedulaEcuatoriana(valor) {
        if (!/^\d{10}$/.test(valor)) {
            return false;
        }

        const provincia = Number(
            valor.substring(0, 2)
        );

        if (
            provincia < 1 ||
            provincia > 24 ||
            Number(valor.charAt(2)) >= 6
        ) {
            return false;
        }

        const coeficientes = [
            2, 1, 2, 1, 2,
            1, 2, 1, 2
        ];

        let suma = 0;

        for (let i = 0; i < 9; i++) {
            let resultado =
                Number(valor.charAt(i)) *
                coeficientes[i];

            if (resultado >= 10) {
                resultado -= 9;
            }

            suma += resultado;
        }

        const digitoVerificador =
            (10 - (suma % 10)) % 10;

        return digitoVerificador ===
            Number(valor.charAt(9));
    }

    function validarCedula() {
        const valor = cedula
            .val()
            .replace(/\D/g, '');

        // La cédula es opcional en la prerreserva.
        if (!valor) {
            return mostrarError(cedula, '');
        }

        if (!validarCedulaEcuatoriana(valor)) {
            return mostrarError(
                cedula,
                'La cédula ecuatoriana ingresada no es válida.'
            );
        }

        return mostrarError(cedula, '');
    }

    function validarDestino() {
        if (!destino.val()) {
            return mostrarError(
                destino,
                'Selecciona el paquete turístico solicitado.'
            );
        }

        return mostrarError(destino, '');
    }

    function validarFechaViaje() {
        const valor = fechaViaje.val();

        if (!valor) {
            return mostrarError(
                fechaViaje,
                'Selecciona la fecha prevista del viaje.'
            );
        }

        const seleccionada = new Date(
            valor + 'T00:00:00'
        );

        const hoy = new Date();
        hoy.setHours(0, 0, 0, 0);

        if (seleccionada < hoy) {
            return mostrarError(
                fechaViaje,
                'La fecha del viaje no puede ser anterior a hoy.'
            );
        }

        return mostrarError(fechaViaje, '');
    }

    function validarCantidad() {
        const valor = Number(cantidad.val());

        if (
            !Number.isInteger(valor) ||
            valor < 1 ||
            valor > 100
        ) {
            return mostrarError(
                cantidad,
                'La cantidad debe estar entre 1 y 100 viajeros.'
            );
        }

        return mostrarError(cantidad, '');
    }

    function validarEstado() {
        const estadosPermitidos = [
            'pendiente_contacto',
            'contactado',
            'perdida'
        ];

        if (
            !estado.val() ||
            !estadosPermitidos.includes(estado.val())
        ) {
            return mostrarError(
                estado,
                'Selecciona un estado válido.'
            );
        }

        return mostrarError(estado, '');
    }

    function validarFormulario() {
        const resultados = [
            validarNombre(),
            validarCorreo(),
            validarTelefono(),
            validarCedula(),
            validarDestino(),
            validarFechaViaje(),
            validarCantidad(),
            validarEstado()
        ];

        return resultados.every(Boolean);
    }

    nombre.on('input blur', validarNombre);
    correo.on('input blur', validarCorreo);
    telefono.on('input blur', validarTelefono);
    cedula.on('input blur', validarCedula);
    destino.on('change blur', validarDestino);
    fechaViaje.on('change blur', validarFechaViaje);
    cantidad.on('input change blur', validarCantidad);
    estado.on('change blur', validarEstado);

    telefono.on('input', function () {
        this.value = this.value
            .replace(/\D/g, '')
            .slice(0, 10);
    });

    cedula.on('input', function () {
        this.value = this.value
            .replace(/\D/g, '')
            .slice(0, 10);
    });

    cantidad.on('input', function () {
        this.value = this.value
            .replace(/\D/g, '')
            .slice(0, 3);
    });

    nombre.on('input', function () {
        this.value = this.value
            .replace(
                /[^a-záéíóúüñ\s.'-]/gi,
                ''
            );
    });

    formulario.on('submit', function (event) {
        if (envioConfirmado) {
            return;
        }

        event.preventDefault();

        if (!validarFormulario()) {
            const primerCampo =
                formulario.find('.is-invalid').first();

            if (primerCampo.length) {
                primerCampo.trigger('focus');

                $('html, body').animate(
                    {
                        scrollTop:
                            primerCampo.offset().top - 140
                    },
                    300
                );
            }

            Swal.fire({
                icon: 'error',
                title: 'Revisa la información ingresada',
                text: 'Corrige los campos señalados antes de continuar.',
                confirmButtonText: 'Corregir',
                confirmButtonColor: '#094c90'
            });

            return;
        }

        const seraDescartada =
            estado.val() === 'perdida';

        Swal.fire({
            icon: seraDescartada
                ? 'warning'
                : 'question',
            title: seraDescartada
                ? '¿Descartar esta prerreserva?'
                : '¿Guardar los cambios?',
            text: seraDescartada
                ? 'La solicitud quedará registrada como descartada y ya no podrá convertirse en una reserva.'
                : 'Se actualizará la información de la prerreserva.',
            showCancelButton: true,
            confirmButtonText: seraDescartada
                ? 'Sí, descartar'
                : 'Sí, guardar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: seraDescartada
                ? '#90091d'
                : '#094c90',
            cancelButtonColor: '#65717E',
            reverseButtons: true
        }).then(function (resultado) {
            if (!resultado.isConfirmed) {
                return;
            }

            envioConfirmado = true;

            const boton = formulario.find(
                'button[type="submit"]'
            );

            boton
                .prop('disabled', true)
                .html(
                    '<span class="spinner-border ' +
                    'spinner-border-sm me-2"></span>' +
                    'Guardando...'
                );

            formulario.trigger('submit');
        });
    });

    const configuracion =
        window.configuracionPrerreserva || {};

    if (
        Array.isArray(configuracion.errores) &&
        configuracion.errores.length
    ) {
        Swal.fire({
            icon: 'error',
            title: 'Revisa la información ingresada',
            html: configuracion.errores
                .map(function (mensaje) {
                    return $('<div>')
                        .text(mensaje)
                        .html();
                })
                .join('<br>'),
            confirmButtonText: 'Corregir',
            confirmButtonColor: '#094c90'
        });
    }

    if (configuracion.mensajeExito) {
        Swal.fire({
            icon: 'success',
            title: 'Cambios guardados',
            text: configuracion.mensajeExito,
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#094c90'
        });
    }
});