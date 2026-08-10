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
    | Errores enviados por Laravel
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
                    confirmButtonColor: '#094c90'
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
    | Manejo visual de errores
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
                'Ej. Boleto aéreo de ida y regreso'
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
                item.find('input')
                    .val('')
                    .trigger('focus');

                Swal.fire({
                    icon: 'info',
                    title: 'Servicio obligatorio',
                    text: 'El paquete debe tener al menos un servicio incluido.',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#094c90'
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
                confirmButtonColor: '#094c90',
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
    | Itinerario y actividades
    |--------------------------------------------------------------------------
    */

    function generarUuid() {
        if (
            window.crypto &&
            typeof window.crypto.randomUUID === 'function'
        ) {
            return window.crypto.randomUUID();
        }

        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'
            .replace(/[xy]/g, function (caracter) {
                const aleatorio = Math.floor(
                    Math.random() * 16
                );

                const valor = caracter === 'x'
                    ? aleatorio
                    : (aleatorio & 0x3) | 0x8;

                return valor.toString(16);
            });
    }

    function opcionesTipoGestion() {
        return `
            <option value="">
                Selecciona el tipo de gestión
            </option>

            <option value="vuelo">
                Vuelo
            </option>

            <option value="alojamiento">
                Alojamiento
            </option>

            <option value="entrada">
                Entrada
            </option>

            <option value="guia">
                Guía
            </option>

            <option value="tren">
                Tren
            </option>

            <option value="traslado">
                Traslado
            </option>

            <option value="alimentacion">
                Alimentación
            </option>

            <option value="actividad_reservada">
                Actividad reservada
            </option>

            <option value="seguro">
                Seguro
            </option>

            <option value="otro">
                Otro servicio
            </option>
        `;
    }

    function crearActividad(
        indiceDia,
        indiceActividad
    ) {
        const uuid = generarUuid();

        return `
            <div class="actividad-itinerario">
                <div class="actividad-cabecera">
                    <strong>
                        Actividad
                        <span class="numero-actividad">
                            ${indiceActividad + 1}
                        </span>
                    </strong>

                    <button
                        type="button"
                        class="btn-eliminar-actividad"
                    >
                        <i class="bi bi-trash"></i>
                        Eliminar
                    </button>
                </div>

                <input
                    type="hidden"
                    name="itinerario[${indiceDia}][actividades][${indiceActividad}][uuid]"
                    class="actividad-uuid"
                    value="${uuid}"
                >

                <div class="campos-grid">
                    <div class="campo campo-completo">
                        <label>
                            Nombre de la actividad
                            <span>*</span>
                        </label>

                        <input
                            type="text"
                            name="itinerario[${indiceDia}][actividades][${indiceActividad}][nombre]"
                            class="form-control actividad-nombre"
                            placeholder="Ej. Traslado del aeropuerto al hotel"
                            maxlength="150"
                        >

                        <small class="mensaje-error"></small>
                    </div>

                    <div class="campo campo-completo">
                        <label>
                            Descripción
                        </label>

                        <textarea
                            name="itinerario[${indiceDia}][actividades][${indiceActividad}][descripcion]"
                            class="form-control actividad-descripcion"
                            rows="2"
                            maxlength="1000"
                            placeholder="Información adicional de la actividad"
                        ></textarea>
                    </div>

                    <div class="campo">
                        <label>
                            Hora de inicio
                        </label>

                        <input
                            type="time"
                            name="itinerario[${indiceDia}][actividades][${indiceActividad}][hora_inicio]"
                            class="form-control actividad-hora-inicio"
                        >

                        <small class="mensaje-error"></small>
                    </div>

                    <div class="campo">
                        <label>
                            Hora de finalización
                        </label>

                        <input
                            type="time"
                            name="itinerario[${indiceDia}][actividades][${indiceActividad}][hora_fin]"
                            class="form-control actividad-hora-fin"
                        >

                        <small class="mensaje-error"></small>
                    </div>

                    <div class="campo campo-completo">
                        <label>
                            Ubicación
                        </label>

                        <input
                            type="text"
                            name="itinerario[${indiceDia}][actividades][${indiceActividad}][ubicacion]"
                            class="form-control actividad-ubicacion"
                            placeholder="Ej. Aeropuerto Internacional Mariscal Sucre"
                            maxlength="180"
                        >
                    </div>

                    <div class="campo campo-completo campo-gestion">
                        <input
                            type="hidden"
                            name="itinerario[${indiceDia}][actividades][${indiceActividad}][requiere_gestion]"
                            class="actividad-requiere-gestion-oculto"
                            value="0"
                        >

                        <label class="opcion-check">
                            <input
                                type="checkbox"
                                name="itinerario[${indiceDia}][actividades][${indiceActividad}][requiere_gestion]"
                                class="actividad-requiere-gestion"
                                value="1"
                            >

                            <span>
                                Esta actividad requiere gestión
                                o preparación de la agencia
                            </span>
                        </label>
                    </div>

                    <div
                        class="campo campo-completo contenedor-tipo-gestion"
                        hidden
                    >
                        <label>
                            Tipo de gestión
                            <span>*</span>
                        </label>

                        <select
                            name="itinerario[${indiceDia}][actividades][${indiceActividad}][tipo_gestion]"
                            class="form-select actividad-tipo-gestion"
                            disabled
                        >
                            ${opcionesTipoGestion()}
                        </select>

                        <small class="mensaje-error"></small>
                    </div>
                </div>
            </div>
        `;
    }

    function crearDiaItinerario(
        indice,
        numeroDia
    ) {
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
                            Descripción general del día
                            <span>*</span>
                        </label>

                        <textarea
                            name="itinerario[${indice}][descripcion]"
                            class="form-control descripcion-dia"
                            rows="3"
                            placeholder="Describe el objetivo y la planificación general del día"
                        ></textarea>
                    </div>
                </div>

                <div class="actividades-dia">
                    <div class="actividades-cabecera">
                        <div>
                            <h4>
                                Actividades del día
                            </h4>

                            <p>
                                Los horarios son opcionales. Marca
                                únicamente las actividades que requieran
                                preparación o coordinación. Cada actividad
                                gestionable debe representar una sola
                                coordinación; separa, por ejemplo, el tren
                                y el traslado al hotel.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="btn-agregar btn-agregar-actividad"
                        >
                            <i class="bi bi-plus-lg"></i>
                            Agregar actividad
                        </button>
                    </div>

                    <div class="lista-actividades"></div>
                </div>
            </div>
        `;
    }

    $('#agregarDia').on('click', function () {
        const cantidadDiasPaquete = parseInt(
            $('#dias').val(),
            10
        );

        const cantidadDiasItinerario =
            $('#listaItinerario .dia-itinerario').length;

        if (
            !Number.isNaN(cantidadDiasPaquete) &&
            cantidadDiasItinerario >= cantidadDiasPaquete
        ) {
            Swal.fire({
                icon: 'info',
                title: 'Duración alcanzada',
                text: 'No puedes agregar más días que la duración del paquete.',
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#094c90'
            });

            return;
        }

        const numeroDia = cantidadDiasItinerario + 1;

        $('#listaItinerario').append(
            crearDiaItinerario(
                contadorItinerario,
                numeroDia
            )
        );

        contadorItinerario++;

        organizarItinerario();

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
                    confirmButtonColor: '#094c90'
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
                confirmButtonColor: '#90091d',
                cancelButtonColor: '#6B7280'
            }).then(function (resultado) {
                if (resultado.isConfirmed) {
                    bloqueDia.remove();
                    organizarItinerario();
                }
            });
        }
    );

    $(document).on(
        'click',
        '.btn-agregar-actividad',
        function () {
            const bloqueDia = $(this)
                .closest('.dia-itinerario');

            const indiceDia = bloqueDia.index();

            const listaActividades = bloqueDia
                .find('.lista-actividades');

            const indiceActividad = listaActividades
                .find('.actividad-itinerario')
                .length;

            listaActividades.append(
                crearActividad(
                    indiceDia,
                    indiceActividad
                )
            );

            organizarItinerario();

            listaActividades
                .find('.actividad-nombre')
                .last()
                .trigger('focus');
        }
    );

    $(document).on(
        'click',
        '.btn-eliminar-actividad',
        function () {
            const actividad = $(this)
                .closest('.actividad-itinerario');

            Swal.fire({
                icon: 'question',
                title: '¿Eliminar esta actividad?',
                text: 'La actividad se retirará del itinerario.',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#90091d',
                cancelButtonColor: '#6B7280'
            }).then(function (resultado) {
                if (resultado.isConfirmed) {
                    actividad.remove();
                    organizarItinerario();
                }
            });
        }
    );

    $(document).on(
        'change',
        '.actividad-requiere-gestion',
        function () {
            const checkbox = $(this);

            const actividad = checkbox
                .closest('.actividad-itinerario');

            const contenedor = actividad
                .find('.contenedor-tipo-gestion');

            const selector = actividad
                .find('.actividad-tipo-gestion');

            if (checkbox.is(':checked')) {
                contenedor.prop('hidden', false);
                selector.prop('disabled', false);
            } else {
                selector
                    .val('')
                    .prop('disabled', true);

                limpiarError(selector);

                contenedor.prop('hidden', true);
            }
        }
    );

    function organizarItinerario() {
        $('#listaItinerario .dia-itinerario').each(
            function (indiceDia) {
                const numeroDia = indiceDia + 1;
                const bloqueDia = $(this);

                bloqueDia
                    .find('.numero-dia')
                    .text(numeroDia);

                bloqueDia
                    .find('.campo-dia')
                    .val(numeroDia)
                    .attr(
                        'name',
                        `itinerario[${indiceDia}][dia]`
                    );

                bloqueDia
                    .find('.titulo-dia')
                    .attr(
                        'name',
                        `itinerario[${indiceDia}][titulo]`
                    );

                bloqueDia
                    .find('.descripcion-dia')
                    .attr(
                        'name',
                        `itinerario[${indiceDia}][descripcion]`
                    );

                bloqueDia
                    .find('.actividad-itinerario')
                    .each(function (indiceActividad) {
                        const actividad = $(this);

                        actividad
                            .find('.numero-actividad')
                            .text(indiceActividad + 1);

                        const prefijo =
                            `itinerario[${indiceDia}]` +
                            `[actividades][${indiceActividad}]`;

                        actividad
                            .find('.actividad-uuid')
                            .attr(
                                'name',
                                `${prefijo}[uuid]`
                            );

                        actividad
                            .find('.actividad-nombre')
                            .attr(
                                'name',
                                `${prefijo}[nombre]`
                            );

                        actividad
                            .find('.actividad-descripcion')
                            .attr(
                                'name',
                                `${prefijo}[descripcion]`
                            );

                        actividad
                            .find('.actividad-hora-inicio')
                            .attr(
                                'name',
                                `${prefijo}[hora_inicio]`
                            );

                        actividad
                            .find('.actividad-hora-fin')
                            .attr(
                                'name',
                                `${prefijo}[hora_fin]`
                            );

                        actividad
                            .find('.actividad-ubicacion')
                            .attr(
                                'name',
                                `${prefijo}[ubicacion]`
                            );

                        actividad
                            .find(
                                '.actividad-requiere-gestion-oculto'
                            )
                            .attr(
                                'name',
                                `${prefijo}[requiere_gestion]`
                            );

                        actividad
                            .find(
                                '.actividad-requiere-gestion'
                            )
                            .attr(
                                'name',
                                `${prefijo}[requiere_gestion]`
                            );

                        actividad
                            .find('.actividad-tipo-gestion')
                            .attr(
                                'name',
                                `${prefijo}[tipo_gestion]`
                            );
                    });
            }
        );
    }

    organizarItinerario();

    /*
    |--------------------------------------------------------------------------
    | Imagen
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
                confirmButtonColor: '#094c90'
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
                confirmButtonColor: '#094c90'
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
    | Fechas
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

    function validarSoloLetras(selector, mensaje) {
        const campo = $(selector);
        const valor = $.trim(campo.val());
        const patron = /^[\p{L}\s.'’-]+$/u;

        if (!valor || !patron.test(valor)) {
            return colocarError(campo, mensaje);
        }

        return limpiarError(campo);
    }

    function validarNombreComercial(selector, mensaje) {
        const campo = $(selector);
        const valor = $.trim(campo.val());

        if (valor && !/\p{L}/u.test(valor)) {
            return colocarError(campo, mensaje);
        }

        return limpiarError(campo);
    }

    /*
    |--------------------------------------------------------------------------
    | Duración
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
    | Precios
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
    | Servicios
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

        const cantidadDiasPaquete = parseInt(
            $('#dias').val(),
            10
        );

        const bloquesDias = $(
            '#listaItinerario .dia-itinerario'
        );

        if (!bloquesDias.length) {
            $('#errorItinerario')
                .text('Agrega al menos un día al itinerario.')
                .addClass('visible');

            return false;
        }

        if (
            !Number.isNaN(cantidadDiasPaquete) &&
            bloquesDias.length > cantidadDiasPaquete
        ) {
            valido = false;
        }

        bloquesDias.each(function () {
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
                    'Describe la planificación general del día.'
                );

                valido = false;
            } else {
                limpiarError(descripcion);
            }

            bloque
                .find('.actividad-itinerario')
                .each(function () {
                    const actividad = $(this);

                    const nombre = actividad
                        .find('.actividad-nombre');

                    const horaInicio = actividad
                        .find('.actividad-hora-inicio');

                    const horaFin = actividad
                        .find('.actividad-hora-fin');

                    const requiereGestion = actividad
                        .find('.actividad-requiere-gestion')
                        .is(':checked');

                    const tipoGestion = actividad
                        .find('.actividad-tipo-gestion');

                    if (!$.trim(nombre.val())) {
                        colocarError(
                            nombre,
                            'Ingresa el nombre de la actividad.'
                        );

                        valido = false;
                    } else {
                        limpiarError(nombre);
                    }

                    if (
                        horaInicio.val() &&
                        horaFin.val() &&
                        horaFin.val() <= horaInicio.val()
                    ) {
                        colocarError(
                            horaFin,
                            'La hora final debe ser posterior a la hora de inicio.'
                        );

                        valido = false;
                    } else {
                        limpiarError(horaFin);
                    }

                    if (
                        requiereGestion &&
                        !tipoGestion.val()
                    ) {
                        colocarError(
                            tipoGestion,
                            'Selecciona el tipo de gestión.'
                        );

                        valido = false;
                    } else {
                        limpiarError(tipoGestion);
                    }
                });
        });

        if (!valido) {
            $('#errorItinerario')
                .text(
                    'Revisa los días y actividades del itinerario.'
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
    | Imagen requerida
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
    | Validación en tiempo real
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
        '.titulo-dia, .descripcion-dia, .actividad-nombre',
        function () {
            const campo = $(this);

            if ($.trim(campo.val())) {
                limpiarError(campo);
            }
        }
    );

    $(document).on(
        'change input',
        '.actividad-hora-inicio, .actividad-hora-fin',
        function () {
            const actividad = $(this)
                .closest('.actividad-itinerario');

            const horaInicio = actividad
                .find('.actividad-hora-inicio');

            const horaFin = actividad
                .find('.actividad-hora-fin');

            if (
                horaInicio.val() &&
                horaFin.val() &&
                horaFin.val() <= horaInicio.val()
            ) {
                colocarError(
                    horaFin,
                    'La hora final debe ser posterior a la hora de inicio.'
                );
            } else {
                limpiarError(horaFin);
            }
        }
    );

    $(document).on(
        'change',
        '.actividad-tipo-gestion',
        function () {
            if ($(this).val()) {
                limpiarError($(this));
            }
        }
    );

    $(document).on(
        'input',
        '.campo-incluye',
        function () {
            const existeServicio = $('.campo-incluye')
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

    $('#fecha_salida').on('change', function () {
        $('#fecha_regreso').attr(
            'min',
            $(this).val() || obtenerFechaActual()
        );
    });

    $('#ciudad_salida, #pais, #ciudad_destino')
        .on('input blur', function () {
            validarSoloLetras(
                '#' + this.id,
                'Este campo solo puede contener letras.'
            );
        });

    $('#aerolinea, #hotel')
        .on('input blur', function () {
            validarNombreComercial(
                '#' + this.id,
                'Este campo debe incluir letras.'
            );
        });

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

            validarSoloLetras(
                '#ciudad_salida',
                'Ingresa una ciudad de salida válida.'
            ),

            validarSoloLetras(
                '#pais',
                'Ingresa un país de destino válido.'
            ),

            validarSoloLetras(
                '#ciudad_destino',
                'Ingresa una ciudad de destino válida.'
            ),

            validarNombreComercial(
                '#aerolinea',
                'La aerolínea debe incluir letras.'
            ),

            validarNombreComercial(
                '#hotel',
                'El hotel debe incluir letras.'
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
    | Envío
    |--------------------------------------------------------------------------
    */

    formulario.on('submit', function (evento) {
        organizarItinerario();

        if (!validarFormularioCompleto()) {
            evento.preventDefault();

            const primerError = $('.campo-error').first();

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
                confirmButtonColor: '#094c90'
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
            confirmButtonColor: '#90091d',
            cancelButtonColor: '#094c90'
        }).then(function (resultado) {
            if (resultado.isConfirmed) {
                window.location.href = enlace;
            }
        });
    });
});