document.addEventListener('DOMContentLoaded', function () {
    const tablaElemento = document.getElementById(
        'tablaDevoluciones'
    );

    if (
        !tablaElemento ||
        typeof DataTable === 'undefined'
    ) {
        return;
    }

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
            title: 'Reporte de devoluciones',
            filename: 'reporte_devoluciones_' + fechaArchivo,
            titleAttr: 'Descargar reporte en Excel',
            className:
                'btn-exportar-devoluciones exportar-excel',
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
            title: 'Reporte de devoluciones',
            filename: 'reporte_devoluciones_' + fechaArchivo,
            titleAttr: 'Descargar reporte en PDF',
            className:
                'btn-exportar-devoluciones exportar-pdf',
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
                        'auto',
                        '*',
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
                '<div class="sin-registros">' +
                    'No hay devoluciones registradas.' +
                '</div>',
            info:
                'Mostrando _START_ a _END_ de _TOTAL_ devoluciones',
            infoEmpty: 'No hay devoluciones registradas',
            lengthMenu: 'Mostrar _MENU_ devoluciones',
            zeroRecords:
                'No se encontraron devoluciones con estos criterios.',
            paginate: {
                first: 'Primera',
                last: 'Ultima',
                next: 'Siguiente',
                previous: 'Anterior'
            }
        }
    });
});
