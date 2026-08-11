$(function () {
    'use strict';

    const $formulario = $('#formularioCrearHabitacion');

    if (!$formulario.length) {
        return;
    }

    function mostrarError($campo, mensaje) {
        const id = $campo.attr('id') + 'ErrorCliente';
        let $mensaje = $('#' + id);

        if (!$mensaje.length) {
            $mensaje = $('<small>', {
                id,
                class: 'campo-error-habitacion campo-error-habitacion-cliente',
                role: 'alert',
                hidden: true
            }).insertAfter($campo);
        }

        $campo.toggleClass('input-error', Boolean(mensaje));
        $campo.attr('aria-invalid', mensaje ? 'true' : 'false');
        $mensaje.text(mensaje).prop('hidden', !mensaje);

        if (!mensaje) {
            $campo.nextAll('.campo-error-habitacion:not(.campo-error-habitacion-cliente)').first().remove();
        }

        return !mensaje;
    }

    function validar() {
        const tipo = String($('#habitacionTipo').val() || '');
        const referencia = $.trim($('#habitacionReferencia').val());
        const observaciones = $.trim($('#habitacionObservaciones').val());
        const tipos = ['individual', 'matrimonial', 'doble', 'triple', 'cuadruple', 'quintuple'];
        const referenciaValida = /^(?:[0-9]{1,4}|(?=.{2,100}$)[\p{L}\p{N}][\p{L}\p{N}\s._-]*)$/u;
        let valido = true;

        valido = mostrarError(
            $('#habitacionTipo'),
            tipos.includes(tipo) ? '' : 'Selecciona un tipo de habitación válido.'
        ) && valido;
        valido = mostrarError(
            $('#habitacionReferencia'),
            referencia && referenciaValida.test(referencia)
                ? ''
                : 'Usa un número o un nombre de al menos dos caracteres.'
        ) && valido;
        valido = mostrarError(
            $('#habitacionObservaciones'),
            !observaciones || observaciones.length >= 3
                ? ''
                : 'Las camas y observaciones deben tener al menos tres caracteres.'
        ) && valido;

        return valido;
    }

    $formulario.on('input change blur', 'input, select, textarea', validar);

    $formulario.on('submit', function (evento) {
        if (validar()) {
            return;
        }

        evento.preventDefault();
        $formulario.find('.input-error').first().trigger('focus');
    });
});
