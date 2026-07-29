$(function () {
    const formulario = $('#formularioPaquete');

    if (!formulario.length) {
        return;
    }

    let formularioEnviado = false;
    let contadorItinerario =
        $('#listaItinerario .dia-itinerario').length;

    /*
    |--------------------------------------------------------------------------
    | Errores recibidos desde Laravel
    |--------------------------------------------------------------------------
    */

    const contenedorErrores = $('#erroresServidor');

    if (contenedorErrores.length) {
        try {
            const errores = JSON.parse(
                contenedorErrores.attr('data-errores') || '[]'
            );

            if (errores.length) {
                Swal.fire({
                    icon: 'error',
                    title: 'Revisa la información ingresada',
                    text: errores.join('\n'),
                    confirmButtonText: 'Corregir',
                    confirmButtonColor: '#093D77'
                });
            }
        } catch (error) {
            console.error(
                'No se pudieron mostrar los errores.',
                error
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Funciones para mostrar y retirar errores
    |--------------------------------------------------------------------------
    */

    function colocarError(campo, mensaje) {
        const contenedorCampo = campo.closest('.campo');

        let mensajeError = contenedorCampo
            .find('.mensaje-error')
            .first();

        if (!mensajeError.length) {
            mensajeError = $(
                '<small class="mensaje-error"></small>'
            );

            contenedorCampo.append(mensajeError);
        }

        campo.addClass('campo-error');

        mensajeError
            .text(mensaje)
            .addClass('visible');

        return false;
    }

    function limpiarError(campo) {
        campo.removeClass('campo-error');

        campo
            .closest('.campo')
            .find('.mensaje-error')
            .first()
            .text('')
            .removeClass('visible');

        return true;
    }

    function validarTexto(selector, mensaje) {
        const campo = $(selector);

        if (!$.trim(campo.val())) {
            return colocarError(campo, mensaje);
        }

        return limpiarError(campo);
    }

    function validarNumero(
        selector,
        mensaje,
        minimo = 0
    ) {
        const campo = $(selector);
        const valor = parseFloat(campo.val());

        if (
            campo.val() === '' ||
            Number.isNaN(valor) ||
            valor < minimo
        ) {
            return colocarError(campo, mensaje);
        }

        return limpiarError(campo);
    }

    /*
    |--------------------------------------------------------------------------
    | Contador de descripción
    |--------------------------------------------------------------------------
    */

    const descripcionCorta = $('#descripcion_corta');

    function actualizarContadorDescripcion() {
        const cantidad = descripcionCorta.val().length;

        $('#contadorDescripcion').text(
            cantidad + ' de 300 caracteres'
        );
    }

    descripcionCorta.on(
        'input',
        actualizarContadorDescripcion
    );

    actualizarContadorDescripcion();

    /*
    |--------------------------------------------------------------------------
    | Servicios incluidos y no incluidos
    |--------------------------------------------------------------------------
    */

    function crearCampoServicio(
        nombre,
        clase,
        ejemplo
    ) {
        return `
            <div class="item-lista">
                <input
                    type="text"
                    name="${nombre}[]"
                    class="form-control ${clase}"
                    placeholder="${ejemplo}"
                    maxlength="255"
                >

                <button
                    type="button"
                    class="btn-eliminar-item"
                    aria-label="Eliminar servicio"
                >
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        `;
    }

    $('#agregarIncluye').on('click', function () {
        $('#listaIncluye').append(
            crearCampoServicio(
                'incluye',
                'campo-incluye',
                'Ej. Traslado aeropuerto - hotel'
            )
        );

        $('#listaIncluye input')
            .last()
            .trigger('focus');
    });

    $('#agregarNoIncluye').on('click', function () {
        $('#listaNoIncluye').append(
            crearCampoServicio(
                'no_incluye',
                '',
                'Ej. Gastos personales'
            )
        );

        $('#listaNoIncluye input')
            .last()
            .trigger('focus');
    });

    $(document).on(
        'click',
        '.btn-eliminar-item',
        function () {
            const item = $(this).closest('.item-lista');
            const lista = item.parent();

            const esListaIncluye =
                lista.attr('id') === 'listaIncluye';

            if (
                esListaIncluye &&
                lista.find('.item-lista').length === 1
            ) {
                item.find('input').val('').trigger('focus');

                Swal.fire({
                    icon: 'info',
                    title: 'Servicio obligatorio',
                    text: 'El paquete debe tener al menos un servicio incluido.',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#093D77'
                });

                return;
            }

            Swal.fire({
                icon: 'question',
                title: '¿Eliminar este servicio?',
                text: 'El elemento se retirará del formulario.',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#093D77',
                cancelButtonColor: '#6B7280'
            }).then(function (resultado) {
                if (resultado.isConfirmed) {
                    item.remove();
                }
            });
        }
    );

    /*
    |--------------------------------------------------------------------------
    | Itinerario
    |--------------------------------------------------------------------------
    */

    function crearDiaItinerario(indice, numeroDia) {
        return `
            <div class="dia-itinerario">
                <div class="dia-cabecera">
                    <strong>
                        Día
                        <span class="numero-dia">
                            ${numeroDia}
                        </span>
                    </strong>

                    <button
                        type="button"
                        class="btn-eliminar-dia"
                    >
                        <i class="bi bi-trash"></i>
                        Eliminar
                    </button>
                </div>

                <div class="campos-grid">
                    <input
                        type="hidden"
                        name="itinerario[${indice}][dia]"
                        class="campo-dia"
                        value="${numeroDia}"
                    >

                    <div class="campo">
                        <label>
                            Título del día
                            <span>*</span>
                        </label>

                        <input
                            type="text"
                            name="itinerario[${indice}][titulo]"
                            class="form-control titulo-dia"
                            placeholder="Ej. Llegada y traslado al hotel"
                            maxlength="150"
                        >
                    </div>

                    <div class="campo campo-completo">
                        <label>
                            Actividades
                            <span>*</span>
                        </label>

                        <textarea
                            name="itinerario[${indice}][descripcion]"
                            class="form-control descripcion-dia"
                            rows="3"
                            placeholder="Describe las actividades planificadas"
                        ></textarea>
                    </div>
                </div>
            </div>
        `;
    }

    $('#agregarDia').on('click', function () {
        const numeroDia =
            $('#listaItinerario .dia-itinerario').length + 1;

        $('#listaItinerario').append(
            crearDiaItinerario(
                contadorItinerario,
                numeroDia
            )
        );

        contadorItinerario++;

        $('#listaItinerario .titulo-dia')
            .last()
            .trigger('focus');
    });

    $(document).on(
        'click',
        '.btn-eliminar-dia',
        function () {
            const bloqueDia =
                $(this).closest('.dia-itinerario');

            const totalDias =
                $('#listaItinerario .dia-itinerario').length;

            if (totalDias === 1) {
                Swal.fire({
                    icon: 'info',
                    title: 'Itinerario obligatorio',
                    text: 'El paquete debe tener al menos un día en el itinerario.',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#093D77'
                });

                return;
            }

            Swal.fire({
                icon: 'warning',
                title: '¿Eliminar este día?',
                text: 'Las actividades registradas también se eliminarán.',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#B42318',
                cancelButtonColor: '#6B7280'
            }).then(function (resultado) {
                if (resultado.isConfirmed) {
                    bloqueDia.remove();
                    organizarItinerario();
                }
            });
        }
    );

    function organizarItinerario() {
        $('#listaItinerario .dia-itinerario').each(
            function (indice) {
                const numeroDia = indice + 1;
                const bloque = $(this);

                bloque
                    .find('.numero-dia')
                    .text(numeroDia);

                bloque
                    .find('.campo-dia')
                    .val(numeroDia)
                    .attr(
                        'name',
                        `itinerario[${indice}][dia]`
                    );

                bloque
                    .find('.titulo-dia')
                    .attr(
                        'name',
                        `itinerario[${indice}][titulo]`
                    );

                bloque
                    .find('.descripcion-dia')
                    .attr(
                        'name',
                        `itinerario[${indice}][descripcion]`
                    );
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Vista previa y validación de imagen
    |--------------------------------------------------------------------------
    */

    $('#imagen').on('change', function () {
        const campoImagen = $(this);
        const archivo = this.files[0];

        if (!archivo) {
            return;
        }

        const tiposPermitidos = [
            'image/jpeg',
            'image/png',
            'image/webp'
        ];

        if (!tiposPermitidos.includes(archivo.type)) {
            this.value = '';

            colocarError(
                campoImagen,
                'Selecciona una imagen JPG, PNG o WEBP.'
            );

            Swal.fire({
                icon: 'error',
                title: 'Formato no permitido',
                text: 'Selecciona una imagen JPG, PNG o WEBP.',
                confirmButtonText: 'Corregir',
                confirmButtonColor: '#093D77'
            });

            return;
        }

        const limite = 5 * 1024 * 1024;

        if (archivo.size > limite) {
            this.value = '';

            colocarError(
                campoImagen,
                'La imagen no debe superar los 5 MB.'
            );

            Swal.fire({
                icon: 'error',
                title: 'La imagen es demasiado grande',
                text: 'La imagen no debe superar los 5 MB.',
                confirmButtonText: 'Corregir',
                confirmButtonColor: '#093D77'
            });

            return;
        }

        limpiarError(campoImagen);

        const lector = new FileReader();

        lector.onload = function (evento) {
            $('#vistaImagen')
                .attr('src', evento.target.result)
                .prop('hidden', false);

            $('#imagenVacia').hide();

            $('#contenedorVistaImagen')
                .addClass('con-imagen');
        };

        lector.readAsDataURL(archivo);
    });

    /*
    |--------------------------------------------------------------------------
    | Validación de fechas
    |--------------------------------------------------------------------------
    */

    function obtenerFechaActual() {
        const fecha = new Date();
        const anio = fecha.getFullYear();

        const mes = String(
            fecha.getMonth() + 1
        ).padStart(2, '0');

        const dia = String(
            fecha.getDate()
        ).padStart(2, '0');

        return `${anio}-${mes}-${dia}`;
    }

    function validarFechas() {
        const salida = $('#fecha_salida');
        const regreso = $('#fecha_regreso');

        const fechaSalida = salida.val();
        const fechaRegreso = regreso.val();
        const fechaActual = obtenerFechaActual();

        let valido = true;

        if (!fechaSalida) {
            colocarError(
                salida,
                'Selecciona la fecha de salida.'
            );

            valido = false;
        } else if (fechaSalida < fechaActual) {
            colocarError(
                salida,
                'La fecha de salida no puede ser anterior a hoy.'
            );

            valido = false;
        } else {
            limpiarError(salida);
        }

        if (!fechaRegreso) {
            colocarError(
                regreso,
                'Selecciona la fecha de regreso.'
            );

            valido = false;
        } else if (
            fechaSalida &&
            fechaRegreso < fechaSalida
        ) {
            colocarError(
                regreso,
                'La fecha de regreso no puede ser anterior a la salida.'
            );

            valido = false;
        } else {
            limpiarError(regreso);
        }

        return valido;
    }

    /*
    |--------------------------------------------------------------------------
    | Validación de duración
    |--------------------------------------------------------------------------
    */

    function validarDuracion() {
        const dias = $('#dias');
        const noches = $('#noches');

        const cantidadDias = parseInt(
            dias.val(),
            10
        );

        const cantidadNoches = parseInt(
            noches.val(),
            10
        );

        let valido = true;

        if (
            Number.isNaN(cantidadDias) ||
            cantidadDias < 1
        ) {
            colocarError(
                dias,
                'Ingresa una cantidad válida de días.'
            );

            valido = false;
        } else {
            limpiarError(dias);
        }

        if (
            Number.isNaN(cantidadNoches) ||
            cantidadNoches < 0
        ) {
            colocarError(
                noches,
                'Ingresa una cantidad válida de noches.'
            );

            valido = false;
        } else if (
            !Number.isNaN(cantidadDias) &&
            cantidadNoches > cantidadDias
        ) {
            colocarError(
                noches,
                'Las noches no pueden superar los días.'
            );

            valido = false;
        } else {
            limpiarError(noches);
        }

        return valido;
    }

    /*
    |--------------------------------------------------------------------------
    | Validación de precios
    |--------------------------------------------------------------------------
    */

    function validarPrecios() {
        const precio = $('#precio');
        const promocional = $('#precio_promocional');

        const precioNormal = parseFloat(
            precio.val()
        );

        const precioOferta = parseFloat(
            promocional.val()
        );

        let valido = validarNumero(
            '#precio',
            'Ingresa un precio mayor a cero.',
            0.01
        );

        if (promocional.val() !== '') {
            if (
                Number.isNaN(precioOferta) ||
                precioOferta <= 0
            ) {
                colocarError(
                    promocional,
                    'Ingresa un precio promocional válido.'
                );

                valido = false;
            } else if (
                !Number.isNaN(precioNormal) &&
                precioOferta >= precioNormal
            ) {
                colocarError(
                    promocional,
                    'El precio promocional debe ser menor al normal.'
                );

                valido = false;
            } else {
                limpiarError(promocional);
            }
        } else {
            limpiarError(promocional);
        }

        return valido;
    }

    /*
    |--------------------------------------------------------------------------
    | Validación de servicios
    |--------------------------------------------------------------------------
    */

    function validarServiciosIncluidos() {
        let cantidadValidos = 0;

        $('.campo-incluye').each(function () {
            if ($.trim($(this).val())) {
                cantidadValidos++;
            }
        });

        if (!cantidadValidos) {
            $('#errorIncluye')
                .text(
                    'Agrega al menos un servicio incluido.'
                )
                .addClass('visible');

            return false;
        }

        $('#errorIncluye')
            .text('')
            .removeClass('visible');

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Validación del itinerario
    |--------------------------------------------------------------------------
    */

    function validarItinerario() {
        let valido = true;

        $('#listaItinerario .dia-itinerario').each(
            function () {
                const bloque = $(this);
                const titulo = bloque.find('.titulo-dia');

                const descripcion =
                    bloque.find('.descripcion-dia');

                if (!$.trim(titulo.val())) {
                    colocarError(
                        titulo,
                        'Ingresa el título de este día.'
                    );

                    valido = false;
                } else {
                    limpiarError(titulo);
                }

                if (!$.trim(descripcion.val())) {
                    colocarError(
                        descripcion,
                        'Describe las actividades de este día.'
                    );

                    valido = false;
                } else {
                    limpiarError(descripcion);
                }
            }
        );

        if (!valido) {
            $('#errorItinerario')
                .text(
                    'Completa la información de todos los días.'
                )
                .addClass('visible');
        } else {
            $('#errorItinerario')
                .text('')
                .removeClass('visible');
        }

        return valido;
    }

    /*
    |--------------------------------------------------------------------------
    | Validación de imagen
    |--------------------------------------------------------------------------
    */

    function validarImagen() {
        const campo = $('#imagen');

        const existeImagenActual =
            $('#contenedorVistaImagen')
                .hasClass('con-imagen');

        if (
            !existeImagenActual &&
            !campo[0].files.length
        ) {
            return colocarError(
                campo,
                'Selecciona la imagen principal del paquete.'
            );
        }

        return limpiarError(campo);
    }

    /*
    |--------------------------------------------------------------------------
    | Retirar errores mientras se corrigen los campos
    |--------------------------------------------------------------------------
    */

    $(
        '#nombre_paquete, #etiqueta, #pais, ' +
        '#ciudad_destino, #ciudad_salida, ' +
        '#descripcion_corta, #descripcion'
    ).on('input blur', function () {
        const campo = $(this);

        if ($.trim(campo.val())) {
            limpiarError(campo);
        }
    });

    $('#categoria, #estado_publicacion')
        .on('change', function () {
            const campo = $(this);

            if (campo.val()) {
                limpiarError(campo);
            }
        });

    $(document).on(
        'input',
        '.titulo-dia, .descripcion-dia',
        function () {
            const campo = $(this);

            if ($.trim(campo.val())) {
                limpiarError(campo);
            }

            const itinerarioCompleto =
                $('#listaItinerario .titulo-dia')
                    .toArray()
                    .every(function (elemento) {
                        return $.trim(
                            $(elemento).val()
                        ) !== '';
                    }) &&
                $('#listaItinerario .descripcion-dia')
                    .toArray()
                    .every(function (elemento) {
                        return $.trim(
                            $(elemento).val()
                        ) !== '';
                    });

            if (itinerarioCompleto) {
                $('#errorItinerario')
                    .text('')
                    .removeClass('visible');
            }
        }
    );

    $(document).on(
        'input',
        '.campo-incluye',
        function () {
            const existeServicio =
                $('.campo-incluye')
                    .toArray()
                    .some(function (elemento) {
                        return $.trim(
                            $(elemento).val()
                        ) !== '';
                    });

            if (existeServicio) {
                $('#errorIncluye')
                    .text('')
                    .removeClass('visible');
            }
        }
    );

    $('#capacidad').on('input', function () {
        const campo = $(this);
        const capacidad = parseInt(campo.val(), 10);

        if (
            !Number.isNaN(capacidad) &&
            capacidad >= 1
        ) {
            limpiarError(campo);
        }
    });

    $('#fecha_salida, #fecha_regreso')
        .on('change', validarFechas);

    $('#dias, #noches')
        .on('input', validarDuracion);

    $('#precio, #precio_promocional')
        .on('input', validarPrecios);

    /*
    |--------------------------------------------------------------------------
    | Validación completa
    |--------------------------------------------------------------------------
    */

    function validarFormularioCompleto() {
        const resultados = [
            validarTexto(
                '#nombre_paquete',
                'Ingresa el nombre del paquete.'
            ),

            validarTexto(
                '#etiqueta',
                'Ingresa una etiqueta promocional.'
            ),

            validarTexto(
                '#categoria',
                'Selecciona una categoría.'
            ),

            validarTexto(
                '#descripcion_corta',
                'Ingresa una descripción corta.'
            ),

            validarTexto(
                '#descripcion',
                'Ingresa la descripción completa.'
            ),

            validarTexto(
                '#ciudad_salida',
                'Ingresa la ciudad de salida.'
            ),

            validarTexto(
                '#pais',
                'Ingresa el país de destino.'
            ),

            validarTexto(
                '#ciudad_destino',
                'Ingresa la ciudad de destino.'
            ),

            validarFechas(),
            validarDuracion(),
            validarPrecios(),

            validarNumero(
                '#capacidad',
                'Ingresa una capacidad mayor a cero.',
                1
            ),

            validarServiciosIncluidos(),
            validarItinerario(),
            validarImagen(),

            validarTexto(
                '#estado_publicacion',
                'Selecciona el estado de publicación.'
            )
        ];

        return resultados.every(function (resultado) {
            return resultado;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Envío del formulario
    |--------------------------------------------------------------------------
    */

    formulario.on('submit', function (evento) {
        organizarItinerario();

        if (!validarFormularioCompleto()) {
            evento.preventDefault();

            const primerError = $('.campo-error')
                .first();

            if (primerError.length) {
                $('html, body').animate(
                    {
                        scrollTop:
                            primerError.offset().top - 130
                    },
                    450
                );

                primerError.trigger('focus');
            }

            Swal.fire({
                icon: 'error',
                title: 'Formulario incompleto',
                text: 'Revisa los campos señalados antes de continuar.',
                confirmButtonText: 'Corregir',
                confirmButtonColor: '#093D77'
            });

            return;
        }

        formularioEnviado = true;

        $('.btn-guardar')
            .prop('disabled', true)
            .each(function () {
                const boton = $(this);

                boton
                    .find('.texto-boton')
                    .text(
                        boton.data('texto-carga')
                    );

                boton
                    .find('i')
                    .removeClass()
                    .addClass('bi bi-arrow-repeat');
            });
    });

    /*
    |--------------------------------------------------------------------------
    | Confirmación al cancelar
    |--------------------------------------------------------------------------
    */

    const datosIniciales = formulario.serialize();

    $('.btn-cancelar').on('click', function (evento) {
        const enlace = $(this).attr('href');

        const existenCambios =
            formulario.serialize() !== datosIniciales ||
            $('#imagen')[0].files.length > 0;

        if (!existenCambios || formularioEnviado) {
            return;
        }

        evento.preventDefault();

        Swal.fire({
            icon: 'warning',
            title: '¿Salir sin guardar?',
            text: 'Los cambios realizados se perderán.',
            showCancelButton: true,
            confirmButtonText: 'Sí, salir',
            cancelButtonText: 'Continuar editando',
            confirmButtonColor: '#B42318',
            cancelButtonColor: '#093D77'
        }).then(function (resultado) {
            if (resultado.isConfirmed) {
                window.location.href = enlace;
            }
        });
    });
});