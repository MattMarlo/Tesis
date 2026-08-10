$(function () {
    'use strict';

    const $formulario = $('#formularioAlojamiento');

    if (!$formulario.length) {
        return;
    }

    function mostrarError(selector, mensaje) {
        const $campo = $(selector);
        const id = $campo.attr('id') + 'ErrorHospedaje';
        let $mensaje = $('#' + id);

        if (!$mensaje.length) {
            $mensaje = $('<small>', {
                id,
                class: 'mensaje-validacion-vuelo',
                role: 'alert',
                hidden: true
            }).insertAfter($campo);
        }

        $campo
            .toggleClass('input-error', Boolean(mensaje))
            .attr('aria-invalid', mensaje ? 'true' : 'false');
        $mensaje.text(mensaje).prop('hidden', !mensaje);

        return !mensaje;
    }

    window.validarFormularioHospedaje = function (enfocar = false) {
        const valor = selector => $.trim($(selector).val());
        const entrada = valor('#alojamientoEntrada');
        const salida = valor('#alojamientoSalida');
        const inicioPaquete = String($formulario.data('fecha-paquete-inicio') || '');
        const finPaquete = String($formulario.data('fecha-paquete-fin') || '');
        const estado = valor('#alojamientoEstado');
        const cantidad = Number(valor('#alojamientoCantidad'));
        const costo = valor('#alojamientoCosto');
        const fechaCompra = valor('#alojamientoFechaCompra');
        const hoy = new Date();
        const haceUnAno = new Date(hoy.getFullYear() - 1, hoy.getMonth(), hoy.getDate());
        const fechaLocal = fecha => [fecha.getFullYear(), String(fecha.getMonth() + 1).padStart(2, '0'), String(fecha.getDate()).padStart(2, '0')].join('-');
        const minimoCompra = fechaLocal(haceUnAno);
        const maximoCompra = fechaLocal(hoy);
        const formatoNombre = /^(?=(?:.*\p{L}){2})[\p{L}\p{N}\s.&'’(),/-]+$/u;
        const formatoLugar = /^[\p{L}][\p{L}\s.'’,\-]+$/u;
        let valido = true;
        let errorFechas = '';

        $('#alojamientoEntrada').attr({ min: inicioPaquete ? inicioPaquete + 'T00:00' : '', max: finPaquete ? finPaquete + 'T23:59' : '' });
        $('#alojamientoSalida').attr({ min: entrada || (inicioPaquete ? inicioPaquete + 'T00:00' : ''), max: finPaquete ? finPaquete + 'T23:59' : '' });
        $('#alojamientoFechaCompra').attr({ min: minimoCompra, max: maximoCompra });

        if (entrada && salida && salida <= entrada) {
            errorFechas = 'La salida debe ser posterior a la entrada.';
        } else if (inicioPaquete && entrada && entrada < inicioPaquete + 'T00:00') {
            errorFechas = 'La entrada no puede ser anterior al inicio del paquete.';
        } else if (finPaquete && ((entrada && entrada > finPaquete + 'T23:59') || (salida && salida > finPaquete + 'T23:59'))) {
            errorFechas = 'Las fechas no pueden superar el regreso del paquete.';
        }

        valido = mostrarError('#alojamientoHotel', formatoNombre.test(valor('#alojamientoHotel')) ? '' : 'Ingresa un nombre de hotel válido.') && valido;
        valido = mostrarError('#alojamientoCiudad', formatoLugar.test(valor('#alojamientoCiudad')) ? '' : 'Ingresa una ciudad válida.') && valido;
        valido = mostrarError('#alojamientoPais', formatoLugar.test(valor('#alojamientoPais')) ? '' : 'Ingresa un país válido.') && valido;
        valido = mostrarError('#alojamientoDireccion', !valor('#alojamientoDireccion') || valor('#alojamientoDireccion').length >= 5 ? '' : 'La dirección debe tener al menos cinco caracteres.') && valido;
        valido = mostrarError('#alojamientoEntrada', !entrada ? 'Ingresa la fecha y hora de entrada.' : errorFechas) && valido;
        valido = mostrarError('#alojamientoSalida', !salida ? 'Ingresa la fecha y hora de salida.' : errorFechas) && valido;
        valido = mostrarError('#alojamientoConfirmacion', estado !== 'confirmado' || (valor('#alojamientoConfirmacion').length >= 3 && /^[A-Z0-9]+(?:-[A-Z0-9]+)*$/i.test(valor('#alojamientoConfirmacion'))) ? '' : 'Ingresa un código válido de al menos tres caracteres.') && valido;
        valido = mostrarError('#alojamientoTipoHabitacion', formatoNombre.test(valor('#alojamientoTipoHabitacion')) ? '' : 'Ingresa un tipo de habitación válido.') && valido;
        valido = mostrarError('#alojamientoCantidad', Number.isInteger(cantidad) && cantidad >= 1 && cantidad <= 100 ? '' : 'Ingresa entre 1 y 100 habitaciones.') && valido;
        valido = mostrarError('#alojamientoAlimentacion', !valor('#alojamientoAlimentacion') || valor('#alojamientoAlimentacion').length >= 3 ? '' : 'Describe la alimentación con al menos tres caracteres.') && valido;
        valido = mostrarError('#alojamientoDistribucion', !valor('#alojamientoDistribucion') || valor('#alojamientoDistribucion').length >= 3 ? '' : 'Describe la distribución con al menos tres caracteres.') && valido;
        valido = mostrarError('#alojamientoTelefono', !valor('#alojamientoTelefono') || /^\+?[0-9\s()\-]{7,20}$/.test(valor('#alojamientoTelefono')) ? '' : 'Ingresa un teléfono válido.') && valido;
        valido = mostrarError('#alojamientoCorreo', !valor('#alojamientoCorreo') || /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(valor('#alojamientoCorreo')) ? '' : 'Ingresa un correo válido.') && valido;
        valido = mostrarError('#alojamientoProveedor', !valor('#alojamientoProveedor') || formatoNombre.test(valor('#alojamientoProveedor')) ? '' : 'Ingresa un proveedor válido.') && valido;
        valido = mostrarError('#alojamientoFechaCompra', !fechaCompra || (fechaCompra >= minimoCompra && fechaCompra <= maximoCompra) ? '' : 'La compra debe estar entre hoy y un año atrás.') && valido;
        valido = mostrarError('#alojamientoCosto', costo === '' || (Number.isFinite(Number(costo)) && Number(costo) >= 0) ? '' : 'El costo debe ser igual o mayor que cero.') && valido;
        valido = mostrarError('#alojamientoMoneda', ['USD', 'EUR', 'PEN'].includes(valor('#alojamientoMoneda').toUpperCase()) ? '' : 'Usa USD, EUR o PEN.') && valido;
        valido = mostrarError('#alojamientoObservaciones', !valor('#alojamientoObservaciones') || valor('#alojamientoObservaciones').length >= 3 ? '' : 'Las observaciones deben tener al menos tres caracteres.') && valido;

        if (!valido && enfocar) {
            $formulario.find('.input-error').first().trigger('focus');
        }

        return valido;
    };

    $formulario.on('input change blur', 'input, select, textarea', function () {
        if (this.id === 'alojamientoMoneda') {
            this.value = this.value.toUpperCase().replace(/[^A-Z]/g, '').slice(0, 3);
        }

        window.validarFormularioHospedaje(false);
    });

    $('#modalAlojamiento').on('shown.bs.modal', function () {
        const entrada = $('#alojamientoEntrada').val();
        $('#alojamientoSalida').attr('min', entrada || '');
    });
});
