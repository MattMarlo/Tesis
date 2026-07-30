document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const sidebar = document.getElementById('sidebar');
    const content = document.getElementById('content');
    const overlay = document.getElementById('overlay');
    const botonContraer = document.getElementById(
        'botonContraerMenu'
    );

    function actualizarIconoMenu() {
        if (!botonContraer) {
            return;
        }

        const icono = botonContraer.querySelector('i');

        if (!icono) {
            return;
        }

        const estaContraido =
            sidebar.classList.contains('collapsed');

        icono.className = estaContraido
            ? 'bi bi-chevron-right'
            : 'bi bi-chevron-left';

        botonContraer.setAttribute(
            'aria-label',
            estaContraido
                ? 'Expandir menú'
                : 'Contraer menú'
        );
    }

    window.toggleSidebar = function () {
        if (!sidebar || !content) {
            return;
        }

        sidebar.classList.toggle('collapsed');
        content.classList.toggle('collapsed');

        const estaContraido =
            sidebar.classList.contains('collapsed');

        localStorage.setItem(
            'menuPanelContraido',
            estaContraido ? 'si' : 'no'
        );

        actualizarIconoMenu();
    };

    window.openSidebar = function () {
        if (!sidebar || !overlay) {
            return;
        }

        sidebar.classList.add('active');
        overlay.classList.add('active');

        document.body.style.overflow = 'hidden';
    };

    window.closeSidebar = function () {
        if (!sidebar || !overlay) {
            return;
        }

        sidebar.classList.remove('active');
        overlay.classList.remove('active');

        document.body.style.overflow = '';
    };

    if (
        window.innerWidth > 768 &&
        localStorage.getItem('menuPanelContraido') === 'si'
    ) {
        sidebar.classList.add('collapsed');
        content.classList.add('collapsed');
    }

    actualizarIconoMenu();

    document
        .querySelectorAll('.sidebar .nav-link')
        .forEach(function (enlace) {
            enlace.addEventListener('click', function () {
                if (window.innerWidth <= 768) {
                    window.closeSidebar();
                }
            });
        });

    window.addEventListener('resize', function () {
        if (window.innerWidth > 768) {
            window.closeSidebar();
        }
    });

    const formularioCerrarSesion = document.getElementById(
        'formularioCerrarSesion'
    );

    if (formularioCerrarSesion) {
        formularioCerrarSesion.addEventListener(
            'submit',
            function (evento) {
                evento.preventDefault();

                Swal.fire({
                    icon: 'question',
                    title: '¿Cerrar sesión?',
                    text: 'Saldrás del panel administrativo.',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, cerrar sesión',
                    cancelButtonText: 'Cancelar',
                    reverseButtons: true
                }).then(function (resultado) {
                    if (resultado.isConfirmed) {
                        formularioCerrarSesion.submit();
                    }
                });
            }
        );
    }
});