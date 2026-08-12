document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const modal = document.getElementById('modalPasajeTren');
    const formulario = document.getElementById('formPasajeTren');

    if (!modal || !formulario) {
        return;
    }

    function campo(nombre) {
        return formulario.querySelector(`[name="${nombre}"]`);
    }

    function mostrarError(elemento, mensaje) {
        const id = String(elemento.id) + 'Error';
        let error = document.getElementById(id);

        elemento.classList.toggle('input-error', Boolean(mensaje));
        elemento.setAttribute(
            'aria-invalid',
            mensaje ? 'true' : 'false'
        );

        if (!error) {
            error = document.createElement('small');
            error.id = id;
            error.className = 'mensaje-error-pasaje';
            error.setAttribute('role', 'alert');
            elemento.insertAdjacentElement('afterend', error);
        }

        error.textContent = mensaje;
        error.hidden = !mensaje;

        return !mensaje;
    }

    function validar() {
        const estado = campo('estado').value;
        const numero = campo('numero_documento').value.trim();
        const asiento = campo('asiento').value.trim().toUpperCase();
        const referencia = campo('referencia_individual').value.trim();
        const restricciones = campo('restricciones').value.trim();
        const observaciones = campo('observaciones').value.trim();
        let valido = true;

        campo('asiento').value = asiento;

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

    document.querySelectorAll('.btn-gestionar-pasaje')
        .forEach(function (boton) {
            boton.addEventListener('click', function () {
                campo('pasaje_id').value = boton.dataset.id || '';
                campo('numero_documento').value =
                    boton.dataset.documento || '';
                campo('asiento').value = boton.dataset.asiento || '';
                campo('referencia_individual').value =
                    boton.dataset.referencia || '';
                campo('estado').value =
                    boton.dataset.estado || 'pendiente';
                campo('restricciones').value =
                    boton.dataset.restricciones || '';
                campo('observaciones').value =
                    boton.dataset.observaciones || '';

                document.getElementById('nombreViajeroPasaje')
                    .textContent = boton.dataset.nombre || 'Viajero';

                validar();
            });
        });

    formulario.querySelectorAll('input, select, textarea')
        .forEach(function (elemento) {
            ['input', 'change', 'blur'].forEach(function (evento) {
                elemento.addEventListener(evento, validar);
            });
        });

    formulario.addEventListener('submit', function (evento) {
        if (validar()) {
            return;
        }

        evento.preventDefault();
        formulario.querySelector('.input-error')?.focus();
    });

    if (modal.dataset.reabrir === 'true') {
        const datos = window.pasajeTrenConError;

        document.getElementById('nombreViajeroPasaje').textContent =
            datos?.nombre || 'Viajero';

        bootstrap.Modal.getOrCreateInstance(modal).show();
        validar();
    }
});
