document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    const configuracion =
        window.configuracionGestionBoletos || {};

    const modalElemento =
        document.querySelector('#modalBoleto');

    const formulario =
        document.querySelector('#formularioBoleto');

    if (
        !modalElemento ||
        !formulario ||
        typeof bootstrap === 'undefined'
    ) {
        return;
    }

    const modal = bootstrap.Modal.getOrCreateInstance(
        modalElemento
    );

    const personas = Array.isArray(
        configuracion.personas
    )
        ? configuracion.personas
        : [];

    const boletos = Array.isArray(
        configuracion.boletos
    )
        ? configuracion.boletos
        : [];

    const obtenerElemento = selector =>
        document.querySelector(selector);

    const campos = {
        titulo:
            obtenerElemento('#tituloModalBoleto'),

        nombreViajero:
            obtenerElemento('#boletoNombreViajero'),

        clienteId:
            obtenerElemento('#boletoClienteId'),

        viajeroId:
            obtenerElemento(
                '#boletoViajeroReservaId'
            ),

        numero:
            obtenerElemento('#boletoNumero'),

        asiento:
            obtenerElemento('#boletoAsiento'),

        clase:
            obtenerElemento('#boletoClase'),

        estado:
            obtenerElemento('#boletoEstado'),

        archivo:
            obtenerElemento('#boletoArchivo'),

        archivoActual:
            obtenerElemento(
                '#boletoArchivoActual'
            ),

        observaciones:
            obtenerElemento(
                '#boletoObservaciones'
            ),
    };

    function buscarPersona(
        personaId,
        personaTipo
    ) {
        return personas.find(persona =>
            String(persona.id) ===
                String(personaId) &&
            persona.tipo === personaTipo
        );
    }

    function buscarBoleto(persona) {
        if (!persona) {
            return null;
        }

        return boletos.find(boleto => {
            if (persona.tipo === 'viajero') {
                return String(
                    boleto.viajero_reserva_id
                ) === String(persona.id);
            }

            return String(
                boleto.cliente_id
            ) === String(persona.id);
        }) || null;
    }

    function colocar(
        elemento,
        valor = ''
    ) {
        if (!elemento) {
            return;
        }

        elemento.value =
            valor === null ||
            valor === undefined
                ? ''
                : String(valor);
    }

    function limpiarArchivoActual() {
        if (!campos.archivoActual) {
            return;
        }

        campos.archivoActual.replaceChildren();
        campos.archivoActual.classList.add(
            'oculto'
        );
    }

    function mostrarArchivoActual(
        boleto
    ) {
        limpiarArchivoActual();

        if (
            !boleto ||
            !boleto.archivo_url ||
            !campos.archivoActual
        ) {
            return;
        }

        const etiqueta =
            document.createElement('span');

        etiqueta.textContent =
            'Archivo actual:';

        const enlace =
            document.createElement('a');

        enlace.href =
            boleto.archivo_url;

        enlace.target = '_blank';
        enlace.rel =
            'noopener noreferrer';

        const icono =
            document.createElement('i');

        icono.className =
            'bi bi-file-earmark-check';

        const texto =
            document.createElement('span');

        texto.textContent =
            'Ver boleto registrado';

        enlace.append(
            icono,
            texto
        );

        campos.archivoActual.append(
            etiqueta,
            enlace
        );

        campos.archivoActual.classList.remove(
            'oculto'
        );
    }

    function limpiarFormulario() {
        formulario.reset();

        formulario.action =
            configuracion.storeUrl || '';

        colocar(campos.clienteId);
        colocar(campos.viajeroId);
        colocar(campos.numero);
        colocar(campos.asiento);
        colocar(campos.clase);
        colocar(
            campos.estado,
            'pendiente'
        );
        colocar(campos.observaciones);

        if (campos.archivo) {
            campos.archivo.value = '';
        }

        limpiarArchivoActual();
    }

    function configurarPersona(
        persona
    ) {
        if (!persona) {
            return;
        }

        if (persona.tipo === 'viajero') {
            colocar(
                campos.viajeroId,
                persona.id
            );

            colocar(
                campos.clienteId
            );
        } else {
            colocar(
                campos.clienteId,
                persona.id
            );

            colocar(
                campos.viajeroId
            );
        }

        if (campos.nombreViajero) {
            campos.nombreViajero.textContent =
                persona.nombre ||
                'Viajero';
        }
    }

    function cargarBoleto(
        boleto
    ) {
        if (!boleto) {
            return;
        }

        colocar(
            campos.numero,
            boleto.numero_boleto
        );

        colocar(
            campos.asiento,
            boleto.asiento
        );

        colocar(
            campos.clase,
            boleto.clase
        );

        colocar(
            campos.estado,
            boleto.estado_emision ||
                'pendiente'
        );

        colocar(
            campos.observaciones,
            boleto.observaciones
        );

        mostrarArchivoActual(
            boleto
        );
    }

    function abrirModal(
        persona,
        boleto = null,
        valoresAnteriores = null
    ) {
        if (!persona) {
            return;
        }

        limpiarFormulario();
        configurarPersona(persona);
        cargarBoleto(boleto);

        if (campos.titulo) {
            campos.titulo.textContent =
                boleto
                    ? 'Editar boleto'
                    : 'Asignar boleto';
        }

        if (valoresAnteriores) {
            colocar(
                campos.numero,
                valoresAnteriores.numero
            );

            colocar(
                campos.asiento,
                valoresAnteriores.asiento
            );

            colocar(
                campos.clase,
                valoresAnteriores.clase
            );

            colocar(
                campos.estado,
                valoresAnteriores.estado ||
                    'pendiente'
            );

            colocar(
                campos.observaciones,
                valoresAnteriores
                    .observaciones
            );
        }

        modal.show();

        modalElemento.addEventListener(
            'shown.bs.modal',
            () => {
                campos.numero?.focus();
            },
            {
                once: true,
            }
        );
    }

    document
        .querySelectorAll(
            '.btnGestionarBoletoPagina'
        )
        .forEach(boton => {
            boton.addEventListener(
                'click',
                () => {
                    const persona =
                        buscarPersona(
                            boton.dataset.personaId,
                            boton.dataset.personaTipo
                        );

                    if (!persona) {
                        return;
                    }

                    const boleto =
                        buscarBoleto(persona);

                    abrirModal(
                        persona,
                        boleto
                    );
                }
            );
        });

    /*
     * Solicita confirmación antes de eliminar
     * un boleto previamente registrado.
     */
    document
        .querySelectorAll(
            '.formEliminarBoletoPagina'
        )
        .forEach(formularioEliminar => {
            formularioEliminar.addEventListener(
                'submit',
                async evento => {
                    evento.preventDefault();

                    if (
                        typeof Swal !==
                        'undefined'
                    ) {
                        const resultado =
                            await Swal.fire({
                                icon:
                                    'warning',

                                title:
                                    '¿Eliminar boleto?',

                                text:
                                    'El viajero volverá a quedar pendiente de emisión.',

                                showCancelButton:
                                    true,

                                confirmButtonText:
                                    'Sí, eliminar',

                                cancelButtonText:
                                    'Cancelar',

                                confirmButtonColor:
                                    '#962234',
                            });

                        if (
                            resultado.isConfirmed
                        ) {
                            formularioEliminar.submit();
                        }

                        return;
                    }

                    const confirmado =
                        window.confirm(
                            '¿Deseas eliminar este boleto?'
                        );

                    if (confirmado) {
                        formularioEliminar.submit();
                    }
                }
            );
        });

    /*
     * Evita envíos duplicados cuando la conexión
     * tarda en responder.
     */
    formulario.addEventListener(
        'submit',
        () => {
            const botonGuardar =
                formulario.querySelector(
                    'button[type="submit"]'
                );

            if (!botonGuardar) {
                return;
            }

            botonGuardar.disabled =
                true;

            const texto =
                botonGuardar.querySelector(
                    'span'
                );

            if (texto) {
                texto.textContent =
                    'Guardando...';
            }
        }
    );

    /*
     * Si Laravel devuelve errores de validación,
     * vuelve a abrir el viajero que se estaba
     * gestionando y conserva los valores enviados.
     */
    const errores =
        configuracion.errores || {};

    const tieneErrores =
        Object.keys(errores).length > 0;

    if (tieneErrores) {
        const anterior =
            configuracion.formularioAnterior ||
            {};

        const personaTipo =
            anterior.viajeroId
                ? 'viajero'
                : 'cliente';

        const personaId =
            anterior.viajeroId ||
            anterior.clienteId;

        const persona =
            buscarPersona(
                personaId,
                personaTipo
            );

        if (persona) {
            const boleto =
                buscarBoleto(persona);

            abrirModal(
                persona,
                boleto,
                anterior
            );
        }
    }

    modalElemento.addEventListener(
        'hidden.bs.modal',
        () => {
            limpiarFormulario();

            if (campos.nombreViajero) {
                campos.nombreViajero.textContent =
                    '—';
            }
        }
    );
});