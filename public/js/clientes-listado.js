$(function () {
    const $contenedor = $('#contenedorClientes');
    const $tarjetas = $contenedor.find(
        '.tarjeta-cliente[data-estado]'
    );

    const $buscador = $('#buscarCliente');
    const $filtro = $('#filtrarClientes');
    const $sinResultados = $('#clientesSinResultados');

    function normalizarTexto(texto) {
        return String(texto || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .trim();
    }

    function filtrarClientes() {
        const busqueda = normalizarTexto(
            $buscador.val()
        );

        const estadoSeleccionado = $filtro.val();
        let clientesVisibles = 0;

        $tarjetas.each(function () {
            const $tarjeta = $(this);

            const contenido = normalizarTexto(
                $tarjeta.attr('data-busqueda')
            );

            const estadoCliente =
                $tarjeta.data('estado');

            const coincideBusqueda =
                !busqueda ||
                contenido.includes(busqueda);

            const coincideEstado =
                !estadoSeleccionado ||
                estadoCliente === estadoSeleccionado;

            const mostrar =
                coincideBusqueda && coincideEstado;

            $tarjeta.toggle(mostrar);

            if (mostrar) {
                clientesVisibles++;
            }
        });

        const noHayResultados =
            $tarjetas.length > 0 &&
            clientesVisibles === 0;

        $sinResultados.toggle(noHayResultados);
    }

    $buscador.on('input', filtrarClientes);
    $filtro.on('change', filtrarClientes);

    $('.accion-cliente.eliminar').on(
        'click',
        function () {
            const $formulario = $(this).closest(
                '.formulario-eliminar-cliente'
            );

            const nombre =
                $formulario.data('nombre');

            Swal.fire({
                icon: 'warning',
                title: '¿Eliminar este cliente?',
                text:
                    `${nombre} será eliminado del sistema. ` +
                    'Esta acción no se puede deshacer.',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#962234',
                cancelButtonColor: '#6C7780',
                reverseButtons: true,
                focusCancel: true
            }).then(function (resultado) {
                if (!resultado.isConfirmed) {
                    return;
                }

                Swal.fire({
                    title: 'Eliminando cliente',
                    text: 'Espera un momento...',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: function () {
                        Swal.showLoading();
                    }
                });

                $formulario[0].submit();
            });
        }
    );

    const mensajes =
        window.mensajesListadoClientes || {};

    if (mensajes.exito) {
        Swal.fire({
            icon: 'success',
            title: 'Proceso completado',
            text: mensajes.exito,
            confirmButtonText: 'Aceptar',
            confirmButtonColor: '#094c90'
        });
    }

    if (mensajes.error) {
        Swal.fire({
            icon: 'error',
            title: 'No se pudo completar la acción',
            text: mensajes.error,
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#094c90'
        });
    }
});