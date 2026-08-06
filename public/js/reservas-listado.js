$(function () {
    const configuracion =
        window.configuracionReservas || {};

    const modalElemento =
        document.getElementById('modalDetalleReserva');

    let modalDetalle = null;

    if (
        modalElemento &&
        typeof bootstrap !== 'undefined'
    ) {
        modalDetalle =
            bootstrap.Modal.getOrCreateInstance(
                modalElemento
            );
    }

    function escaparHtml(valor) {
        return $('<div>')
            .text(
                valor === null ||
                valor === undefined
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
            return new Intl.NumberFormat(
                'es-EC',
                {
                    style: 'currency',
                    currency: moneda || 'USD',
                    minimumFractionDigits: 2
                }
            ).format(numero);
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

        const fechaNormalizada =
            String(fecha).includes('T')
                ? fecha
                : fecha + 'T00:00:00';

        const objetoFecha =
            new Date(fechaNormalizada);

        if (
            Number.isNaN(
                objetoFecha.getTime()
            )
        ) {
            return fecha;
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
            textoSeguro(
                valor,
                alternativo
            )
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
        establecerTexto('#detalleAnticipo', '');
        establecerTexto(
            '#detalleLimiteAnticipo',
            ''
        );
        establecerTexto(
            '#detalleVencimientoSaldo',
            ''
        );
        establecerTexto(
            '#detalleUltimoPago',
            ''
        );
        establecerTexto(
            '#detalleCantidad',
            ''
        );

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

        const nombreViajero =
            String(
                viajero.nombre || ''
            ).trim();

        const responsable =
            String(
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

                <small
                    class="d-block
                    ${obtenerClaseEstadoPago(
                        viajero.saldo
                    )}"
                >
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
                ? Number(
                    viajero.porcentaje
                ) + '%'
                : '—';

        return `
            <tr>
                <td>
                    <div class="viajero-nombre">
                        ${escaparHtml(
                            textoSeguro(
                                viajero.nombre
                            )
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
                    ${escaparHtml(
                        porcentaje
                    )}
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
        const moneda =
            datos.moneda || 'USD';

        const grupo =
            datos.grupo || null;

        const viajeros =
            Array.isArray(datos.viajeros)
                ? datos.viajeros
                : [];

        let tipoReserva =
            datos.tipo === 'grupal'
                ? 'Reserva grupal'
                : 'Reserva individual';

        if (grupo?.tipo) {
            tipoReserva +=
                ' · ' + grupo.tipo;
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
            '#detalleAnticipo',
            formatearMoneda(
                datos.politica_pago?.anticipo,
                moneda
            )
        );

        establecerTexto(
            '#detalleLimiteAnticipo',
            datos.politica_pago
                ?.fecha_limite_anticipo,
            'Sin fecha'
        );

        establecerTexto(
            '#detalleVencimientoSaldo',
            datos.politica_pago
                ?.fecha_vencimiento_saldo,
            'Sin fecha'
        );

        establecerTexto(
            '#detalleUltimoPago',
            datos.politica_pago
                ?.ultimo_pago,
            'Sin pagos registrados'
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
                                        )}.

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

        viajeros.forEach(
            function (viajero) {
                filas +=
                    construirFilaViajero(
                        viajero,
                        grupo,
                        moneda
                    );
            }
        );

        if (!filas) {
            filas = `
                <tr>
                    <td
                        colspan="6"
                        class="text-center
                        text-muted py-4"
                    >
                        No existe información
                        de los viajeros.
                    </td>
                </tr>
            `;
        }

        $('#detalleViajeros')
            .html(filas);

        if (datos.cancelacion) {
            $('#detalleMotivoCancelacion')
                .text(
                    textoSeguro(
                        datos.cancelacion.motivo,
                        'No se registró un motivo.'
                    )
                );

            $('#detalleFechaCancelacion')
                .text(
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

    $(document).on(
        'click',
        '.btn-ver-reserva',
        function () {
            const boton = $(this);

            const url =
                boton.data('detalle-url');

            if (!url) {
                Swal.fire({
                    icon: 'error',
                    title:
                        'No se pudo consultar la reserva',
                    text:
                        'No se encontró la dirección del detalle.',
                    confirmButtonText:
                        'Entendido',
                    confirmButtonColor:
                        '#094c90'
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
                    Accept:
                        'application/json',
                    'X-Requested-With':
                        'XMLHttpRequest'
                }
            })
                .then(
                    async function (
                        respuesta
                    ) {
                        const datos =
                            await respuesta
                                .json()
                                .catch(
                                    function () {
                                        return {};
                                    }
                                );

                        if (!respuesta.ok) {
                            throw new Error(
                                datos.message ||
                                'No fue posible consultar el detalle.'
                            );
                        }

                        if (
                            !datos.success ||
                            !datos.data
                        ) {
                            throw new Error(
                                datos.message ||
                                'La respuesta de la reserva no contiene información.'
                            );
                        }

                        return datos.data;
                    }
                )
                .then(mostrarDetalle)
                .catch(
                    function (error) {
                        if (modalDetalle) {
                            modalDetalle.hide();
                        }

                        Swal.fire({
                            icon: 'error',
                            title:
                                'No se pudo cargar el detalle',
                            text:
                                error.message,
                            confirmButtonText:
                                'Entendido',
                            confirmButtonColor:
                                '#094c90'
                        });
                    }
                );
        }
    );

    $(document).on(
        'click',
        '.btn-cancelar-reserva',
        function () {
            const url =
                $(this).data(
                    'cancelar-url'
                );

            if (!url) {
                Swal.fire({
                    icon: 'error',
                    title:
                        'No se pudo cancelar',
                    text:
                        'No se encontró la dirección para cancelar la reserva.',
                    confirmButtonText:
                        'Entendido',
                    confirmButtonColor:
                        '#094c90'
                });

                return;
            }

            Swal.fire({
                icon: 'warning',
                title:
                    'Cancelar y liquidar reserva',

                html: `
                    <div class="cancelacion-formulario">
                        <p class="cancelacion-ayuda">
                            El reembolso será el dinero
                            pagado menos únicamente los
                            costos no recuperables que
                            queden documentados.
                        </p>

                        <label for="swalTipoCancelacion">
                            Tipo de cancelación
                        </label>

                        <select
                            id="swalTipoCancelacion"
                            class="swal2-select"
                        >
                            <option value="">
                                Selecciona una opción
                            </option>

                            <option value="fuerza_mayor">
                                Fuerza mayor
                            </option>

                            <option value="cliente">
                                Decisión del cliente
                            </option>

                            <option value="agencia">
                                Responsabilidad de la agencia
                            </option>

                            <option value="proveedor">
                                Responsabilidad del proveedor
                            </option>

                            <option value="otro">
                                Otro motivo
                            </option>
                        </select>

                        <label for="swalMotivoCancelacion">
                            Motivo
                        </label>

                        <textarea
                            id="swalMotivoCancelacion"
                            class="swal2-textarea"
                            maxlength="500"
                            placeholder="Describe lo ocurrido"
                        ></textarea>

                        <label for="swalGastosCancelacion">
                            Costos no reembolsables
                            comprobados
                        </label>

                        <input
                            id="swalGastosCancelacion"
                            class="swal2-input"
                            type="number"
                            min="0"
                            step="0.01"
                            value="0"
                        >

                        <label for="swalDetalleGastos">
                            Detalle de costos y proveedores
                        </label>

                        <textarea
                            id="swalDetalleGastos"
                            class="swal2-textarea"
                            maxlength="1000"
                            placeholder="Solo si existen costos no recuperables"
                        ></textarea>

                        <label for="swalEvidenciaCancelacion">
                            Evidencia revisada
                        </label>

                        <textarea
                            id="swalEvidenciaCancelacion"
                            class="swal2-textarea"
                            maxlength="1000"
                            placeholder="Obligatoria para fuerza mayor"
                        ></textarea>
                    </div>
                `,

                showCancelButton: true,
                confirmButtonText:
                    'Sí, cancelar reserva',
                cancelButtonText:
                    'Volver',
                confirmButtonColor:
                    '#90091d',
                cancelButtonColor:
                    '#65717E',
                reverseButtons: true,
                showLoaderOnConfirm: true,
                width: 620,

                preConfirm: function () {
                    const tipo =
                        $('#swalTipoCancelacion')
                            .val();

                    const motivo = $.trim(
                        $('#swalMotivoCancelacion')
                            .val() || ''
                    );

                    const gastos = Number(
                        $('#swalGastosCancelacion')
                            .val() || 0
                    );

                    const detalle = $.trim(
                        $('#swalDetalleGastos')
                            .val() || ''
                    );

                    const evidencia = $.trim(
                        $('#swalEvidenciaCancelacion')
                            .val() || ''
                    );

                    if (!tipo) {
                        Swal.showValidationMessage(
                            'Selecciona el tipo de cancelación.'
                        );

                        return false;
                    }

                    if (
                        motivo.length < 10
                    ) {
                        Swal.showValidationMessage(
                            'El motivo debe tener al menos 10 caracteres.'
                        );

                        return false;
                    }

                    if (
                        motivo.length > 500
                    ) {
                        Swal.showValidationMessage(
                            'El motivo no puede superar los 500 caracteres.'
                        );

                        return false;
                    }

                    if (
                        !Number.isFinite(gastos) ||
                        gastos < 0
                    ) {
                        Swal.showValidationMessage(
                            'El valor de costos no es válido.'
                        );

                        return false;
                    }

                    if (
                        gastos > 0 &&
                        detalle.length < 10
                    ) {
                        Swal.showValidationMessage(
                            'Detalla los costos no reembolsables y sus respaldos.'
                        );

                        return false;
                    }

                    if (
                        tipo ===
                            'fuerza_mayor' &&
                        evidencia.length < 10
                    ) {
                        Swal.showValidationMessage(
                            'Registra la evidencia revisada para la fuerza mayor.'
                        );

                        return false;
                    }

                    return fetch(url, {
                        method: 'PATCH',

                        headers: {
                            'Content-Type':
                                'application/json',
                            Accept:
                                'application/json',
                            'X-Requested-With':
                                'XMLHttpRequest',
                            'X-CSRF-TOKEN':
                                configuracion.token
                        },

                        body: JSON.stringify({
                            tipo_cancelacion:
                                tipo,

                            motivo_cancelacion:
                                motivo,

                            gastos_no_reembolsables:
                                gastos,

                            detalle_gastos_no_reembolsables:
                                detalle,

                            evidencia_cancelacion:
                                evidencia
                        })
                    })
                        .then(
                            async function (
                                respuesta
                            ) {
                                const datos =
                                    await respuesta
                                        .json()
                                        .catch(
                                            function () {
                                                return {};
                                            }
                                        );

                                if (
                                    !respuesta.ok
                                ) {
                                    let mensaje =
                                        datos.message ||
                                        'No fue posible cancelar la reserva.';

                                    if (
                                        datos.errors
                                    ) {
                                        const primerGrupoErrores =
                                            Object.values(
                                                datos.errors
                                            )[0];

                                        if (
                                            Array.isArray(
                                                primerGrupoErrores
                                            ) &&
                                            primerGrupoErrores
                                                .length
                                        ) {
                                            mensaje =
                                                primerGrupoErrores[0];
                                        }
                                    }

                                    throw new Error(
                                        mensaje
                                    );
                                }

                                return datos;
                            }
                        )
                        .catch(
                            function (error) {
                                Swal.showValidationMessage(
                                    error.message
                                );
                            }
                        );
                },

                allowOutsideClick:
                    function () {
                        return !Swal.isLoading();
                    }
            }).then(
                function (resultado) {
                    if (
                        !resultado.isConfirmed
                    ) {
                        return;
                    }

                    Swal.fire({
                        icon: 'success',
                        title:
                            'Reserva cancelada',
                        text:
                            resultado.value
                                ?.message ||
                            'La reserva fue cancelada correctamente.',
                        confirmButtonText:
                            'Entendido',
                        confirmButtonColor:
                            '#094c90'
                    }).then(
                        function () {
                            window.location.reload();
                        }
                    );
                }
            );
        }
    );

    if (configuracion.mensajeExito) {
        Swal.fire({
            icon: 'success',
            title:
                'Proceso completado',
            text:
                configuracion.mensajeExito,
            confirmButtonText:
                'Entendido',
            confirmButtonColor:
                '#094c90'
        });
    }

    if (configuracion.mensajeError) {
        Swal.fire({
            icon: 'error',
            title:
                'No se pudo completar la acción',
            text:
                configuracion.mensajeError,
            confirmButtonText:
                'Corregir',
            confirmButtonColor:
                '#094c90'
        });
    }
});