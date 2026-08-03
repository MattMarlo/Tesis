$(function () {
    const $formulario =
        $('#formularioReservaIndividual');

    const $cliente = $('#cliente_id');
    const $destino = $('#destino_id');

    const configuracion =
        window.configuracionReservaIndividual || {};
    const esEdicion = configuracion.modo === 'editar';
    let enviando = false;
    let calculoActual = null;

    function mostrarError(campo, mensaje) {
        const $control = $('#' + campo);
        const $error = $('#' + campo + 'Error');

        $control.toggleClass(
            'input-error',
            Boolean(mensaje)
        );

        $error
            .text(mensaje || '')
            .toggle(Boolean(mensaje));

        return !mensaje;
    }

    function obtenerOpcion($select) {
        return $select.find('option:selected');
    }

    function crearFecha(valor) {
        if (!valor) {
            return null;
        }

        const partes = valor.split('-').map(Number);

        if (partes.length !== 3) {
            return null;
        }

        return new Date(
            partes[0],
            partes[1] - 1,
            partes[2]
        );
    }

    function formatearFecha(valor) {
        const fecha = crearFecha(valor);

        if (!fecha) {
            return 'Sin registrar';
        }

        return new Intl.DateTimeFormat(
            'es-EC',
            {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            }
        ).format(fecha);
    }

    function formatearDinero(valor, moneda) {
        const cantidad = Number(valor || 0);
        const codigo = moneda || 'USD';

        try {
            return new Intl.NumberFormat(
                'es-EC',
                {
                    style: 'currency',
                    currency: codigo,
                    minimumFractionDigits: 2
                }
            ).format(cantidad);
        } catch (error) {
            return `${codigo} ${cantidad.toFixed(2)}`;
        }
    }

    function calcularEdad(
        fechaNacimientoTexto,
        fechaViajeTexto
    ) {
        const nacimiento =
            crearFecha(fechaNacimientoTexto);

        const viaje = crearFecha(fechaViajeTexto);

        if (!nacimiento || !viaje) {
            return null;
        }

        let edad =
            viaje.getFullYear() -
            nacimiento.getFullYear();

        const antesDelCumpleanos =
            viaje.getMonth() < nacimiento.getMonth() ||
            (
                viaje.getMonth() === nacimiento.getMonth() &&
                viaje.getDate() < nacimiento.getDate()
            );

        if (antesDelCumpleanos) {
            edad--;
        }

        return edad;
    }

    function determinarTarifa(edad) {
        if (edad < 2) {
            return {
                categoria: 'Infante',
                porcentaje: 0
            };
        }

        if (edad < 12) {
            return {
                categoria: 'Niño',
                porcentaje: 50
            };
        }

        if (edad > 60) {
            return {
                categoria: 'Adulto mayor',
                porcentaje: 50
            };
        }

        return {
            categoria: 'Adulto',
            porcentaje: 100
        };
    }

    function actualizarCliente() {
        const $opcion = obtenerOpcion($cliente);
        const id = $cliente.val();

        if (!id) {
            $('#resumenCliente').addClass('oculto');
            $('#avisoClienteIncompleto').addClass(
                'oculto'
            );

            calculoActual = null;
            actualizarCalculo();
            return;
        }

        const nombre = $opcion.data('nombre');
        const documento = $opcion.data('documento');
        const tipoDocumento =
            $opcion.data('tipo-documento');
        const completo =
            String($opcion.data('completo')) === '1';

        $('#resumenClienteNombre').text(nombre);
        $('#resumenClienteDocumento').text(
            `${tipoDocumento || 'Documento'}: ${documento}`
        );

        $('#resumenCliente').removeClass('oculto');

        $('#avisoClienteIncompleto').toggleClass(
            'oculto',
            completo
        );

        $('#editarClienteSeleccionado').attr(
            'href',
            $opcion.data('editar-url') || '#'
        );

        mostrarError(
            'cliente_id',
            completo
                ? ''
                : 'Completa la información del cliente antes de reservar.'
        );

        actualizarCalculo();
    }

    function actualizarPaquete() {
        const $opcion = obtenerOpcion($destino);
        const id = $destino.val();

        if (!id) {
            $('#resumenPaquete').addClass('oculto');
            calculoActual = null;
            actualizarCalculo();
            return;
        }

        const origen =
            $opcion.data('origen') || 'Sin origen';

        const ciudadDestino =
            $opcion.data('destino') ||
            $opcion.data('pais') ||
            'Sin destino';

        const salida =
            $opcion.data('fecha-salida');

        const regreso =
            $opcion.data('fecha-regreso');

        const precio = Number(
            $opcion.data('precio') || 0
        );

        const moneda =
            $opcion.data('moneda') || 'USD';

        const capacidad =
            $opcion.data('capacidad');

        $('#paqueteRuta').text(
            `${origen} → ${ciudadDestino}`
        );

        $('#paqueteSalida').text(
            formatearFecha(salida)
        );

        $('#paqueteRegreso').text(
            formatearFecha(regreso)
        );

        $('#paquetePrecio').text(
            formatearDinero(precio, moneda)
        );

        $('#paqueteCapacidad').text(
            capacidad
                ? `${capacidad} viajeros`
                : 'Sin configurar'
        );

        $('#resumenPaquete').removeClass('oculto');

        mostrarError(
            'destino_id',
            precio > 0 && salida
                ? ''
                : 'El paquete no tiene precio o fecha de salida válidos.'
        );

        actualizarCalculo();
    }

    function actualizarCalculo() {
        const $opcionCliente =
            obtenerOpcion($cliente);

        const $opcionDestino =
            obtenerOpcion($destino);

        const clienteId = $cliente.val();
        const destinoId = $destino.val();

        const completo =
            String(
                $opcionCliente.data('completo')
            ) === '1';

        if (
            !clienteId ||
            !destinoId ||
            !completo
        ) {
            $('#seccionCalculo').addClass('oculto');
            calculoActual = null;
            return;
        }

        const fechaNacimiento =
            $opcionCliente.data(
                'fecha-nacimiento'
            );

        const fechaSalida =
            $opcionDestino.data('fecha-salida');

        const precioBase = Number(
            $opcionDestino.data('precio') || 0
        );

        const moneda =
            $opcionDestino.data('moneda') || 'USD';

        const edad = calcularEdad(
            fechaNacimiento,
            fechaSalida
        );

        if (
            edad === null ||
            edad < 0 ||
            precioBase <= 0
        ) {
            $('#seccionCalculo').addClass('oculto');
            calculoActual = null;
            return;
        }

        const tarifa = determinarTarifa(edad);

        const total =
            precioBase *
            (tarifa.porcentaje / 100);

        calculoActual = {
            edad: edad,
            categoria: tarifa.categoria,
            porcentaje: tarifa.porcentaje,
            precioBase: precioBase,
            total: total,
            moneda: moneda
        };

        $('#tarifaEdad').text(
            `${edad} ${edad === 1 ? 'año' : 'años'}`
        );

        $('#tarifaCategoria').text(
            tarifa.categoria
        );

        $('#tarifaPorcentaje').text(
            `${tarifa.porcentaje}%`
        );

        $('#tarifaTotal').text(
            formatearDinero(total, moneda)
        );

        $('#seccionCalculo').removeClass('oculto');
    }

    function validarFormulario() {
        const $opcionCliente =
            obtenerOpcion($cliente);

        const $opcionDestino =
            obtenerOpcion($destino);

        let clienteValido = true;
        let destinoValido = true;

        if (!$cliente.val()) {
            clienteValido = mostrarError(
                'cliente_id',
                'Selecciona el cliente que realizará el viaje.'
            );
        } else if (
            String(
                $opcionCliente.data('completo')
            ) !== '1'
        ) {
            clienteValido = mostrarError(
                'cliente_id',
                'Completa la información del cliente antes de reservar.'
            );
        } else {
            clienteValido = mostrarError(
                'cliente_id',
                ''
            );
        }

        if (!$destino.val()) {
            destinoValido = mostrarError(
                'destino_id',
                'Selecciona el paquete turístico.'
            );
        } else if (
            !$opcionDestino.data('fecha-salida') ||
            Number(
                $opcionDestino.data('precio') || 0
            ) <= 0
        ) {
            destinoValido = mostrarError(
                'destino_id',
                'El paquete no tiene información válida para reservar.'
            );
        } else {
            destinoValido = mostrarError(
                'destino_id',
                ''
            );
        }

        return (
            clienteValido &&
            destinoValido &&
            Boolean(calculoActual)
        );
    }

    $cliente.on('change', actualizarCliente);
    $destino.on('change', actualizarPaquete);

    $formulario.on('submit', function (evento) {
        evento.preventDefault();

        if (enviando) {
            return;
        }

        if (!validarFormulario()) {
            Swal.fire({
                icon: 'error',
                title: 'Revisa la información',
                text: 'Selecciona un cliente y un paquete válidos.',
                confirmButtonText: 'Corregir',
                confirmButtonColor: '#094c90'
            });

            $('.input-error').first().trigger('focus');
            return;
        }

        const nombreCliente =
            obtenerOpcion($cliente).data('nombre');

        const nombrePaquete =
            obtenerOpcion($destino).data('nombre');

        Swal.fire({
            icon: 'question',
            title: esEdicion
                ? '¿Guardar los cambios?'
                : '¿Registrar la reserva?',
            html:
                `<strong>${nombreCliente}</strong><br>` +
                `${nombrePaquete}<br>` +
                `Total: <strong>${formatearDinero(
                    calculoActual.total,
                    calculoActual.moneda
                )}</strong>`,
            showCancelButton: true,
            confirmButtonText: esEdicion
                ? 'Sí, guardar cambios'
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
                esEdicion
                    ? 'Guardando cambios...'
                    : 'Registrando reserva...'
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
                title: 'Revisa la información',
                text: mensajes.join('\n'),
                confirmButtonText: 'Corregir',
                confirmButtonColor: '#094c90'
            });
        } else if (configuracion.mensajeError) {
            Swal.fire({
                icon: 'error',
                title: esEdicion
                    ? 'No se pudo actualizar la reserva'
                    : 'No se pudo registrar la reserva',
                text: configuracion.mensajeError,
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#094c90'
            });
        }
    }

    actualizarCliente();
    actualizarPaquete();
    mostrarErroresServidor();
});