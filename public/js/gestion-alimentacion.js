$(function () {
    'use strict';

    const $formularios = $('.formulario-gestion-contextual[data-validacion-alimentacion="true"]');

    function mostrarError($campo, mensaje) {
        const baseId = $campo.attr('id') || (
            $campo.closest('form').attr('id') + '-' +
            String($campo.attr('name') || 'campo').replace(/[^a-z0-9]+/gi, '-')
        );
        const id = baseId + 'ErrorAlimentacion';
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

    function validarFormulario($formulario) {
        const campo = nombre => $formulario.find(`[name="${nombre}"]`);
        const $nombre = campo('nombre');
        const $proveedor = campo('proveedor');
        const $contacto = campo('contacto');
        const $telefono = campo('telefono');
        const $correo = campo('correo');
        const $inicio = campo('fecha_hora_inicio');
        const $fin = campo('fecha_hora_fin');
        const $origen = campo('ubicacion_origen');
        const $restaurante = campo('datos_adicionales[restaurante]');
        const $tipoMenu = campo('datos_adicionales[tipo_menu]');
        const $restricciones = campo('datos_adicionales[restricciones_alimentarias]');
        const $cantidad = campo('cantidad_viajeros');
        const $estado = campo('estado');
        const $referencia = campo('referencia_confirmacion');
        const $costo = campo('costo_total');
        const $archivo = campo('archivo_comprobante');
        const $observaciones = campo('observaciones');
        const inicioPaquete = String($formulario.data('fecha-paquete-inicio') || '');
        const finPaquete = String($formulario.data('fecha-paquete-fin') || '');
        const inicio = $inicio.val();
        const fin = $fin.val();
        const formatoNombre = /^(?=(?:.*\p{L}){2})[\p{L}\p{N}\s.&'’(),/-]+$/u;
        const cantidad = Number($cantidad.val());
        const costo = $costo.val();
        const archivo = $archivo.get(0)?.files?.[0];
        let valido = true;
        let errorFechas = '';
        let errorArchivo = '';

        $inicio.attr({
            min: inicioPaquete ? inicioPaquete + 'T00:00' : '',
            max: finPaquete ? finPaquete + 'T23:59' : ''
        });
        $fin.attr({
            min: inicio || (inicioPaquete ? inicioPaquete + 'T00:00' : ''),
            max: finPaquete ? finPaquete + 'T23:59' : ''
        });

        if (inicio && fin && fin <= inicio) {
            errorFechas = 'La fecha y hora de finalización debe ser posterior al inicio.';
        } else if (inicioPaquete && inicio && inicio < inicioPaquete + 'T00:00') {
            errorFechas = 'El inicio no puede ser anterior a la salida del paquete.';
        } else if (finPaquete && ((inicio && inicio > finPaquete + 'T23:59') || (fin && fin > finPaquete + 'T23:59'))) {
            errorFechas = 'La programación no puede superar la fecha de regreso del paquete.';
        }

        valido = mostrarError(
            $nombre,
            formatoNombre.test($.trim($nombre.val())) ? '' : 'Ingresa un nombre válido de al menos dos letras.'
        ) && valido;
        valido = mostrarError(
            $proveedor,
            formatoNombre.test($.trim($proveedor.val())) ? '' : 'Ingresa un proveedor válido de al menos dos letras.'
        ) && valido;
        valido = mostrarError(
            $contacto,
            !$contacto.val() || formatoNombre.test($.trim($contacto.val())) ? '' : 'Ingresa un nombre de contacto válido.'
        ) && valido;
        valido = mostrarError(
            $telefono,
            !$telefono.val() || /^\+?[0-9\s()-]{7,20}$/.test($.trim($telefono.val())) ? '' : 'Ingresa un teléfono válido de 7 a 20 caracteres.'
        ) && valido;
        valido = mostrarError(
            $correo,
            !$correo.val() || /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test($.trim($correo.val())) ? '' : 'Ingresa un correo electrónico válido.'
        ) && valido;
        valido = mostrarError($inicio, !inicio ? 'Selecciona la fecha y hora de inicio.' : errorFechas) && valido;
        valido = mostrarError($fin, errorFechas) && valido;
        valido = mostrarError(
            $origen,
            !$origen.val() || $.trim($origen.val()).length >= 3 ? '' : 'La ubicación debe tener al menos tres caracteres.'
        ) && valido;
        valido = mostrarError(
            $restaurante,
            !$restaurante.val() || $.trim($restaurante.val()).length >= 3 ? '' : 'El establecimiento debe tener al menos tres caracteres.'
        ) && valido;
        valido = mostrarError(
            $tipoMenu,
            !$tipoMenu.val() || formatoNombre.test($.trim($tipoMenu.val())) ? '' : 'Ingresa un tipo de menú válido.'
        ) && valido;
        valido = mostrarError(
            $restricciones,
            !$restricciones.val() || $.trim($restricciones.val()).length >= 3 ? '' : 'Describe la restricción con al menos tres caracteres.'
        ) && valido;
        valido = mostrarError(
            $cantidad,
            Number.isInteger(cantidad) && cantidad >= 1
                ? ''
                : 'La cantidad de viajeros debe ser un número entero mayor que cero.'
        ) && valido;
        valido = mostrarError(
            $referencia,
            $estado.val() !== 'confirmado' || $.trim($referencia.val()).length >= 3
                ? ''
                : 'Una gestión confirmada requiere una referencia de al menos tres caracteres.'
        ) && valido;
        valido = mostrarError(
            $costo,
            costo === '' || (Number.isFinite(Number(costo)) && Number(costo) >= 0)
                ? ''
                : 'El costo debe ser un número igual o mayor que cero.'
        ) && valido;

        if (archivo) {
            const extension = archivo.name.split('.').pop().toLowerCase();

            if (!['pdf', 'jpg', 'jpeg', 'png', 'webp'].includes(extension)) {
                errorArchivo = 'Selecciona un comprobante PDF, JPG, JPEG, PNG o WEBP.';
            } else if (archivo.size > 5 * 1024 * 1024) {
                errorArchivo = 'El comprobante no puede superar los 5 MB.';
            }
        }

        valido = mostrarError($archivo, errorArchivo) && valido;
        valido = mostrarError(
            $observaciones,
            !$observaciones.val() || $.trim($observaciones.val()).length >= 3
                ? ''
                : 'Las observaciones deben tener al menos tres caracteres.'
        ) && valido;

        return valido;
    }

    $formularios.each(function () {
        const $formulario = $(this);

        validarFormulario($formulario);

        $formulario.on('input change blur', 'input, select, textarea', function () {
            validarFormulario($formulario);
        });

        $formulario.on('submit', function (evento) {
            if (validarFormulario($formulario)) {
                return;
            }

            evento.preventDefault();
            evento.stopImmediatePropagation();
            $formulario.find('.input-error').first().trigger('focus');
        });
    });
});
