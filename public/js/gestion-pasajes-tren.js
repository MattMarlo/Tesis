$(function () {
    'use strict';

    const $modal = $('#modalPasajeTren');
    const $formulario = $('#formPasajeTren');

    if (!$modal.length || !$formulario.length) {
        return;
    }

    function campo(nombre) {
        return $formulario.find(`[name="${nombre}"]`);
    }

    function mostrarError($campo, mensaje) {
        const id = String($campo.attr('id')) + 'Error';
        let $error = $('#' + id);

        $campo
            .toggleClass('input-error', Boolean(mensaje))
            .attr('aria-invalid', mensaje ? 'true' : 'false');

        if (!$error.length) {
            $error = $('<small>', {
                id,
                class: 'mensaje-error-pasaje',
                role: 'alert'
            }).insertAfter($campo);
        }

        $error.text(mensaje).prop('hidden', !mensaje);

        return !mensaje;
    }

    function validar() {
        const estado = campo('estado').val();
        const numero = $.trim(campo('numero_documento').val());
        const asiento = $.trim(campo('asiento').val()).toUpperCase();
        const referencia = $.trim(campo('referencia_individual').val());
        const restricciones = $.trim(campo('restricciones').val());
        const observaciones = $.trim(campo('observaciones').val());
        let valido = true;

        campo('asiento').val(asiento);

        valido = mostrarError(
            campo('numero_documento'),
            estado === 'confirmado' && !numero
                ? 'Ingresa el número del pasaje confirmado.'
                : numero && !/^[A-Z0-9-]{3,150}$/i.test(numero)
                    ? 'Usa al menos tres letras, números o guiones.'
                    : ''
        ) && valido;

        valido = mostrarError(
            campo('asiento'),
            asiento && !/^[0-9]{1,3}[A-Z]$/i.test(asiento)
                ? 'Ingresa un asiento válido, por ejemplo 12A.'
                : ''
        ) && valido;

        valido = mostrarError(
            campo('referencia_individual'),
            referencia && !/^[A-Z0-9-]{3,150}$/i.test(referencia)
                ? 'Usa al menos tres letras, números o guiones.'
                : ''
        ) && valido;

        valido = mostrarError(
            campo('restricciones'),
            restricciones && restricciones.length < 3
                ? 'Ingresa al menos tres caracteres.'
                : ''
        ) && valido;

        valido = mostrarError(
            campo('observaciones'),
            observaciones && observaciones.length < 3
                ? 'Ingresa al menos tres caracteres.'
                : ''
        ) && valido;

        return valido;
    }

    $('.btn-gestionar-pasaje').on('click', function () {
        const $boton = $(this);

        campo('pasaje_id').val($boton.data('id'));
        campo('numero_documento').val($boton.data('documento') || '');
        campo('asiento').val($boton.data('asiento') || '');
        campo('referencia_individual').val($boton.data('referencia') || '');
        campo('estado').val($boton.data('estado') || 'pendiente');
        campo('restricciones').val($boton.data('restricciones') || '');
        campo('observaciones').val($boton.data('observaciones') || '');
        $('#nombreViajeroPasaje').text($boton.data('nombre') || 'Viajero');
        validar();
    });

    $formulario.on(
        'input change blur',
        'input, select, textarea',
        validar
    );

    $formulario.on('submit', function (evento) {
        if (validar()) {
            return;
        }

        evento.preventDefault();
        $formulario.find('.input-error').first().trigger('focus');
    });

    if ($modal.data('reabrir') === true || $modal.data('reabrir') === 'true') {
        const datos = window.pasajeTrenConError;
        $('#nombreViajeroPasaje').text(datos?.nombre || 'Viajero');
        bootstrap.Modal.getOrCreateInstance($modal.get(0)).show();
        validar();
    }
});
