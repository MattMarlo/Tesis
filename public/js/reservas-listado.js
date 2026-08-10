$(function () {
    const configuracion = window.configuracionReservas || {};
    const modalElemento = document.getElementById('modalDetalleReserva');

    let modalDetalle = null;

    if (modalElemento && typeof bootstrap !== 'undefined') {
        modalDetalle = bootstrap.Modal.getOrCreateInstance(
            modalElemento
        );
    }

    function escaparHtml(valor) {
        return $('<div>')
            .text(
                valor === null || valor === undefined
                    ? ''
                    : valor
            )
            .html();
    }

    function textoSeguro(
        valor,
        textoAlternativo = 'Sin información'
    ) {
        if (
            valor === null ||
            valor === undefined ||
            valor === ''
        ) {
            return textoAlternativo;
        }

        return valor;
    }

    function formatearMoneda(
        valor,
        moneda = 'USD'
    ) {
        const numero = Number(valor || 0);

        try {
            return new Intl.NumberFormat('es-EC', {
                style: 'currency',
                currency: moneda || 'USD',
                minimumFractionDigits: 2
            }).format(numero);
        } catch (error) {
            return '$' + numero.toFixed(2);
        }
    }

    function formatearFecha(
        fecha,
        incluirHora = false
    ) {
        if (!fecha) {
            return 'Sin información';
        }

        const textoFecha = String(fecha);

        const fechaNormalizada = textoFecha.includes('T')
            ? textoFecha
            : textoFecha + 'T00:00:00';

        const objetoFecha = new Date(
            fechaNormalizada
        );

        if (Number.isNaN(objetoFecha.getTime())) {
            return textoFecha;
        }

        const opciones = {
            day: '2-digit',
            month: 'long',
            year: 'numeric'
        };

        if (incluirHora) {
            opciones.hour = '2-digit';
            opciones.minute = '2-digit';
        }

        return new Intl.DateTimeFormat(
            'es-EC',
            opciones
        ).format(objetoFecha);
    }

    function establecerTexto(
        selector,
        valor,
        alternativo = 'Sin información'
    ) {
        $(selector).text(
            textoSeguro(valor, alternativo)
        );
    }

    function mostrarEstadoCarga() {
        $('#detalleCargando')
            .removeClass('oculto');

        $('#detalleContenido')
            .addClass('oculto');

        establecerTexto('#detalleTipo', '');
        establecerTexto('#detalleCodigo', '');
        establecerTexto('#detallePaquete', '');
        establecerTexto('#detalleRuta', '');
        establecerTexto('#detalleSalida', '');
        establecerTexto('#detalleRegreso', '');
        establecerTexto('#detalleTotal', '');
        establecerTexto('#detallePagado', '');
        establecerTexto('#detalleSaldo', '');
        establecerTexto('#detalleCantidad', '');

        $('#detalleViajeros').empty();

        $('#detalleCancelacion')
            .addClass('oculto');

        $('#detalleMotivoCancelacion')
            .text('');

        $('#detalleFechaCancelacion')
            .text('');
    }

    function obtenerClaseEstadoPago(saldo) {
        return Number(saldo || 0) <= 0
            ? 'text-success'
            : 'text-danger';
    }

    function construirResponsabilidad(
        viajero,
        grupo
    ) {
        const etiquetas = [];

        const nombreViajero = String(
            viajero.nombre || ''
        ).trim();

        const responsable = String(
            grupo?.responsable_pago || ''
        ).trim();

        if (viajero.es_lider) {
            etiquetas.push(
                '<span class="badge-responsabilidad badge-lider">' +
                    'Líder' +
                '</span>'
            );
        }

        if (
            responsable &&
            nombreViajero.toLocaleLowerCase() ===
                responsable.toLocaleLowerCase()
        ) {
            etiquetas.push(
                '<span class="badge-responsabilidad badge-responsable">' +
                    'Responsable del pago' +
                '</span>'
            );
        }

        if (!etiquetas.length) {
            etiquetas.push(
                '<span class="badge-responsabilidad badge-integrante">' +
                    'Integrante' +
                '</span>'
            );
        }

        return etiquetas.join(' ');
    }

    function construirPrecioViajero(
        viajero,
        moneda
    ) {
        if (viajero.es_titular_familiar) {
            return (
                '<span class="text-muted">' +
                    'Pago familiar colectivo' +
                '</span>'
            );
        }

        let contenido = `
            <span class="fw-semibold">
                ${escaparHtml(
                    formatearMoneda(
                        viajero.precio,
                        moneda
                    )
                )}
            </span>
        `;

        if (
            viajero.pagado !== null &&
            viajero.pagado !== undefined &&
            viajero.saldo !== null &&
            viajero.saldo !== undefined
        ) {
            contenido += `
                <small class="d-block text-muted mt-1">
                    Pagado:
                    ${escaparHtml(
                        formatearMoneda(
                            viajero.pagado,
                            moneda
                        )
                    )}
                </small>

                <small class="d-block ${
                    obtenerClaseEstadoPago(
                        viajero.saldo
                    )
                }">
                    Saldo:
                    ${escaparHtml(
                        formatearMoneda(
                            viajero.saldo,
                            moneda
                        )
                    )}
                </small>
            `;
        }

        return contenido;
    }

    function construirFilaViajero(
        viajero,
        grupo,
        moneda
    ) {
        const edad =
            viajero.edad !== null &&
            viajero.edad !== undefined
                ? viajero.edad + ' años'
                : 'Sin información';

        const porcentaje =
            viajero.porcentaje !== null &&
            viajero.porcentaje !== undefined
                ? Number(viajero.porcentaje) + '%'
                : '—';

        return `
            <tr>
                <td>
                    <div class="viajero-nombre">
                        ${escaparHtml(
                            textoSeguro(viajero.nombre)
                        )}
                    </div>

                    <small class="text-muted">
                        ${escaparHtml(
                            textoSeguro(
                                viajero.documento,
                                'Sin documento'
                            )
                        )}
                    </small>
                </td>

                <td>
                    ${escaparHtml(edad)}
                </td>

                <td>
                    ${escaparHtml(
                        textoSeguro(
                            viajero.categoria,
                            'Sin información'
                        )
                    )}
                </td>

                <td>
                    ${escaparHtml(porcentaje)}
                </td>

                <td>
                    ${construirPrecioViajero(
                        viajero,
                        moneda
                    )}
                </td>

                <td>
                    ${construirResponsabilidad(
                        viajero,
                        grupo
                    )}
                </td>
            </tr>
        `;
    }

    function mostrarDetalle(datos) {
        const moneda = datos.moneda || 'USD';

        const grupo = datos.grupo || null;

        const viajeros = Array.isArray(
            datos.viajeros
        )
            ? datos.viajeros
            : [];

        let tipoReserva =
            datos.tipo === 'grupal'
                ? 'Reserva grupal'
                : 'Reserva individual';

        if (grupo?.tipo) {
            tipoReserva += ' · ' + grupo.tipo;
        }

        establecerTexto(
            '#detalleTipo',
            tipoReserva
        );

        establecerTexto(
            '#detalleCodigo',
            datos.codigo
        );

        establecerTexto(
            '#detallePaquete',
            datos.paquete?.nombre
        );

        const origen =
            datos.paquete?.origen || '';

        const destino =
            datos.paquete?.destino || '';

        const pais =
            datos.paquete?.pais || '';

        let ruta = [
            origen,
            destino
        ]
            .filter(Boolean)
            .join(' → ');

        if (pais) {
            ruta += ruta
                ? `, ${pais}`
                : pais;
        }

        establecerTexto(
            '#detalleRuta',
            ruta,
            'Ruta no especificada'
        );

        $('#detalleSalida').text(
            formatearFecha(
                datos.fecha_viaje
            )
        );

        $('#detalleRegreso').text(
            formatearFecha(
                datos.paquete?.fecha_regreso
            )
        );

        $('#detalleTotal').text(
            formatearMoneda(
                datos.precio_total,
                moneda
            )
        );

        $('#detallePagado').text(
            formatearMoneda(
                datos.total_pagado,
                moneda
            )
        );

        $('#detalleSaldo')
            .text(
                formatearMoneda(
                    datos.saldo_pendiente,
                    moneda
                )
            )
            .toggleClass(
                'text-danger',
                Number(
                    datos.saldo_pendiente || 0
                ) > 0
            )
            .toggleClass(
                'text-success',
                Number(
                    datos.saldo_pendiente || 0
                ) <= 0
            );

        establecerTexto(
            '#detalleCantidad',
            datos.cantidad_viajeros,
            viajeros.length || 1
        );

        const composicion =
            grupo?.composicion_familiar;

        const desglose =
            grupo?.desglose_familiar;

        $('#detalleComposicionFamiliar')
            .toggleClass(
                'oculto',
                !composicion
            )
            .html(
                composicion
                    ? `
                        <strong>
                            Composición familiar:
                        </strong>

                        ${Number(
                            composicion
                                .cantidad_infantes || 0
                        )} infantes,

                        ${Number(
                            composicion
                                .cantidad_ninos || 0
                        )} niños,

                        ${Number(
                            composicion
                                .cantidad_adultos || 0
                        )} adultos y

                        ${Number(
                            composicion
                                .cantidad_adultos_mayores || 0
                        )} adultos mayores.

                        Solo el titular está registrado
                        como cliente.

                        ${
                            desglose
                                ? `
                                    <div class="mt-2">
                                        Infantes (0 %):
                                        ${escaparHtml(
                                            formatearMoneda(
                                                desglose
                                                    .subtotal_infantes,
                                                moneda
                                            )
                                        )}

                                        · Niños (50 %):
                                        ${escaparHtml(
                                            formatearMoneda(
                                                desglose
                                                    .subtotal_ninos,
                                                moneda
                                            )
                                        )}

                                        · Adultos (100 %):
                                        ${escaparHtml(
                                            formatearMoneda(
                                                desglose
                                                    .subtotal_adultos,
                                                moneda
                                            )
                                        )}

                                        · Adultos mayores (50 %):
                                        ${escaparHtml(
                                            formatearMoneda(
                                                desglose
                                                    .subtotal_adultos_mayores,
                                                moneda
                                            )
                                        )}

                                        <strong>
                                            Total familiar:
                                            ${escaparHtml(
                                                formatearMoneda(
                                                    desglose
                                                        .precio_total,
                                                    moneda
                                                )
                                            )}
                                        </strong>
                                    </div>
                                `
                                : ''
                        }
                    `
                    : ''
            );

        let filas = '';

        viajeros.forEach(function (viajero) {
            filas += construirFilaViajero(
                viajero,
                grupo,
                moneda
            );
        });

        if (!filas) {
            filas = `
                <tr>
                    <td
                        colspan="6"
                        class="text-center text-muted py-4"
                    >
                        No existe información de los viajeros.
                    </td>
                </tr>
            `;
        }

        $('#detalleViajeros').html(filas);

        if (datos.cancelacion) {
            $('#detalleMotivoCancelacion').text(
                textoSeguro(
                    datos.cancelacion.motivo,
                    'No se registró un motivo.'
                )
            );

            $('#detalleFechaCancelacion').text(
                formatearFecha(
                    datos.cancelacion.fecha,
                    true
                )
            );

            $('#detalleCancelacion')
                .removeClass('oculto');
        } else {
            $('#detalleCancelacion')
                .addClass('oculto');
        }

        $('#detalleCargando')
            .addClass('oculto');

        $('#detalleContenido')
            .removeClass('oculto');
    }

    /*
     * Consulta y muestra el detalle de una reserva.
     */
    $(document).on(
        'click',
        '.btn-ver-reserva',
        function () {
            const boton = $(this);

            const url = boton.data(
                'detalle-url'
            );

            if (!url) {
                Swal.fire({
                    icon: 'error',
                    title:
                        'No se pudo consultar la reserva',
                    text:
                        'No se encontró la dirección ' +
                        'del detalle.',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#094c90'
                });

                return;
            }

            mostrarEstadoCarga();

            if (modalDetalle) {
                modalDetalle.show();
            }

            fetch(url, {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With':
                        'XMLHttpRequest'
                }
            })
                .then(async function (respuesta) {
                    const datos = await respuesta
                        .json()
                        .catch(function () {
                            return {};
                        });

                    if (!respuesta.ok) {
                        throw new Error(
                            datos.message ||
                            'No fue posible consultar ' +
                            'el detalle.'
                        );
                    }

                    if (
                        !datos.success ||
                        !datos.data
                    ) {
                        throw new Error(
                            datos.message ||
                            'La respuesta no contiene ' +
                            'información.'
                        );
                    }

                    return datos.data;
                })
                .then(mostrarDetalle)
                .catch(function (error) {
                    if (modalDetalle) {
                        modalDetalle.hide();
                    }

                    Swal.fire({
                        icon: 'error',
                        title:
                            'No se pudo cargar el detalle',
                        text: error.message,
                        confirmButtonText: 'Entendido',
                        confirmButtonColor: '#094c90'
                    });
                });
        }
    );

    /*
     * Ya no existe una petición PATCH para cancelar
     * directamente desde este archivo.
     *
     * Si todavía queda un botón antiguo en una vista,
     * este evento lo redirige de forma segura hacia la
     * página de solicitud.
     */
    $(document).on(
        'click',
        '.btn-solicitar-cancelacion',
        function (evento) {
            evento.preventDefault();

            const boton = $(this);

            const url =
                boton.data('solicitud-url') ||
                boton.attr('href');

            if (
                !url ||
                url === '#'
            ) {
                Swal.fire({
                    icon: 'error',
                    title:
                        'No se pudo abrir la solicitud',
                    text:
                        'No se encontró la dirección ' +
                        'para solicitar la cancelación.',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#094c90'
                });

                return;
            }

            window.location.assign(url);
        }
    );

    if (configuracion.mensajeExito) {
        Swal.fire({
            icon: 'success',
            title: 'Proceso completado',
            text: configuracion.mensajeExito,
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#094c90'
        });
    }

    if (configuracion.mensajeError) {
        Swal.fire({
            icon: 'error',
            title:
                'No se pudo completar la acción',
            text: configuracion.mensajeError,
            confirmButtonText: 'Corregir',
            confirmButtonColor: '#094c90'
        });
    }
});