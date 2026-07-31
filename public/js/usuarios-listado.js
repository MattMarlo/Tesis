$(function () {
    const $tabla = $('#tablaUsuarios');
    const $filas = $tabla.find('tbody tr[data-estado]');
    const $buscador = $('#buscarUsuario');
    const $filtroEstado = $('#filtrarEstado');
    const $sinResultados = $('#sinResultados');

    function normalizarTexto(texto) {
        return String(texto || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .trim();
    }

    function filtrarUsuarios() {
        const busqueda = normalizarTexto($buscador.val());
        const estadoSeleccionado = $filtroEstado.val();
        let resultadosVisibles = 0;

        $filas.each(function () {
            const $fila = $(this);
            const contenido = normalizarTexto($fila.text());
            const estado = $fila.data('estado');

            const coincideBusqueda =
                !busqueda || contenido.includes(busqueda);

            const coincideEstado =
                !estadoSeleccionado || estado === estadoSeleccionado;

            const mostrar = coincideBusqueda && coincideEstado;

            $fila.toggle(mostrar);

            if (mostrar) {
                resultadosVisibles++;
            }
        });

        $sinResultados.toggle(
            $filas.length > 0 && resultadosVisibles === 0
        );

        $tabla.toggle(
            !($filas.length > 0 && resultadosVisibles === 0)
        );
    }

    $buscador.on('input', filtrarUsuarios);
    $filtroEstado.on('change', filtrarUsuarios);

    $('.accion-eliminar').on('click', function () {
        const $formulario = $(this).closest(
            '.formulario-eliminar-usuario'
        );

        const nombre = $formulario.data('nombre');

        Swal.fire({
            icon: 'warning',
            title: '¿Eliminar este usuario?',
            text: `${nombre} perderá el acceso al sistema. Esta acción no se puede deshacer.`,
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#C83D48',
            cancelButtonColor: '#6C7780',
            reverseButtons: true,
            focusCancel: true
        }).then(function (resultado) {
            if (resultado.isConfirmed) {
                Swal.fire({
                    title: 'Eliminando usuario',
                    text: 'Espera un momento...',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: function () {
                        Swal.showLoading();
                    }
                });

                $formulario.trigger('submit');
            }
        });
    });

    const mensajes = window.usuariosMensajes || {};

    if (mensajes.exito) {
        Swal.fire({
            icon: 'success',
            title: 'Proceso completado',
            text: mensajes.exito,
            confirmButtonText: 'Aceptar',
            confirmButtonColor: '#093D77'
        });
    }

    if (mensajes.error) {
        Swal.fire({
            icon: 'error',
            title: 'No se pudo completar la acción',
            text: mensajes.error,
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#093D77'
        });
    }
});