$(function () {
    const configuracion =
        window.configuracionPagos || {};

    const modalRegistrar = bootstrap.Modal
        .getOrCreateInstance(
            document.getElementById(
                'modalRegistrarPago'
            )
        );

    const modalDesglose = bootstrap.Modal
        .getOrCreateInstance(
            document.getElementById(
                'modalDesglosePago'
            )
        );

    const modalHistorial = bootstrap.Modal
        .getOrCreateInstance(
            document.getElementById(
                'modalHistorialPagos'
            )
        );

    const modalEditar = bootstrap.Modal
        .getOrCreateInstance(
            document.getElementById(
                'modalEditarPago'
            )
        );

    const modalAnular = bootstrap.Modal
        .getOrCreateInstance(
            document.getElementById(
                'modalAnularPago'
            )
        );

    const $formularioPago =
        $('#formularioRegistrarPago');

    const $formularioEditar =
        $('#formularioEditarPago');

    let saldoMaximo = 0;
    let monedaActual = 'USD';
    let enviandoPago = false;
    let enviandoEdicion = false;
    let tablaPagos = null;

    const tablaElemento = document.getElementById(
        'tablaPagos'
    );

    if (
        tablaElemento &&
        typeof DataTable !== 'undefined'
    ) {
        const fechaActual = new Date();
        const fechaArchivo = [
            fechaActual.getFullYear(),
            String(fechaActual.getMonth() + 1).padStart(2, '0'),
            String(fechaActual.getDate()).padStart(2, '0')
        ].join('-');

        const opcionesExportacion = {
            columns: [0, 1, 2, 3, 4, 5],
            modifier: {
                search: 'applied',
                page: 'all'
            },
            format: {
                body: function (contenido, fila, columna, nodo) {
                    const texto = nodo
                        ? nodo.innerText
                        : contenido;

                    return String(texto || '')
                        .replace(/\s+/g, ' ')
                        .trim();
                }
            }
        };

        const botonesExportacion = [];
        const botonesRegistrados =
            DataTable.ext?.buttons || {};

        if (
            botonesRegistrados.excelHtml5 &&
            typeof JSZip !== 'undefined'
        ) {
            if (
                typeof DataTable.Buttons?.jszip === 'function'
            ) {
                DataTable.Buttons.jszip(JSZip);
            }

            botonesExportacion.push({
                extend: 'excelHtml5',
                text:
                    '<i class="bi bi-file-earmark-excel"></i> Excel',
                title: 'Reporte de pagos',
                filename: 'reporte_pagos_' + fechaArchivo,
                titleAttr: 'Descargar reporte en Excel',
                className: 'btn-exportar-pagos exportar-excel',
                exportOptions: opcionesExportacion
            });
        }

        if (
            botonesRegistrados.pdfHtml5 &&
            typeof pdfMake !== 'undefined'
        ) {
            if (
                typeof DataTable.Buttons?.pdfMake === 'function'
            ) {
                DataTable.Buttons.pdfMake(pdfMake);
            }

            botonesExportacion.push({
                extend: 'pdfHtml5',
                text:
                    '<i class="bi bi-file-earmark-pdf"></i> PDF',
                title: 'Reporte de pagos',
                filename: 'reporte_pagos_' + fechaArchivo,
                titleAttr: 'Descargar reporte en PDF',
                className: 'btn-exportar-pagos exportar-pdf',
                orientation: 'landscape',
                pageSize: 'A4',
                exportOptions: opcionesExportacion,
                customize: function (documento) {
                    documento.pageMargins = [24, 24, 24, 24];
                    documento.defaultStyle.fontSize = 8;
                    documento.styles.tableHeader.fontSize = 8;

                    const contenidoTabla = documento.content.find(
                        function (contenido) {
                            return contenido.table;
                        }
                    );

                    if (contenidoTabla) {
                        contenidoTabla.table.widths = [
                            'auto',
                            '*',
                            'auto',
                            '*',
                            'auto',
                            'auto'
                        ];
                    }
                }
            });
        }

        tablaPagos = new DataTable(tablaElemento, {
            autoWidth: false,
            order: [],
            pageLength: 12,
            lengthMenu: [12, 25, 50, 100],
            columnDefs: [
                {
                    targets: 6,
                    orderable: false,
                    searchable: false
                }
            ],
            layout: {
                topStart: botonesExportacion.length
                    ? {
                        buttons: botonesExportacion
                    }
                    : 'pageLength',
                topEnd: botonesExportacion.length
                    ? 'pageLength'
                    : null,
                bottomStart: 'info',
                bottomEnd: 'paging'
            },
            language: {
                emptyTable:
                    '<div class="sin-pagos">' +
                        '<i class="bi bi-receipt"></i>' +
                        '<strong>No existen reservas para mostrar</strong>' +
                        '<span>Cambia los filtros o registra una reserva.</span>' +
                    '</div>',
                info:
                    'Mostrando _START_ a _END_ de _TOTAL_ registros',
                infoEmpty: 'No hay registros de pago disponibles',
                lengthMenu: 'Mostrar _MENU_ registros',
                zeroRecords:
                    'No se encontraron pagos con estos criterios.',
                paginate: {
                    first: 'Primera',
                    last: 'Última',
                    next: 'Siguiente',
                    previous: 'Anterior'
                }
            }
        });
    }

    function escapar(valor) {
        return $('<div>')
            .text(
                valor === null ||
                valor === undefined
                    ? ''
                    : valor
            )
            .html();
    }

    function formatearDinero(
        valor,
        moneda = 'USD'
    ) {
        const numero = Number(valor || 0);

        try {
            return new Intl.NumberFormat(
                'es-EC',
                {
                    style: 'currency',
                    currency: moneda,
                    minimumFractionDigits: 2
                }
            ).format(numero);
        } catch (error) {
            return `${moneda} ${numero.toFixed(2)}`;
        }
    }

    function nombreCategoria(categoria) {
        const nombres = {
            infante: 'Infante',
            nino: 'Niño',
            adulto: 'Adulto',
            adulto_mayor: 'Adulto mayor'
        };

        return nombres[categoria] ||
            categoria ||
            'Sin información';
    }

    function mostrarErrorCampo(
        campo,
        mensaje
    ) {
        const $campo = $('#' + campo);
        const $error = $('#' + campo + 'Error');

        $campo.toggleClass(
            'input-error',
            Boolean(mensaje)
        );

        $error
            .text(mensaje || '')
            .toggle(Boolean(mensaje));

        return !mensaje;
    }

    function mensajeErrorRespuesta(
        datos,
        alternativo
    ) {
        if (datos?.errors) {
            const primerError =
                Object.values(datos.errors)[0];

            if (
                Array.isArray(primerError) &&
                primerError.length
            ) {
                return primerError[0];
            }
        }

        return datos?.message || alternativo;
    }

    $('#buscarPagos').on(
        'input',
        function () {
            const texto = $.trim(
                $(this).val()
            ).toLocaleLowerCase();

            if (tablaPagos) {
                tablaPagos.search(texto).draw();

                return;
            }

            $('.fila-pago').each(
                function () {
                    const contenido = String(
                        $(this).data('busqueda') || ''
                    ).toLocaleLowerCase();

                    $(this).toggle(
                        contenido.includes(texto)
                    );
                }
            );
        }
    );

    function abrirFormularioCobro(datos) {
        saldoMaximo = Number(
            datos.saldo || 0
        );

        monedaActual =
            datos.moneda || 'USD';

        $('#pagoReservaId').val(
            datos.reservaId
        );

        $('#pagoClienteId').val(
            datos.clienteId || ''
        );

        $('#pagoCodigoReserva').text(
            datos.codigo || 'Reserva'
        );

        $('#pagoNombreCliente').text(
            datos.nombre ||
            'Cliente no disponible'
        );

        $('#pagoSaldoDisponible').text(
            formatearDinero(
                saldoMaximo,
                monedaActual
            )
        );

        $('#pagoMonto')
            .val('')
            .attr(
                'max',
                saldoMaximo.toFixed(2)
            );

        $('#pagoMetodo').val('');
        $('#pagoReferencia').val('');

        mostrarErrorCampo(
            'pagoMonto',
            ''
        );

        mostrarErrorCampo(
            'pagoMetodo',
            ''
        );

        mostrarErrorCampo(
            'pagoReferencia',
            ''
        );

        enviandoPago = false;

        const $boton = $formularioPago.find(
            'button[type="submit"]'
        );

        $boton.prop('disabled', false);
        $boton.find('span').text(
            'Registrar pago'
        );

        modalDesglose.hide();

        setTimeout(function () {
            modalRegistrar.show();
        }, 180);
    }

    $(document).on(
        'click',
        '.btn-cobrar-reserva',
        function () {
            abrirFormularioCobro({
                reservaId:
                    $(this).data('reserva-id'),
                clienteId:
                    $(this).data('cliente-id'),
                codigo:
                    $(this).data('codigo'),
                nombre:
                    $(this).data('nombre'),
                saldo:
                    $(this).data('saldo'),
                moneda:
                    $(this).data('moneda')
            });
        }
    );

    function validarRegistroPago() {
        const monto = Number(
            $('#pagoMonto').val()
        );

        const metodo =
            $('#pagoMetodo').val();

        const referencia = $.trim(
            $('#pagoReferencia').val()
        );

        let valido = true;

        valido = mostrarErrorCampo(
            'pagoMonto',
            !monto || monto <= 0
                ? 'Ingresa un monto mayor que cero.'
                : (
                    monto > saldoMaximo
                        ? 'El monto no puede superar el saldo disponible.'
                        : ''
                )
        ) && valido;

        valido = mostrarErrorCampo(
            'pagoMetodo',
            metodo
                ? ''
                : 'Selecciona el método de pago.'
        ) && valido;

        const necesitaReferencia =
            metodo === 'transferencia' ||
            metodo === 'tarjeta';

        valido = mostrarErrorCampo(
            'pagoReferencia',
            necesitaReferencia &&
            !referencia
                ? 'Ingresa el comprobante o referencia.'
                : ''
        ) && valido;

        return valido;
    }

    $('#pagoMonto').on(
        'input blur',
        validarRegistroPago
    );

    $('#pagoMetodo').on(
        'change',
        validarRegistroPago
    );

    $('#pagoReferencia').on(
        'input blur',
        validarRegistroPago
    );

    $formularioPago.on(
        'submit',
        function (evento) {
            evento.preventDefault();

            if (enviandoPago) {
                return;
            }

            if (!validarRegistroPago()) {
                Swal.fire({
                    icon: 'error',
                    title: 'Revisa el pago',
                    text:
                        'Corrige los datos señalados antes de continuar.',
                    confirmButtonText: 'Corregir',
                    confirmButtonColor: '#094c90'
                });

                return;
            }

            const monto = Number(
                $('#pagoMonto').val()
            );

            Swal.fire({
                icon: 'question',
                title: '¿Registrar el pago?',
                html:
                    'Se registrará un pago de ' +
                    `<strong>${formatearDinero(
                        monto,
                        monedaActual
                    )}</strong>.`,
                showCancelButton: true,
                confirmButtonText:
                    'Sí, registrar',
                cancelButtonText:
                    'Cancelar',
                confirmButtonColor:
                    '#094c90',
                cancelButtonColor:
                    '#65717E',
                reverseButtons: true
            }).then(function (resultado) {
                if (!resultado.isConfirmed) {
                    return;
                }

                enviandoPago = true;

                const $boton =
                    $formularioPago.find(
                        'button[type="submit"]'
                    );

                $boton.prop(
                    'disabled',
                    true
                );

                $boton.find('span').text(
                    'Registrando...'
                );

                $formularioPago[0].submit();
            });
        }
    );

    $(document).on(
        'click',
        '.btn-desglose-pago',
        function () {
            const url = $(this).data('url');

            $('#desgloseCargando')
                .removeClass('oculto');

            $('#desgloseContenido')
                .addClass('oculto');

            $('#cuerpoDesglosePago').empty();
            $('#resumenDesglose').empty();

            modalDesglose.show();

            fetch(url, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With':
                        'XMLHttpRequest'
                }
            })
                .then(async function (
                    respuesta
                ) {
                    const datos =
                        await respuesta
                            .json()
                            .catch(() => ({}));

                    if (!respuesta.ok) {
                        throw new Error(
                            mensajeErrorRespuesta(
                                datos,
                                'No se pudo consultar el grupo.'
                            )
                        );
                    }

                    return datos.data;
                })
                .then(mostrarDesglose)
                .catch(function (error) {
                    modalDesglose.hide();

                    Swal.fire({
                        icon: 'error',
                        title:
                            'No se pudo cargar el desglose',
                        text: error.message,
                        confirmButtonText:
                            'Entendido',
                        confirmButtonColor:
                            '#094c90'
                    });
                });
        }
    );

    function mostrarDesglose(datos) {
        const moneda =
            datos.moneda || 'USD';

        $('#desgloseNombreGrupo').text(
            datos.nombre_grupo
        );

        let resumen = `
            <strong>
                ${escapar(datos.codigo_reserva)}
            </strong>
            · ${escapar(
                datos.modalidad === 'familiar'
                    ? 'Pago familiar'
                    : 'Pago por integrante'
            )}
            · Total:
            <strong>
                ${escapar(
                    formatearDinero(
                        datos.precio_total,
                        moneda
                    )
                )}
            </strong>
            · Pagado:
            <strong>
                ${escapar(
                    formatearDinero(
                        datos.total_pagado,
                        moneda
                    )
                )}
            </strong>
            · Saldo:
            <strong>
                ${escapar(
                    formatearDinero(
                        datos.saldo_total,
                        moneda
                    )
                )}
            </strong>
        `;

        if (datos.responsable_pago) {
            resumen += `
                <br>
                ${datos.modalidad === 'familiar'
                    ? 'Saldo familiar total a cargo del titular:'
                    : 'Responsable del pago:'}
                <strong>
                    ${escapar(
                        datos.responsable_pago
                            .nombre
                    )}
                </strong>
            `;
        }

        if (datos.composicion_familiar) {
            const composicion = datos.composicion_familiar;
            const desglose = datos.desglose_familiar;
            resumen += `
                <br>
                Composición: ${composicion.cantidad_infantes} infantes,
                ${composicion.cantidad_ninos} niños,
                ${composicion.cantidad_adultos} adultos y
                ${composicion.cantidad_adultos_mayores} adultos mayores.
                <strong>${composicion.cantidad_viajeros} viajeros en total.</strong>
                ${desglose ? `
                    <br>
                    Desglose: infantes (0 %)
                    ${escapar(formatearDinero(desglose.subtotal_infantes, moneda))};
                    niños (50 %)
                    ${escapar(formatearDinero(desglose.subtotal_ninos, moneda))};
                    adultos (100 %)
                    ${escapar(formatearDinero(desglose.subtotal_adultos, moneda))};
                    adultos mayores (50 %)
                    ${escapar(formatearDinero(desglose.subtotal_adultos_mayores, moneda))}.
                    <strong>Total familiar:
                        ${escapar(formatearDinero(desglose.precio_total, moneda))}
                    </strong>
                ` : ''}
            `;
        }

        if (
            datos.modalidad === 'familiar' &&
            datos.puede_cobrar_total
        ) {
            resumen += `
                <button
                    type="button"
                    class="btn-cobrar-integrante ms-3 btn-cobrar-familiar"
                    data-reserva-id="${datos.reserva_id}"
                    data-cliente-id="${datos.responsable_pago?.id || ''}"
                    data-codigo="${escapar(datos.codigo_reserva)}"
                    data-nombre="${escapar(
                        datos.responsable_pago?.nombre ||
                        'Responsable del pago'
                    )}"
                    data-saldo="${datos.saldo_total}"
                    data-moneda="${escapar(moneda)}"
                >
                    Registrar pago familiar
                </button>
            `;
        }

        $('#resumenDesglose').html(
            resumen
        );

        const filas = (
            datos.integrantes || []
        ).map(function (integrante) {
            const etiquetas = [];

            if (integrante.es_lider) {
                etiquetas.push('Líder');
            }

            if (
                integrante
                    .es_responsable_pago
            ) {
                etiquetas.push(
                    'Responsable del pago'
                );
            }

            let accion = '—';

            if (
                datos.modalidad ===
                    'independiente' &&
                integrante.puede_cobrar
            ) {
                accion = `
                    <button
                        type="button"
                        class="btn-cobrar-integrante btn-cobrar-miembro"
                        data-reserva-id="${datos.reserva_id}"
                        data-cliente-id="${integrante.cliente_id}"
                        data-codigo="${escapar(datos.codigo_reserva)}"
                        data-nombre="${escapar(integrante.nombre_completo)}"
                        data-saldo="${integrante.pendiente}"
                        data-moneda="${escapar(moneda)}"
                    >
                        Cobrar
                    </button>
                `;
            }

            const pagado =
                integrante.pagado === null
                    ? 'Pago colectivo'
                    : formatearDinero(
                        integrante.pagado,
                        moneda
                    );

            const pendiente =
                integrante.pendiente === null
                    ? 'Pago colectivo'
                    : formatearDinero(
                        integrante.pendiente,
                        moneda
                    );

            return `
                <tr>
                    <td>
                        <div class="integrante-identidad">
                            <strong>
                                ${escapar(
                                    integrante
                                        .nombre_completo
                                )}
                            </strong>

                            <small>
                                ${escapar(
                                    integrante.documento ||
                                    'Sin documento'
                                )}
                            </small>

                            ${etiquetas.map(
                                etiqueta => `
                                    <span class="etiqueta-integrante">
                                        ${escapar(etiqueta)}
                                    </span>
                                `
                            ).join('')}
                        </div>
                    </td>

                    <td>
                        ${integrante.es_titular_familiar
                            ? 'Titular y responsable del pago'
                            : `
                                ${escapar(nombreCategoria(integrante.categoria))}
                                <small class="d-block text-muted">
                                    ${integrante.edad ?? '—'} años
                                    · ${integrante.porcentaje ?? '—'}%
                                </small>
                            `}
                    </td>

                    <td>
                        ${integrante.es_titular_familiar
                            ? 'Pago familiar colectivo'
                            : escapar(formatearDinero(integrante.asignado, moneda))}
                    </td>

                    <td>${escapar(pagado)}</td>
                    <td>${escapar(pendiente)}</td>
                    <td>${accion}</td>
                </tr>
            `;
        }).join('');

        $('#cuerpoDesglosePago').html(
            filas || `
                <tr>
                    <td
                        colspan="6"
                        class="text-center text-muted py-4"
                    >
                        No existen integrantes.
                    </td>
                </tr>
            `
        );

        $('#desgloseCargando')
            .addClass('oculto');

        $('#desgloseContenido')
            .removeClass('oculto');
    }

    $(document).on(
        'click',
        '.btn-cobrar-familiar, .btn-cobrar-miembro',
        function () {
            abrirFormularioCobro({
                reservaId:
                    $(this).data('reserva-id'),
                clienteId:
                    $(this).data('cliente-id'),
                codigo:
                    $(this).data('codigo'),
                nombre:
                    $(this).data('nombre'),
                saldo:
                    $(this).data('saldo'),
                moneda:
                    $(this).data('moneda')
            });
        }
    );

    $(document).on(
        'click',
        '.btn-historial-pagos',
        function () {
            const url = $(this).data('url');
            const codigo =
                $(this).data('codigo');

            $('#historialCodigoReserva').text(
                codigo
            );

            $('#historialCargando')
                .removeClass('oculto');

            $('#historialContenido')
                .addClass('oculto');

            $('#listaHistorialPagos').empty();
            $('#historialResumen').empty();

            modalHistorial.show();

            cargarHistorial(url);
        }
    );

    function cargarHistorial(url) {
        fetch(url, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With':
                    'XMLHttpRequest'
            }
        })
            .then(async function (respuesta) {
                const datos =
                    await respuesta
                        .json()
                        .catch(() => ({}));

                if (!respuesta.ok) {
                    throw new Error(
                        mensajeErrorRespuesta(
                            datos,
                            'No se pudo consultar el historial.'
                        )
                    );
                }

                return datos;
            })
            .then(mostrarHistorial)
            .catch(function (error) {
                modalHistorial.hide();

                Swal.fire({
                    icon: 'error',
                    title:
                        'No se pudo cargar el historial',
                    text: error.message,
                    confirmButtonText:
                        'Entendido',
                    confirmButtonColor:
                        '#094c90'
                });
            });
    }

    function mostrarHistorial(datos) {
        $('#historialResumen').html(
            'Total registrado: ' +
            `<strong>${formatearDinero(
                datos.total_registrado,
                datos.data?.[0]?.moneda ||
                'USD'
            )}</strong>`
        );

        const elementos = (
            datos.data || []
        ).map(function (pago) {
            const acciones = [];

            if (pago.puede_editar) {
                acciones.push(`
                    <button
                        type="button"
                        class="btn-editar-pago"
                        data-id="${pago.id}"
                        data-reserva-id="${pago.reserva_id || ''}"
                        data-monto="${pago.monto}"
                        data-metodo="${escapar(pago.metodo_pago_val)}"
                        data-referencia="${escapar(
                            pago.referencia ===
                                'Sin referencia'
                                ? ''
                                : pago.referencia
                        )}"
                        title="Editar pago"
                    >
                        <i class="bi bi-pencil-square"></i>
                    </button>
                `);
            }

            if (pago.puede_anular) {
                acciones.push(`
                    <button
                        type="button"
                        class="btn-anular-pago"
                        data-id="${pago.id}"
                        data-reserva-id="${pago.reserva_id || ''}"
                        title="Anular pago"
                    >
                        <i class="bi bi-x-circle"></i>
                    </button>
                `);
            }

            const anulacion =
                pago.esta_anulado
                    ? `
                        <small>
                            Motivo:
                            ${escapar(
                                pago.motivo_anulacion ||
                                'Sin información'
                            )}
                        </small>
                    `
                    : '';

            return `
                <article class="historial-item ${
                    pago.esta_anulado
                        ? 'anulado'
                        : ''
                }">
                    <div>
                        <strong>
                            ${escapar(pago.cliente)}
                        </strong>

                        <small>
                            ${escapar(
                                pago.fecha_pago_fmt
                            )}
                            · Registrado por
                            ${escapar(pago.cobrador)}
                        </small>

                        ${anulacion}
                    </div>

                    <div>
                        <strong>
                            ${escapar(
                                pago.metodo_pago
                            )}
                        </strong>

                        <small>
                            ${escapar(
                                pago.referencia
                            )}
                        </small>
                    </div>

                    <div>
                        <strong>
                            ${escapar(
                                formatearDinero(
                                    pago.monto,
                                    pago.moneda
                                )
                            )}
                        </strong>

                        <span class="estado-transaccion ${escapar(pago.estado)}">
                            ${pago.esta_anulado
                                ? 'Anulado'
                                : 'Registrado'}
                        </span>
                    </div>

                    <div class="acciones-historial">
                        ${acciones.join('')}
                    </div>
                </article>
            `;
        }).join('');

        $('#listaHistorialPagos').html(
            elementos || `
                <div class="sin-pagos">
                    <i class="bi bi-receipt"></i>
                    <strong>
                        No hay pagos registrados
                    </strong>
                </div>
            `
        );

        $('#historialCargando')
            .addClass('oculto');

        $('#historialContenido')
            .removeClass('oculto');
    }

    $(document).on(
        'click',
        '.btn-editar-pago',
        function () {
            const id = $(this).data('id');

            $('#formularioEditarPago').attr(
                'action',
                `${configuracion.basePagos}/${id}`
            );

            $('#editarReservaId').val(
                $(this).data('reserva-id')
            );

            $('#editarPagoTitulo').text(
                `Pago #${id}`
            );

            $('#editarPagoMonto').val(
                $(this).data('monto')
            );

            $('#editarPagoMetodo').val(
                $(this).data('metodo')
            );

            $('#editarPagoReferencia').val(
                $(this).data('referencia')
            );

            enviandoEdicion = false;

            modalHistorial.hide();

            setTimeout(function () {
                modalEditar.show();
            }, 180);
        }
    );

    $formularioEditar.on(
        'submit',
        function (evento) {
            evento.preventDefault();

            if (enviandoEdicion) {
                return;
            }

            const monto = Number(
                $('#editarPagoMonto').val()
            );

            const metodo =
                $('#editarPagoMetodo').val();

            const referencia = $.trim(
                $('#editarPagoReferencia').val()
            );

            if (!monto || monto <= 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Monto incorrecto',
                    text:
                        'El monto debe ser mayor que cero.',
                    confirmButtonText:
                        'Corregir',
                    confirmButtonColor:
                        '#094c90'
                });

                return;
            }

            if (
                (
                    metodo === 'transferencia' ||
                    metodo === 'tarjeta'
                ) &&
                !referencia
            ) {
                Swal.fire({
                    icon: 'error',
                    title:
                        'Falta la referencia',
                    text:
                        'Ingresa el comprobante o referencia del pago.',
                    confirmButtonText:
                        'Corregir',
                    confirmButtonColor:
                        '#094c90'
                });

                return;
            }

            Swal.fire({
                icon: 'question',
                title:
                    '¿Guardar la corrección?',
                text:
                    'Se actualizará la información de esta transacción.',
                showCancelButton: true,
                confirmButtonText:
                    'Sí, guardar',
                cancelButtonText:
                    'Cancelar',
                confirmButtonColor:
                    '#094c90',
                cancelButtonColor:
                    '#65717E',
                reverseButtons: true
            }).then(function (resultado) {
                if (!resultado.isConfirmed) {
                    return;
                }

                enviandoEdicion = true;
                $formularioEditar[0].submit();
            });
        }
    );

    $(document).on(
        'click',
        '.btn-anular-pago',
        function () {
            const pagoId =
                $(this).data('id');

            const reservaId =
                $(this).data('reserva-id');

            const mostrarConfirmacion = function () {
                Swal.fire({
                icon: 'warning',
                title: 'Anular pago',
                text:
                    'El pago permanecerá en el historial, pero dejará de sumarse al total recibido.',
                input: 'textarea',
                inputLabel:
                    'Motivo de la anulación',
                inputPlaceholder:
                    'Explica por qué se anula este pago...',
                inputAttributes: {
                    maxlength: 500
                },
                showCancelButton: true,
                confirmButtonText:
                    'Sí, anular pago',
                cancelButtonText:
                    'Cancelar',
                confirmButtonColor:
                    '#962234',
                cancelButtonColor:
                    '#65717E',
                reverseButtons: true,
                inputValidator: function (
                    valor
                ) {
                    const motivo = $.trim(
                        valor || ''
                    );

                    if (motivo.length < 10) {
                        return 'El motivo debe tener al menos 10 caracteres.';
                    }

                    if (motivo.length > 500) {
                        return 'El motivo no puede superar 500 caracteres.';
                    }

                    return null;
                }
                }).then(function (resultado) {
                    if (!resultado.isConfirmed) {
                        return;
                    }

                    $('#formularioAnularPago').attr(
                        'action',
                        `${configuracion.basePagos}/${pagoId}`
                    );

                    $('#anularReservaId').val(
                        reservaId
                    );

                    $('#anularMotivo').val(
                        $.trim(resultado.value)
                    );

                    $('#formularioAnularPago')[0]
                        .submit();
                });
            };

            const historialElemento =
                document.getElementById(
                    'modalHistorialPagos'
                );

            $('#formularioAnularPago').attr(
                'action',
                `${configuracion.basePagos}/${pagoId}`
            );

            $('#anularReservaId').val(reservaId);
            $('#anularMotivo').val('');

            historialElemento.addEventListener(
                'hidden.bs.modal',
                function () {
                    modalAnular.show();
                },
                { once: true }
            );

            modalHistorial.hide();
        }
    );

    const errores =
        configuracion.errores || {};

    const mensajesErrores = [];

    Object.keys(errores).forEach(
        function (campo) {
            if (
                Array.isArray(errores[campo]) &&
                errores[campo].length
            ) {
                mensajesErrores.push(
                    errores[campo][0]
                );
            }
        }
    );

    if (mensajesErrores.length) {
        Swal.fire({
            icon: 'error',
            title: 'Revisa la información',
            text:
                mensajesErrores.join('\n'),
            confirmButtonText: 'Corregir',
            confirmButtonColor: '#094c90'
        });
    } else if (
        configuracion.mensajeError
    ) {
        Swal.fire({
            icon: 'error',
            title:
                'No se pudo completar la acción',
            text:
                configuracion.mensajeError,
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#094c90'
        });
    } else if (
        configuracion.mensajeExito
    ) {
        Swal.fire({
            icon: 'success',
            title: 'Proceso completado',
            text:
                configuracion.mensajeExito,
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#094c90'
        });
    }
});
