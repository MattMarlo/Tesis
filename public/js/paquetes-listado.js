$(function () {
    const tarjetas = $('.tarjeta-paquete');
    const buscador = $('#buscarPaquete');
    const filtroCategoria = $('#filtrarCategoria');
    const filtroEstado = $('#filtrarEstado');
    const sinResultados = $('#sinResultados');

    const paquetes = Array.isArray(
        window.paquetesRegistrados
    )
        ? window.paquetesRegistrados
        : [];

    /*
    |--------------------------------------------------------------------------
    | Normalizar textos para la búsqueda
    |--------------------------------------------------------------------------
    */

    function normalizarTexto(texto) {
        return String(texto || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim();
    }

    /*
    |--------------------------------------------------------------------------
    | Búsqueda y filtros
    |--------------------------------------------------------------------------
    */

    function aplicarFiltros() {
        const texto = normalizarTexto(
            buscador.val()
        );

        const categoria = normalizarTexto(
            filtroCategoria.val()
        );

        const estado = filtroEstado.val();

        let cantidadVisible = 0;

        tarjetas.each(function () {
            const tarjeta = $(this);

            const datosBusqueda = normalizarTexto(
                tarjeta.data('busqueda')
            );

            const categoriaTarjeta = normalizarTexto(
                tarjeta.data('categoria')
            );

            const estadoTarjeta =
                tarjeta.data('estado');

            const coincideTexto =
                !texto ||
                datosBusqueda.includes(texto);

            const coincideCategoria =
                !categoria ||
                categoriaTarjeta === categoria;

            const coincideEstado =
                !estado ||
                estadoTarjeta === estado;

            const mostrar =
                coincideTexto &&
                coincideCategoria &&
                coincideEstado;

            tarjeta.toggle(mostrar);

            if (mostrar) {
                cantidadVisible++;
            }
        });

        $('#cantidadResultados').text(
            cantidadVisible
        );

        sinResultados.prop(
            'hidden',
            cantidadVisible !== 0
        );

        $('#contenedorPaquetes').toggle(
            cantidadVisible !== 0
        );
    }

    buscador.on('input', aplicarFiltros);

    filtroCategoria.on(
        'change',
        aplicarFiltros
    );

    filtroEstado.on(
        'change',
        aplicarFiltros
    );

    $('#limpiarFiltros').on('click', function () {
        buscador.val('');
        filtroCategoria.val('');
        filtroEstado.val('');

        aplicarFiltros();

        buscador.trigger('focus');
    });

    /*
    |--------------------------------------------------------------------------
    | Funciones para el modal
    |--------------------------------------------------------------------------
    */

    function obtenerPaquete(id) {
        return paquetes.find(function (paquete) {
            return Number(paquete.id) === Number(id);
        });
    }

    function formatearFecha(fecha) {
        if (!fecha) {
            return 'Fecha pendiente';
        }

        const partes = String(fecha)
            .substring(0, 10)
            .split('-');

        if (partes.length !== 3) {
            return fecha;
        }

        return `${partes[2]}/${partes[1]}/${partes[0]}`;
    }

    function formatearPrecio(valor, moneda) {
        const cantidad = Number(valor || 0);

        return `${moneda || 'USD'} ${cantidad.toLocaleString(
            'es-EC',
            {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        )}`;
    }

    function llenarLista(
        selector,
        elementos,
        mensajeVacio
    ) {
        const lista = $(selector);
        lista.empty();

        if (
            !Array.isArray(elementos) ||
            elementos.length === 0
        ) {
            lista.append(
                $('<li>').text(mensajeVacio)
            );

            return;
        }

        elementos.forEach(function (elemento) {
            lista.append(
                $('<li>').text(elemento)
            );
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Vista rápida
    |--------------------------------------------------------------------------
    */

    $('.btn-vista-rapida').on('click', function () {
        const id = $(this).data('paquete-id');
        const paquete = obtenerPaquete(id);

        if (!paquete) {
            Swal.fire({
                icon: 'error',
                title: 'Paquete no encontrado',
                text: 'No se pudo cargar la información del paquete.',
                confirmButtonText: 'Cerrar',
                confirmButtonColor: '#093D77'
            });

            return;
        }

        const nombre =
            paquete.nombre_paquete ||
            paquete.pais ||
            'Paquete turístico';

        const origen =
            paquete.ciudad_salida ||
            'Salida pendiente';

        const destino = [
            paquete.ciudad_destino,
            paquete.pais
        ]
            .filter(Boolean)
            .join(', ');

        const ruta =
            origen + ' → ' +
            (destino || 'Destino pendiente');

        const fechas =
            formatearFecha(paquete.fecha_salida) +
            ' - ' +
            formatearFecha(paquete.fecha_regreso);

        const duracion =
            `${paquete.dias || 0} días / ` +
            `${paquete.noches || 0} noches`;

        const precioAplicado =
            paquete.precio_promocional ||
            paquete.precio;

        const imagen = paquete.imagen
            ? `${window.rutaAlmacenamiento}/${paquete.imagen}`
            : window.imagenPaquetePredeterminada;

        $('#modalCategoria').text(
            paquete.categoria ||
            'Sin categoría'
        );

        $('#tituloModalPaquete').text(nombre);

        $('#modalDescripcion').text(
            paquete.descripcion_corta ||
            paquete.descripcion ||
            paquete.etiqueta ||
            'Sin descripción disponible.'
        );

        $('#modalRuta').text(ruta);
        $('#modalFechas').text(fechas);
        $('#modalDuracion').text(duracion);

        $('#modalPrecio').text(
            formatearPrecio(
                precioAplicado,
                paquete.moneda
            )
        );

        $('#modalImagen')
            .attr('src', imagen)
            .attr('alt', nombre);

        llenarLista(
            '#modalIncluye',
            paquete.incluye,
            'No se especificaron servicios.'
        );

        llenarLista(
            '#modalNoIncluye',
            paquete.no_incluye,
            'No se especificaron exclusiones.'
        );

        $('#modalEditar').attr(
            'href',
            `${window.rutaEditarPaquete}/${paquete.id}`
        );

        const modalElemento =
            document.getElementById(
                'modalVistaPaquete'
            );

        const modal = bootstrap.Modal
            .getOrCreateInstance(modalElemento);

        modal.show();
    });

    /*
    |--------------------------------------------------------------------------
    | Confirmación de eliminación
    |--------------------------------------------------------------------------
    */

    $('.btn-eliminar-paquete').on(
        'click',
        function () {
            const boton = $(this);

            const formulario = boton.closest(
                '.formulario-eliminar-paquete'
            );

            const nombre =
                boton.data('nombre') ||
                'este paquete';

            Swal.fire({
                icon: 'warning',
                title: '¿Eliminar el paquete?',
                html:
                    'Se eliminará <strong>' +
                    $('<div>').text(nombre).html() +
                    '</strong>.<br>' +
                    'Esta acción no se puede deshacer.',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#B42318',
                cancelButtonColor: '#6B7280',
                reverseButtons: true
            }).then(function (resultado) {
                if (resultado.isConfirmed) {
                    formulario
                        .find('button')
                        .prop('disabled', true);

                    formulario[0].submit();
                }
            });
        }
    );
});