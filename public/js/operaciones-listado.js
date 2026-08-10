$(function () {
    const configuracion =
        window.configuracionOperaciones ||
        {};

    const $formularioFiltros =
        $('.filtros-operaciones');

    const $campoBuscar =
        $formularioFiltros.find(
            'input[name="buscar"]'
        );

    $formularioFiltros.on(
        'submit',
        function () {
            const busqueda =
                $.trim(
                    $campoBuscar.val()
                );

            $campoBuscar.val(busqueda);
        }
    );

    $(document).on(
        'submit',
        '.form-iniciar-operacion',
        function (evento) {
            evento.preventDefault();

            const formulario = this;

            Swal.fire({
                icon: 'question',
                title: '¿Iniciar la preparación del viaje?',
                text: 'Se creará el expediente para registrar vuelos, boletos, alojamiento y guía cuando corresponda.',
                showCancelButton: true,
                confirmButtonText: 'Sí, iniciar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#094c90',
                cancelButtonColor: '#65717E',
                reverseButtons: true,
            }).then(function (resultado) {
                if (resultado.isConfirmed) {
                    const accion = formulario.getAttribute('action');

                    if (
                        !accion ||
                        accion === 'undefined' ||
                        accion.endsWith('/undefined')
                    ) {
                        Swal.fire({
                            icon: 'error',
                            title: 'No se pudo iniciar la preparación',
                            text: 'La reserva no tiene una ruta válida. Recarga la página e inténtalo nuevamente.',
                            confirmButtonText: 'Entendido',
                            confirmButtonColor: '#094c90',
                        });

                        return;
                    }

                    HTMLFormElement.prototype.submit.call(formulario);
                }
            });
        }
    );

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
