document.addEventListener(
    'DOMContentLoaded',
    function () {
        const configuracion =
            window
                .configuracionReporteFinanciero ||
            {};

        const canvas =
            document.getElementById(
                'graficoIngresos'
            );

        if (
            canvas &&
            typeof Chart !== 'undefined'
        ) {
            const tipo =
                configuracion.tipo ||
                'mensual';

            new Chart(
                canvas.getContext('2d'),
                {
                    type: 'bar',

                    data: {
                        labels:
                            configuracion
                                .labels || [],

                        datasets: [
                            {
                                label:
                                    tipo === 'diario'
                                        ? 'Ingresos diarios'
                                        : 'Ingresos mensuales',

                                data:
                                    configuracion
                                        .datos || [],

                                backgroundColor:
                                    'rgba(58, 124, 165, 0.72)',

                                borderColor:
                                    '#093D77',

                                borderWidth: 1,

                                borderRadius: 5,

                                maxBarThickness: 42,
                            },
                        ],
                    },

                    options: {
                        responsive: true,

                        maintainAspectRatio:
                            false,

                        interaction: {
                            intersect: false,
                            mode: 'index',
                        },

                        plugins: {
                            legend: {
                                display: false,
                            },

                            tooltip: {
                                callbacks: {
                                    label:
                                        function (
                                            contexto
                                        ) {
                                            const valor =
                                                Number(
                                                    contexto
                                                        .parsed
                                                        .y || 0
                                                );

                                            return (
                                                'USD ' +
                                                valor
                                                    .toLocaleString(
                                                        'es-EC',
                                                        {
                                                            minimumFractionDigits:
                                                                2,
                                                            maximumFractionDigits:
                                                                2,
                                                        }
                                                    )
                                            );
                                        },
                                },
                            },
                        },

                        scales: {
                            x: {
                                grid: {
                                    display: false,
                                },

                                ticks: {
                                    color: '#65717E',
                                },
                            },

                            y: {
                                beginAtZero: true,

                                grid: {
                                    color:
                                        'rgba(219, 227, 234, 0.65)',
                                },

                                ticks: {
                                    color: '#65717E',

                                    callback:
                                        function (
                                            valor
                                        ) {
                                            return (
                                                'USD ' +
                                                Number(
                                                    valor
                                                ).toLocaleString(
                                                    'es-EC'
                                                )
                                            );
                                        },
                                },
                            },
                        },
                    },
                }
            );
        }

        const errores =
            configuracion.errores || {};

        const mensajes = [];

        Object.values(errores).forEach(
            function (grupo) {
                if (
                    Array.isArray(grupo) &&
                    grupo.length
                ) {
                    mensajes.push(
                        grupo[0]
                    );
                }
            }
        );

        if (
            mensajes.length &&
            typeof Swal !== 'undefined'
        ) {
            Swal.fire({
                icon: 'error',
                title:
                    'Revisa los filtros',
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
            configuracion.mensajeError &&
            typeof Swal !== 'undefined'
        ) {
            Swal.fire({
                icon: 'error',
                title:
                    'No se pudo cargar el reporte',
                text:
                    configuracion
                        .mensajeError,
                confirmButtonText:
                    'Entendido',
                confirmButtonColor:
                    '#093D77',
            });
        }
    }
);