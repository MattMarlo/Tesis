$(function () {
    const configuracion =
        window.configuracionPrerreservas ||
        {};

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
                    '#093D77',
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
                    '#C53B45',
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
                '#093D77',
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