$(function () {
    const configuracion =
        window.configuracionPrerreservas ||
        {};

    const tablaElemento = document.getElementById(
        'tablaPrerreservas'
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
            columns: [0, 1, 2, 3, 4, 5, 6],
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
                title: 'Reporte de prerreservas',
                filename: 'reporte_prerreservas_' + fechaArchivo,
                titleAttr: 'Descargar reporte en Excel',
                className:
                    'btn-exportar-prerreservas exportar-excel',
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
                title: 'Reporte de prerreservas',
                filename: 'reporte_prerreservas_' + fechaArchivo,
                titleAttr: 'Descargar reporte en PDF',
                className:
                    'btn-exportar-prerreservas exportar-pdf',
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
                            '*',
                            '*',
                            '*',
                            'auto',
                            'auto',
                            'auto',
                            'auto'
                        ];
                    }
                }
            });
        }

        new DataTable(tablaElemento, {
            autoWidth: false,
            order: [],
            pageLength: 12,
            lengthMenu: [12, 25, 50, 100],
            searching: false,
            columnDefs: [
                {
                    targets: 7,
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
                    '<div class="sin-prerreservas">' +
                        '<i class="bi bi-whatsapp"></i>' +
                        '<strong>No existen prerreservas para mostrar</strong>' +
                        '<span>Cambia los filtros o espera una nueva solicitud desde WhatsApp.</span>' +
                    '</div>',
                info:
                    'Mostrando _START_ a _END_ de _TOTAL_ prerreservas',
                infoEmpty: 'No hay prerreservas disponibles',
                lengthMenu: 'Mostrar _MENU_ prerreservas',
                zeroRecords:
                    'No se encontraron prerreservas con estos criterios.',
                paginate: {
                    first: 'Primera',
                    last: 'Última',
                    next: 'Siguiente',
                    previous: 'Anterior'
                }
            }
        });
    }

    const $formularioFiltros =
        $('.filtros-prerreservas');

    $formularioFiltros.on(
        'submit',
        function () {
            const $buscar =
                $(this).find(
                    'input[name="buscar"]'
                );

            $buscar.val(
                $.trim($buscar.val())
            );
        }
    );

    $(document).on(
        'click',
        '.btn-convertir-prerreserva',
        function () {
            const formulario =
                $(this)
                    .closest('form')[0];

            Swal.fire({
                icon: 'question',
                title:
                    '¿Continuar con la reserva?',
                text:
                    'Se comprobarán los datos del cliente y después se abrirá el formulario correspondiente.',
                showCancelButton: true,
                confirmButtonText:
                    'Sí, continuar',
                cancelButtonText:
                    'Cancelar',
                confirmButtonColor:
                    '#094c90',
                cancelButtonColor:
                    '#65717E',
                reverseButtons: true,
            }).then(
                function (resultado) {
                    if (
                        resultado.isConfirmed
                    ) {
                        formulario.submit();
                    }
                }
            );
        }
    );

    $(document).on(
        'click',
        '.btn-descartar-prerreserva',
        function () {
            const formulario =
                $(this)
                    .closest('form')[0];

            Swal.fire({
                icon: 'warning',
                title:
                    '¿Descartar esta prerreserva?',
                text:
                    'La solicitud se conservará en el historial, pero ya no podrá convertirse en reserva.',
                showCancelButton: true,
                confirmButtonText:
                    'Sí, descartar',
                cancelButtonText:
                    'Cancelar',
                confirmButtonColor:
                    '#962234',
                cancelButtonColor:
                    '#65717E',
                reverseButtons: true,
            }).then(
                function (resultado) {
                    if (
                        resultado.isConfirmed
                    ) {
                        formulario.submit();
                    }
                }
            );
        }
    );

    const errores =
        configuracion.errores || {};

    const mensajes = [];

    Object.values(errores).forEach(
        function (grupo) {
            if (
                Array.isArray(grupo) &&
                grupo.length
            ) {
                mensajes.push(grupo[0]);
            }
        }
    );

    if (mensajes.length) {
        Swal.fire({
            icon: 'error',
            title:
                'Revisa la información',
            text:
                mensajes.join('\n'),
            confirmButtonText:
                'Corregir',
            confirmButtonColor:
                '#094c90',
        });

        return;
    }

    if (
        configuracion.mensajeError
    ) {
        Swal.fire({
            icon: 'error',
            title:
                'No se pudo completar la acción',
            text:
                configuracion.mensajeError,
            confirmButtonText:
                'Entendido',
            confirmButtonColor:
                '#094c90',
        });

        return;
    }

    if (
        configuracion.mensajeExito
    ) {
        Swal.fire({
            icon: 'success',
            title:
                'Proceso completado',
            text:
                configuracion.mensajeExito,
            confirmButtonText:
                'Entendido',
            confirmButtonColor:
                '#094c90',
        });
    }
});
