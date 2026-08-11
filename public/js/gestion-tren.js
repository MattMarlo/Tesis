$(function () {
    'use strict';

    const $formularios = $('.formulario-gestion-contextual[data-validacion-tren="true"]');

    function mostrarError($campo, mensaje) {
        if (!$campo.length) {
            return !mensaje;
        }

        const base = $campo.attr('id') || (
            $campo.closest('form').attr('id') + '-' +
            String($campo.attr('name') || 'campo').replace(/[^a-z0-9]+/gi, '-')
        );
        const id = base + 'ErrorTren';
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

    function validar($formulario) {
        const campo = nombre => $formulario.find(`[name="${nombre}"]`);
        const texto = nombre => $.trim(campo(nombre).val());
        const inicio = texto('fecha_hora_inicio');
        const fin = texto('fecha_hora_fin');
        const inicioPaquete = String($formulario.data('fecha-paquete-inicio') || '');
        const finPaquete = String($formulario.data('fecha-paquete-fin') || '');
        const origen = texto('ubicacion_origen');
        const destino = texto('destino');
        const estado = texto('estado');
        const cantidad = Number(texto('cantidad_viajeros'));
        const costo = texto('costo_total');
        const archivo = campo('archivo_comprobante').get(0)?.files?.[0];
        const formatoNombre = /^(?=(?:.*\p{L}){2})[\p{L}\p{N}\s.&'’(),/-]+$/u;
        let valido = true;
        let errorFechas = '';
        let errorArchivo = '';

        campo('fecha_hora_inicio').attr({ min: inicioPaquete ? inicioPaquete + 'T00:00' : '', max: finPaquete ? finPaquete + 'T23:59' : '' });
        campo('fecha_hora_fin').attr({ min: inicio || (inicioPaquete ? inicioPaquete + 'T00:00' : ''), max: finPaquete ? finPaquete + 'T23:59' : '' });

        if (inicio && fin && fin <= inicio) {
            errorFechas = 'La llegada debe ser posterior a la salida.';
        } else if (inicioPaquete && inicio && inicio < inicioPaquete + 'T00:00') {
            errorFechas = 'La salida del tren no puede ser anterior al inicio del paquete.';
        } else if (finPaquete && ((inicio && inicio > finPaquete + 'T23:59') || (fin && fin > finPaquete + 'T23:59'))) {
            errorFechas = 'El trayecto no puede superar la fecha de regreso del paquete.';
        }

        valido = mostrarError(campo('nombre'), formatoNombre.test(texto('nombre')) ? '' : 'Ingresa un nombre válido para el trayecto.') && valido;
        valido = mostrarError(campo('proveedor'), formatoNombre.test(texto('proveedor')) ? '' : 'Ingresa un proveedor válido.') && valido;
        valido = mostrarError(campo('contacto'), !texto('contacto') || /^[\p{L}][\p{L}\s.'’-]{2,}$/u.test(texto('contacto')) ? '' : 'Ingresa una persona de contacto válida.') && valido;
        valido = mostrarError(campo('telefono'), !texto('telefono') || /^\+?[0-9\s()\-]{7,20}$/.test(texto('telefono')) ? '' : 'Ingresa un teléfono válido.') && valido;
        valido = mostrarError(campo('correo'), !texto('correo') || /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(texto('correo')) ? '' : 'Ingresa un correo válido.') && valido;
        valido = mostrarError(campo('fecha_hora_inicio'), !inicio ? 'Selecciona la fecha y hora de salida.' : errorFechas) && valido;
        valido = mostrarError(campo('fecha_hora_fin'), !fin ? 'Selecciona la fecha y hora de llegada.' : errorFechas) && valido;
        valido = mostrarError(campo('ubicacion_origen'), origen.length >= 3 ? '' : 'Ingresa la estación o ciudad de origen.') && valido;
        valido = mostrarError(campo('destino'), destino.length < 3 ? 'Ingresa la estación o ciudad de destino.' : origen.toLocaleLowerCase() === destino.toLocaleLowerCase() ? 'El destino debe ser diferente al origen.' : '') && valido;
        valido = mostrarError(campo('datos_adicionales[empresa_ferroviaria]'), formatoNombre.test(texto('datos_adicionales[empresa_ferroviaria]')) ? '' : 'Ingresa una empresa ferroviaria válida.') && valido;
        valido = mostrarError(campo('datos_adicionales[ruta]'), texto('datos_adicionales[ruta]').length >= 3 ? '' : 'Ingresa la ruta ferroviaria.') && valido;
        valido = mostrarError(campo('datos_adicionales[clase]'), !texto('datos_adicionales[clase]') || /^[\p{L}][\p{L}\p{N}\s.'’/-]+$/u.test(texto('datos_adicionales[clase]')) ? '' : 'Ingresa una clase válida.') && valido;
        valido = mostrarError(campo('cantidad_viajeros'), Number.isInteger(cantidad) && cantidad >= 1 ? '' : 'La cantidad de viajeros debe ser mayor que cero.') && valido;
        valido = mostrarError(campo('referencia_confirmacion'), estado !== 'confirmado' || texto('referencia_confirmacion').length >= 3 ? '' : 'Una reserva confirmada requiere una referencia.') && valido;
        valido = mostrarError(campo('costo_total'), costo === '' || (Number.isFinite(Number(costo)) && Number(costo) >= 0) ? '' : 'El costo debe ser igual o mayor que cero.') && valido;

        if (archivo) {
            const extension = archivo.name.split('.').pop().toLowerCase();
            if (!['pdf', 'jpg', 'jpeg', 'png', 'webp'].includes(extension)) {
                errorArchivo = 'Selecciona un comprobante PDF, JPG, JPEG, PNG o WEBP.';
            } else if (archivo.size > 5 * 1024 * 1024) {
                errorArchivo = 'El comprobante no puede superar los 5 MB.';
            }
        }
        valido = mostrarError(campo('archivo_comprobante'), errorArchivo) && valido;

        $formulario.find('.viajero-gestion-contextual').each(function () {
            const $viajero = $(this);
            const $estadoViajero = $viajero.find('[name$="[estado]"]');
            const $documento = $viajero.find('[name$="[numero_documento]"]');
            const $referencia = $viajero.find('[name$="[referencia_individual]"]');
            const $asiento = $viajero.find('[name$="[asiento]"]');
            const confirmado = $estadoViajero.val() === 'confirmado';
            const documento = $.trim($documento.val());
            const referencia = $.trim($referencia.val());
            const asiento = $.trim($asiento.val());

            valido = mostrarError($documento, confirmado && !documento && !referencia ? 'Registra el boleto o una referencia para el viajero confirmado.' : documento && !/^[A-Z0-9-]{3,150}$/i.test(documento) ? 'Usa al menos tres letras, números o guiones.' : '') && valido;
            valido = mostrarError($referencia, referencia && !/^[A-Z0-9-]{3,150}$/i.test(referencia) ? 'Usa al menos tres letras, números o guiones.' : '') && valido;
            valido = mostrarError($asiento, asiento && !/^[0-9]{1,3}[A-Z]$/i.test(asiento) ? 'Ingresa un asiento válido, por ejemplo 12A.' : '') && valido;
        });

        return valido;
    }

    $formularios.each(function () {
        const $formulario = $(this);

        $formulario.on('input change blur', 'input, select, textarea', function () {
            if (String(this.name).endsWith('[asiento]')) {
                this.value = this.value.toUpperCase();
            }
            validar($formulario);
        });

        $formulario.on('submit', function (evento) {
            if (validar($formulario)) {
                return;
            }
            evento.preventDefault();
            $formulario.find('.input-error').first().trigger('focus');
        });

        if (String($formulario.data('reabrir-validacion')) === 'true') {
            bootstrap.Modal.getOrCreateInstance(
                $formulario.closest('.modal').get(0)
            ).show();
            validar($formulario);
        }
    });
});
