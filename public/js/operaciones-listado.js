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
        'click',
        '.btn-gestionar-operacion',
        function (evento) {
            const $enlace = $(this);

            const texto =
                $.trim(
                    $enlace.text()
                ).toLowerCase();

            const iniciaPreparacion =
                texto.includes(
                    'iniciar preparación'
                );

            if (!iniciaPreparacion) {
                return;
            }

            evento.preventDefault();

            const destino =
                $enlace.attr('href');

            Swal.fire({
                icon: 'question',
                title:
                    '¿Iniciar la preparación del viaje?',
                text:
                    'Se creará el expediente para registrar vuelos, boletos, alojamiento y guía cuando corresponda.',
                showCancelButton: true,
                confirmButtonText:
                    'Sí, iniciar',
                cancelButtonText:
                    'Cancelar',
                confirmButtonColor:
                    '#093D77',
                cancelButtonColor:
                    '#65717E',
                reverseButtons: true,
            }).then(
                function (resultado) {
                    if (
                        resultado.isConfirmed
                    ) {
                        window.location.href =
                            destino;
                    }
                }
            );
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
                '#093D77',
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
                '#093D77',
        });
    }
});