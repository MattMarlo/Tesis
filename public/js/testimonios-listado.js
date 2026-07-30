$(document).ready(function () {
    'use strict';

    const $tarjetas = $('.tarjeta-testimonio-admin');
    const $cantidad = $('#cantidadTestimonios');
    const $sinResultados = $('#sinResultadosTestimonios');

    function normalizarTexto(texto) {
        return String(texto || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .trim();
    }

    function filtrarTestimonios() {
        const busqueda = normalizarTexto(
            $('#buscarTestimonio').val()
        );

        const estadoSeleccionado = normalizarTexto(
            $('#filtrarEstado').val()
        );

        let cantidadVisible = 0;

        $tarjetas.each(function () {
            const $tarjeta = $(this);

            const nombre = normalizarTexto(
                $tarjeta.data('nombre')
            );

            const destino = normalizarTexto(
                $tarjeta.data('destino')
            );

            const estado = normalizarTexto(
                $tarjeta.data('estado')
            );

            const coincideBusqueda =
                !busqueda ||
                nombre.includes(busqueda) ||
                destino.includes(busqueda);

            const coincideEstado =
                !estadoSeleccionado ||
                estado === estadoSeleccionado;

            const mostrar =
                coincideBusqueda && coincideEstado;

            $tarjeta.toggleClass(
                'testimonio-oculto-filtro',
                !mostrar
            );

            if (mostrar) {
                cantidadVisible++;
            }
        });

        $cantidad.text(cantidadVisible);

        if (
            cantidadVisible === 0 &&
            $tarjetas.length > 0
        ) {
            $sinResultados.addClass('visible');
        } else {
            $sinResultados.removeClass('visible');
        }
    }

    $('#buscarTestimonio').on('input', function () {
        filtrarTestimonios();
    });

    $('#filtrarEstado').on('change', function () {
        filtrarTestimonios();
    });

    $('#limpiarFiltrosTestimonios').on(
        'click',
        function () {
            $('#buscarTestimonio').val('');
            $('#filtrarEstado').val('');

            filtrarTestimonios();
        }
    );

    $('.formulario-eliminar-testimonio').on(
        'submit',
        function (evento) {
            evento.preventDefault();

            const formulario = this;
            const nombre = String(
                $(formulario).data('nombre') || ''
            );

            Swal.fire({
                icon: 'warning',
                title: '¿Eliminar testimonio?',
                html:
                    'Se eliminará el testimonio de:<br>' +
                    '<strong>' +
                    escaparHtml(nombre) +
                    '</strong><br><br>' +
                    'Esta acción también eliminará su fotografía.',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#d64545',
                reverseButtons: true,
                focusCancel: true
            }).then(function (resultado) {
                if (resultado.isConfirmed) {
                    formulario.submit();
                }
            });
        }
    );

    function escaparHtml(texto) {
        return $('<div>')
            .text(texto)
            .html();
    }

    filtrarTestimonios();
});