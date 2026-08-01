$(function () {
    const configuracion =
        window.configuracionOperacionViaje || {};

    const base =
        configuracion.baseOperaciones ||
        '/operaciones';

    const vuelos =
        configuracion.vuelos || [];

    const alojamientos =
        configuracion.alojamientos || [];

    const guias =
        configuracion.guias || [];

    const datosPaquete =
        configuracion.datosPaquete || {};

    const modalVuelo = bootstrap.Modal
        .getOrCreateInstance(
            document.getElementById(
                'modalVuelo'
            )
        );

    const modalBoleto = bootstrap.Modal
        .getOrCreateInstance(
            document.getElementById(
                'modalBoleto'
            )
        );

    const modalAlojamiento = bootstrap.Modal
        .getOrCreateInstance(
            document.getElementById(
                'modalAlojamiento'
            )
        );

    const modalGuia = bootstrap.Modal
        .getOrCreateInstance(
            document.getElementById(
                'modalGuia'
            )
        );

    const $formVuelo =
        $('#formularioVuelo');

    const $formBoleto =
        $('#formularioBoleto');

    const $formAlojamiento =
        $('#formularioAlojamiento');

    const $formGuia =
        $('#formularioGuia');

    const accionesIniciales = {
        vuelo: $formVuelo.attr('action'),
        alojamiento:
            $formAlojamiento.attr('action'),
        guia: $formGuia.attr('action')
    };

    let enviando = false;

    function valorFechaHora(valor) {
        if (!valor) {
            return '';
        }

        const fecha = new Date(valor);

        if (Number.isNaN(fecha.getTime())) {
            return String(valor)
                .replace(' ', 'T')
                .slice(0, 16);
        }

        const dosDigitos = numero =>
            String(numero).padStart(2, '0');

        return (
            fecha.getFullYear() +
            '-' +
            dosDigitos(
                fecha.getMonth() + 1
            ) +
            '-' +
            dosDigitos(fecha.getDate()) +
            'T' +
            dosDigitos(fecha.getHours()) +
            ':' +
            dosDigitos(fecha.getMinutes())
        );
    }

    function valorFecha(valor) {
        if (!valor) {
            return '';
        }

        return String(valor).slice(0, 10);
    }

    function limpiarFormulario(
        $formulario
    ) {
        $formulario[0].reset();

        $formulario
            .find('.input-error')
            .removeClass('input-error');

        enviando = false;

        $formulario
            .find('button[type="submit"]')
            .prop('disabled', false);
    }

    function colocar(
        selector,
        valor
    ) {
        $(selector).val(
            valor === null ||
            valor === undefined
                ? ''
                : valor
        );
    }

    function fechaHoraInicial(fecha) {
        return fecha
            ? fecha + 'T00:00'
            : '';
    }

    function precargarVuelo(tipoTramo) {
        colocar(
            '#vueloAerolinea',
            datosPaquete.aerolinea
        );

        colocar(
            '#vueloMoneda',
            datosPaquete.moneda || 'USD'
        );

        if (tipoTramo === 'ida') {
            colocar(
                '#vueloCiudadOrigen',
                datosPaquete.ciudadSalida
            );

            colocar(
                '#vueloCiudadDestino',
                datosPaquete.ciudadDestino
            );

            colocar(
                '#vueloSalida',
                fechaHoraInicial(
                    datosPaquete.fechaSalida
                )
            );

            colocar('#vueloLlegada', '');
            return;
        }

        if (tipoTramo === 'regreso') {
            colocar(
                '#vueloCiudadOrigen',
                datosPaquete.ciudadDestino
            );

            colocar(
                '#vueloCiudadDestino',
                datosPaquete.ciudadSalida
            );

            colocar(
                '#vueloSalida',
                fechaHoraInicial(
                    datosPaquete.fechaRegreso
                )
            );

            colocar('#vueloLlegada', '');
            return;
        }

        colocar('#vueloCiudadOrigen', '');
        colocar('#vueloCiudadDestino', '');
        colocar('#vueloSalida', '');
        colocar('#vueloLlegada', '');
    }

    $('#btnNuevoVuelo').on(
        'click',
        function () {
            limpiarFormulario($formVuelo);

            $formVuelo.attr(
                'action',
                accionesIniciales.vuelo
            );

            $('#vueloMetodo').prop(
                'disabled',
                true
            );

            $('#tituloModalVuelo').text(
                'Agregar vuelo'
            );

            $('#vueloTipoTramo').val('ida');
            $('#vueloEstado').val('confirmado');

            precargarVuelo('ida');

            modalVuelo.show();
        }
    );

    $('#vueloTipoTramo').on(
        'change',
        function () {
            const creandoVuelo =
                $('#vueloMetodo').prop(
                    'disabled'
                );

            if (!creandoVuelo) {
                return;
            }

            precargarVuelo(
                $(this).val()
            );
        }
    );

    $(document).on(
        'click',
        '.btnEditarVuelo',
        function () {
            const id = Number(
                $(this).data('id')
            );

            const vuelo = vuelos.find(
                elemento =>
                    Number(elemento.id) === id
            );

            if (!vuelo) {
                return;
            }

            limpiarFormulario($formVuelo);

            $formVuelo.attr(
                'action',
                `${base}/vuelos/${id}`
            );

            $('#vueloMetodo').prop(
                'disabled',
                false
            );

            $('#tituloModalVuelo').text(
                'Editar vuelo'
            );

            colocar(
                '#vueloTipoTramo',
                vuelo.tipo_tramo
            );

            colocar(
                '#vueloAerolinea',
                vuelo.aerolinea
            );

            colocar(
                '#vueloNumero',
                vuelo.numero_vuelo
            );

            colocar(
                '#vueloEstado',
                vuelo.estado
            );

            colocar(
                '#vueloCiudadOrigen',
                vuelo.ciudad_origen
            );

            colocar(
                '#vueloAeropuertoOrigen',
                vuelo.aeropuerto_origen
            );

            colocar(
                '#vueloCiudadDestino',
                vuelo.ciudad_destino
            );

            colocar(
                '#vueloAeropuertoDestino',
                vuelo.aeropuerto_destino
            );

            colocar(
                '#vueloSalida',
                valorFechaHora(
                    vuelo.fecha_hora_salida
                )
            );

            colocar(
                '#vueloLlegada',
                valorFechaHora(
                    vuelo.fecha_hora_llegada
                )
            );

            colocar(
                '#vueloTerminalSalida',
                vuelo.terminal_salida
            );

            colocar(
                '#vueloTerminalLlegada',
                vuelo.terminal_llegada
            );

            colocar(
                '#vueloLocalizador',
                vuelo.localizador_reserva
            );

            colocar(
                '#vueloEquipaje',
                vuelo.equipaje_incluido
            );

            colocar(
                '#vueloProveedor',
                vuelo.proveedor
            );

            colocar(
                '#vueloFechaCompra',
                valorFecha(
                    vuelo.fecha_compra
                )
            );

            colocar(
                '#vueloCosto',
                vuelo.costo_total
            );

            colocar(
                '#vueloMoneda',
                vuelo.moneda || 'USD'
            );

            colocar(
                '#vueloObservaciones',
                vuelo.observaciones
            );

            modalVuelo.show();
        }
    );

    $('#btnNuevoAlojamiento').on(
        'click',
        function () {
            limpiarFormulario(
                $formAlojamiento
            );

            $formAlojamiento.attr(
                'action',
                accionesIniciales.alojamiento
            );

            $('#alojamientoMetodo').prop(
                'disabled',
                true
            );

            $('#tituloModalAlojamiento').text(
                'Agregar alojamiento'
            );

            $('#alojamientoEstado').val(
                'confirmado'
            );

            $('#alojamientoCantidad').val(1);

            colocar(
                '#alojamientoHotel',
                datosPaquete.hotel
            );

            colocar(
                '#alojamientoCiudad',
                datosPaquete.ciudadDestino
            );

            colocar(
                '#alojamientoPais',
                datosPaquete.paisDestino
            );

            colocar(
                '#alojamientoEntrada',
                fechaHoraInicial(
                    datosPaquete.fechaSalida
                )
            );

            colocar(
                '#alojamientoSalida',
                fechaHoraInicial(
                    datosPaquete.fechaRegreso
                )
            );

            colocar(
                '#alojamientoMoneda',
                datosPaquete.moneda || 'USD'
            );

            modalAlojamiento.show();
        }
    );

    $(document).on(
        'click',
        '.btnEditarAlojamiento',
        function () {
            const id = Number(
                $(this).data('id')
            );

            const alojamiento =
                alojamientos.find(
                    elemento =>
                        Number(elemento.id) ===
                        id
                );

            if (!alojamiento) {
                return;
            }

            limpiarFormulario(
                $formAlojamiento
            );

            $formAlojamiento.attr(
                'action',
                `${base}/alojamientos/${id}`
            );

            $('#alojamientoMetodo').prop(
                'disabled',
                false
            );

            $('#tituloModalAlojamiento').text(
                'Editar alojamiento'
            );

            colocar(
                '#alojamientoHotel',
                alojamiento.nombre_hotel
            );

            colocar(
                '#alojamientoEstado',
                alojamiento.estado
            );

            colocar(
                '#alojamientoCiudad',
                alojamiento.ciudad
            );

            colocar(
                '#alojamientoPais',
                alojamiento.pais
            );

            colocar(
                '#alojamientoDireccion',
                alojamiento.direccion
            );

            colocar(
                '#alojamientoEntrada',
                valorFechaHora(
                    alojamiento
                        .fecha_hora_entrada
                )
            );

            colocar(
                '#alojamientoSalida',
                valorFechaHora(
                    alojamiento
                        .fecha_hora_salida
                )
            );

            colocar(
                '#alojamientoConfirmacion',
                alojamiento
                    .codigo_confirmacion
            );

            colocar(
                '#alojamientoTipoHabitacion',
                alojamiento.tipo_habitacion
            );

            colocar(
                '#alojamientoCantidad',
                alojamiento
                    .cantidad_habitaciones
            );

            colocar(
                '#alojamientoAlimentacion',
                alojamiento
                    .alimentacion_incluida
            );

            colocar(
                '#alojamientoDistribucion',
                alojamiento
                    .distribucion_habitaciones
            );

            colocar(
                '#alojamientoTelefono',
                alojamiento.telefono_hotel
            );

            colocar(
                '#alojamientoCorreo',
                alojamiento.correo_hotel
            );

            colocar(
                '#alojamientoProveedor',
                alojamiento.proveedor
            );

            colocar(
                '#alojamientoFechaCompra',
                valorFecha(
                    alojamiento.fecha_compra
                )
            );

            colocar(
                '#alojamientoCosto',
                alojamiento.costo_total
            );

            colocar(
                '#alojamientoMoneda',
                alojamiento.moneda || 'USD'
            );

            colocar(
                '#alojamientoObservaciones',
                alojamiento.observaciones
            );

            modalAlojamiento.show();
        }
    );

    function obtenerServiciosGuia() {
        const servicios = Array.isArray(
            datosPaquete.incluye
        )
            ? datosPaquete.incluye
            : [];

        const palabrasRelacionadas = [
            'guía',
            'guia',
            'tour',
            'recorrido',
            'excursión',
            'excursion',
            'visita',
            'traslado'
        ];

        return servicios
            .filter(servicio => {
                const texto = String(
                    servicio
                ).toLowerCase();

                return palabrasRelacionadas.some(
                    palabra =>
                        texto.includes(palabra)
                );
            })
            .join('\n');
    }

    $('#btnNuevoGuia').on(
        'click',
        function () {
            limpiarFormulario($formGuia);

            $formGuia.attr(
                'action',
                accionesIniciales.guia
            );

            $('#guiaMetodo').prop(
                'disabled',
                true
            );

            $('#tituloModalGuia').text(
                'Agregar guía'
            );

            $('#guiaEstado').val(
                'confirmado'
            );

            colocar(
                '#guiaCiudad',
                datosPaquete.ciudadDestino
            );

            colocar(
                '#guiaFechaInicio',
                datosPaquete.fechaSalida
            );

            colocar(
                '#guiaFechaFin',
                datosPaquete.fechaRegreso
            );

            colocar(
                '#guiaMoneda',
                datosPaquete.moneda || 'USD'
            );

            colocar(
                '#guiaServicios',
                obtenerServiciosGuia()
            );

            modalGuia.show();
        }
    );

    $(document).on(
        'click',
        '.btnEditarGuia',
        function () {
            const id = Number(
                $(this).data('id')
            );

            const guia = guias.find(
                elemento =>
                    Number(elemento.id) === id
            );

            if (!guia) {
                return;
            }

            limpiarFormulario($formGuia);

            $formGuia.attr(
                'action',
                `${base}/guias/${id}`
            );

            $('#guiaMetodo').prop(
                'disabled',
                false
            );

            $('#tituloModalGuia').text(
                'Editar guía'
            );

            colocar(
                '#guiaNombre',
                guia.nombre_completo
            );

            colocar(
                '#guiaEstado',
                guia.estado
            );

            colocar(
                '#guiaEmpresa',
                guia.empresa
            );

            colocar(
                '#guiaCiudad',
                guia.ciudad_servicio
            );

            colocar(
                '#guiaTelefono',
                guia.telefono
            );

            colocar(
                '#guiaCorreo',
                guia.correo
            );

            colocar(
                '#guiaIdiomas',
                guia.idiomas
            );

            colocar(
                '#guiaContactoEmergencia',
                guia.contacto_emergencia
            );

            colocar(
                '#guiaFechaInicio',
                valorFecha(guia.fecha_inicio)
            );

            colocar(
                '#guiaFechaFin',
                valorFecha(guia.fecha_fin)
            );

            colocar(
                '#guiaPuntoEncuentro',
                guia.punto_encuentro
            );

            colocar(
                '#guiaHoraEncuentro',
                valorFechaHora(
                    guia.fecha_hora_encuentro
                )
            );

            colocar(
                '#guiaCosto',
                guia.costo_total
            );

            colocar(
                '#guiaMoneda',
                guia.moneda || 'USD'
            );

            colocar(
                '#guiaServicios',
                guia.servicios_incluidos
            );

            colocar(
                '#guiaObservaciones',
                guia.observaciones
            );

            modalGuia.show();
        }
    );

    $(document).on(
        'click',
        '.btnGestionarBoleto',
        function () {
            const vueloId = Number(
                $(this).data('vuelo-id')
            );

            const clienteId = Number(
                $(this).data('cliente-id')
            );

            const vuelo = vuelos.find(
                elemento =>
                    Number(elemento.id) ===
                    vueloId
            );

            const boleto = (
                vuelo?.boletos || []
            ).find(
                elemento =>
                    Number(
                        elemento.cliente_id
                    ) === clienteId
            );

            limpiarFormulario($formBoleto);

            $formBoleto.attr(
                'action',
                `${base}/vuelos/${vueloId}/boletos`
            );

            $('#boletoClienteId').val(
                clienteId
            );

            const nombreViajero =
                $(this)
                    .closest('tr')
                    .find('td')
                    .first()
                    .text()
                    .trim();

            $('#boletoNombreViajero').text(
                nombreViajero
            );

            $('#tituloModalBoleto').text(
                boleto
                    ? 'Editar boleto'
                    : 'Asignar boleto'
            );

            colocar(
                '#boletoNumero',
                boleto?.numero_boleto
            );

            colocar(
                '#boletoAsiento',
                boleto?.asiento
            );

            colocar(
                '#boletoClase',
                boleto?.clase
            );

            colocar(
                '#boletoEstado',
                boleto?.estado_emision ||
                'pendiente'
            );

            colocar(
                '#boletoObservaciones',
                boleto?.observaciones
            );

            $('#boletoArchivoActual')
                .toggleClass(
                    'oculto',
                    !boleto?.archivo_boleto
                )
                .text(
                    boleto?.archivo_boleto
                        ? 'Ya existe un archivo guardado. Selecciona otro únicamente si deseas reemplazarlo.'
                        : ''
                );

            modalBoleto.show();
        }
    );

    function validarFormulario(
        $formulario
    ) {
        let valido = true;

        $formulario
            .find('[required]')
            .each(function () {
                const $campo = $(this);

                const vacio =
                    !String(
                        $campo.val() || ''
                    ).trim();

                $campo.toggleClass(
                    'input-error',
                    vacio
                );

                if (vacio) {
                    valido = false;
                }
            });

        return valido;
    }

    function nombreFormulario(
        formulario
    ) {
        const nombres = {
            formularioVuelo: 'el vuelo',
            formularioBoleto: 'el boleto',
            formularioAlojamiento:
                'el alojamiento',
            formularioGuia: 'el guía'
        };

        return nombres[formulario.id] ||
            'la información';
    }

    $(
        '#formularioVuelo, ' +
        '#formularioBoleto, ' +
        '#formularioAlojamiento, ' +
        '#formularioGuia'
    ).on(
        'submit',
        function (evento) {
            evento.preventDefault();

            if (enviando) {
                return;
            }

            const $formulario = $(this);

            if (
                !validarFormulario(
                    $formulario
                )
            ) {
                Swal.fire({
                    icon: 'error',
                    title:
                        'Revisa la información',
                    text:
                        'Completa los campos obligatorios.',
                    confirmButtonText:
                        'Corregir',
                    confirmButtonColor:
                        '#093D77'
                });

                return;
            }

            if (
                this.id ===
                'formularioVuelo'
            ) {
                    const estado =
                        $('#vueloEstado').val();

                    const numeroVuelo =
                        $.trim(
                            $('#vueloNumero').val()
                        );

                    const ciudadOrigen =
                        $.trim(
                            $('#vueloCiudadOrigen').val()
                        ).toLowerCase();

                    const ciudadDestino =
                        $.trim(
                            $('#vueloCiudadDestino').val()
                        ).toLowerCase();

                    $('#vueloNumero').removeClass(
                        'input-error'
                    );

                    $('#vueloCiudadOrigen, ' +
                        '#vueloCiudadDestino'
                    ).removeClass('input-error');

                    if (
                        estado === 'confirmado' &&
                        !numeroVuelo
                    ) {
                        $('#vueloNumero')
                            .addClass('input-error')
                            .trigger('focus');

                        Swal.fire({
                            icon: 'error',
                            title: 'Falta el número del vuelo',
                            text:
                                'Ingresa el número del vuelo cuando está confirmado.',
                            confirmButtonText: 'Corregir',
                            confirmButtonColor: '#093D77'
                        });

                        return;
                    }

                    if (
                        ciudadOrigen &&
                        ciudadDestino &&
                        ciudadOrigen === ciudadDestino
                    ) {
                        $('#vueloCiudadOrigen, ' +
                            '#vueloCiudadDestino'
                        ).addClass('input-error');

                        $('#vueloCiudadDestino')
                            .trigger('focus');

                        Swal.fire({
                            icon: 'error',
                            title: 'Revisa las ciudades',
                            text:
                                'La ciudad de origen y la ciudad de destino deben ser diferentes.',
                            confirmButtonText: 'Corregir',
                            confirmButtonColor: '#093D77'
                        });

                        return;
                    }

                    const salida =
                        new Date(
                            $('#vueloSalida').val()
                        );

                    const llegada =
                        new Date(
                            $('#vueloLlegada').val()
                        );

                    if (llegada <= salida) {
                        $('#vueloSalida, #vueloLlegada')
                            .addClass('input-error');

                        Swal.fire({
                            icon: 'error',
                            title: 'Fechas incorrectas',
                            text:
                                'La llegada debe ser posterior a la salida.',
                            confirmButtonText: 'Corregir',
                            confirmButtonColor: '#093D77'
                        });

                        return;
                    }

                    $('#vueloSalida, #vueloLlegada')
                        .removeClass('input-error');
            }

            if (
                this.id ===
                'formularioAlojamiento'
            ) {
                const estado =
                    $('#alojamientoEstado').val();

                const codigoConfirmacion =
                    $.trim(
                        $('#alojamientoConfirmacion')
                            .val()
                    );

                const cantidadHabitaciones =
                    Number(
                        $('#alojamientoCantidad')
                            .val()
                    );

                $('#alojamientoConfirmacion')
                    .removeClass('input-error');

                $('#alojamientoCantidad')
                    .removeClass('input-error');

                if (
                    estado === 'confirmado' &&
                    !codigoConfirmacion
                ) {
                    $('#alojamientoConfirmacion')
                        .addClass('input-error')
                        .trigger('focus');

                    Swal.fire({
                        icon: 'error',
                        title:
                            'Falta el código de confirmación',
                        text:
                            'Ingresa el código cuando el alojamiento está confirmado.',
                        confirmButtonText: 'Corregir',
                        confirmButtonColor: '#093D77'
                    });

                    return;
                }

                if (
                    !Number.isInteger(
                        cantidadHabitaciones
                    ) ||
                    cantidadHabitaciones < 1 ||
                    cantidadHabitaciones > 100
                ) {
                    $('#alojamientoCantidad')
                        .addClass('input-error')
                        .trigger('focus');

                    Swal.fire({
                        icon: 'error',
                        title:
                            'Cantidad de habitaciones incorrecta',
                        text:
                            'Ingresa una cantidad entre 1 y 100 habitaciones.',
                        confirmButtonText: 'Corregir',
                        confirmButtonColor: '#093D77'
                    });

                    return;
                }

                const entrada =
                    new Date(
                        $('#alojamientoEntrada')
                            .val()
                    );

                const salida =
                    new Date(
                        $('#alojamientoSalida')
                            .val()
                    );

                if (salida <= entrada) {
                    $('#alojamientoEntrada, ' +
                        '#alojamientoSalida'
                    ).addClass('input-error');

                    Swal.fire({
                        icon: 'error',
                        title: 'Fechas incorrectas',
                        text:
                            'La salida del hotel debe ser posterior a la entrada.',
                        confirmButtonText: 'Corregir',
                        confirmButtonColor: '#093D77'
                    });

                    return;
                }

                $('#alojamientoEntrada, ' +
                    '#alojamientoSalida'
                ).removeClass('input-error');
            }

            if (
                this.id ===
                'formularioGuia'
            ) {
                const nombre =
                    $.trim(
                        $('#guiaNombre').val()
                    );

                const telefono =
                    $.trim(
                        $('#guiaTelefono').val()
                    );

                const fechaInicio =
                    $('#guiaFechaInicio').val();

                const fechaFin =
                    $('#guiaFechaFin').val();

                const formatoNombre =
                    /^[\p{L}\s.'-]+$/u;

                const formatoTelefono =
                    /^\+?[0-9\s\-()]{7,20}$/;

                $('#guiaNombre, #guiaTelefono, ' +
                    '#guiaFechaInicio, #guiaFechaFin'
                ).removeClass('input-error');

                if (
                    !formatoNombre.test(nombre)
                ) {
                    $('#guiaNombre')
                        .addClass('input-error')
                        .trigger('focus');

                    Swal.fire({
                        icon: 'error',
                        title: 'Nombre incorrecto',
                        text:
                            'El nombre del guía solo puede contener letras, espacios, puntos, apóstrofes y guiones.',
                        confirmButtonText: 'Corregir',
                        confirmButtonColor: '#093D77'
                    });

                    return;
                }

                if (
                    !formatoTelefono.test(telefono)
                ) {
                    $('#guiaTelefono')
                        .addClass('input-error')
                        .trigger('focus');

                    Swal.fire({
                        icon: 'error',
                        title: 'Teléfono incorrecto',
                        text:
                            'Ingresa un número de teléfono válido.',
                        confirmButtonText: 'Corregir',
                        confirmButtonColor: '#093D77'
                    });

                    return;
                }

                if (
                    fechaInicio &&
                    fechaFin &&
                    fechaFin < fechaInicio
                ) {
                    $('#guiaFechaInicio, ' +
                        '#guiaFechaFin'
                    ).addClass('input-error');

                    $('#guiaFechaFin')
                        .trigger('focus');

                    Swal.fire({
                        icon: 'error',
                        title: 'Fechas incorrectas',
                        text:
                            'La fecha de finalización no puede ser anterior a la fecha de inicio.',
                        confirmButtonText: 'Corregir',
                        confirmButtonColor: '#093D77'
                    });

                    return;
                }
            }

            if (
                this.id ===
                'formularioBoleto'
            ) {
                const estado =
                    $('#boletoEstado').val();

                const numeroBoleto =
                    $.trim(
                        $('#boletoNumero').val()
                    );

                const archivo =
                    $('#boletoArchivo')[0]
                        .files[0];

                $('#boletoNumero, #boletoArchivo')
                    .removeClass('input-error');

                if (
                    estado === 'emitido' &&
                    !numeroBoleto
                ) {
                    $('#boletoNumero')
                        .addClass('input-error')
                        .trigger('focus');

                    Swal.fire({
                        icon: 'error',
                        title:
                            'Falta el número de boleto',
                        text:
                            'Un boleto emitido debe tener su número registrado.',
                        confirmButtonText: 'Corregir',
                        confirmButtonColor: '#093D77'
                    });

                    return;
                }

                if (archivo) {
                    const extensionesPermitidas = [
                        'pdf',
                        'jpg',
                        'jpeg',
                        'png'
                    ];

                    const extension =
                        archivo.name
                            .split('.')
                            .pop()
                            .toLowerCase();

                    const tamanoMaximo =
                        5 * 1024 * 1024;

                    if (
                        !extensionesPermitidas.includes(
                            extension
                        )
                    ) {
                        $('#boletoArchivo')
                            .addClass('input-error');

                        Swal.fire({
                            icon: 'error',
                            title:
                                'Archivo no permitido',
                            text:
                                'El boleto debe estar en formato PDF, JPG, JPEG o PNG.',
                            confirmButtonText:
                                'Corregir',
                            confirmButtonColor:
                                '#093D77'
                        });

                        return;
                    }

                    if (
                        archivo.size > tamanoMaximo
                    ) {
                        $('#boletoArchivo')
                            .addClass('input-error');

                        Swal.fire({
                            icon: 'error',
                            title:
                                'Archivo demasiado grande',
                            text:
                                'El archivo del boleto no puede superar los 5 MB.',
                            confirmButtonText:
                                'Corregir',
                            confirmButtonColor:
                                '#093D77'
                        });

                        return;
                    }
                }
            }

            const elemento =
                nombreFormulario(this);

            Swal.fire({
                icon: 'question',
                title:
                    '¿Guardar la información?',
                text:
                    `Se guardará ${elemento} en el expediente.`,
                showCancelButton: true,
                confirmButtonText:
                    'Sí, guardar',
                cancelButtonText:
                    'Cancelar',
                confirmButtonColor:
                    '#093D77',
                cancelButtonColor:
                    '#65717E',
                reverseButtons: true
            }).then(function (resultado) {
                if (!resultado.isConfirmed) {
                    return;
                }

                enviando = true;

                $formulario
                    .find(
                        'button[type="submit"]'
                    )
                    .prop('disabled', true);

                $formulario[0].submit();
            });
        }
    );

    $('#formularioEstadoOperacion').on(
        'submit',
        function (evento) {
            evento.preventDefault();

            const formulario = this;
            const estado =
                $('#estado').val();

            Swal.fire({
                icon:
                    estado === 'completo'
                        ? 'warning'
                        : 'question',
                title:
                    estado === 'completo'
                        ? '¿Marcar expediente como completo?'
                        : '¿Guardar el estado?',
                text:
                    estado === 'completo'
                        ? 'Confirma que revisaste vuelos, boletos, alojamiento y guía cuando corresponda.'
                        : 'Se actualizará el estado de preparación.',
                showCancelButton: true,
                confirmButtonText:
                    'Sí, guardar',
                cancelButtonText:
                    'Cancelar',
                confirmButtonColor:
                    '#093D77',
                cancelButtonColor:
                    '#65717E',
                reverseButtons: true
            }).then(function (resultado) {
                if (resultado.isConfirmed) {
                    formulario.submit();
                }
            });
        }
    );

    $(document).on(
        'click',
        '.btnEliminarExpediente',
        function () {
            const formulario =
                $(this).closest('form')[0];

            const tipo =
                $(formulario).data('tipo') ||
                'registro';

            Swal.fire({
                icon: 'warning',
                title: `¿Eliminar ${tipo}?`,
                text:
                    'Esta acción retirará la información del expediente.',
                showCancelButton: true,
                confirmButtonText:
                    'Sí, eliminar',
                cancelButtonText:
                    'Cancelar',
                confirmButtonColor:
                    '#C53B45',
                cancelButtonColor:
                    '#65717E',
                reverseButtons: true
            }).then(function (resultado) {
                if (resultado.isConfirmed) {
                    formulario.submit();
                }
            });
        }
    );

    $('.campo-expediente input[name="moneda"]')
        .on('input', function () {
            this.value =
                this.value
                    .toUpperCase()
                    .replace(/[^A-Z]/g, '')
                    .slice(0, 3);
        });

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
            title: 'Revisa la información',
            text: mensajes.join('\n'),
            confirmButtonText: 'Corregir',
            confirmButtonColor: '#093D77'
        });
    } else if (
        configuracion.mensajeError
    ) {
        Swal.fire({
            icon: 'error',
            title:
                'No se pudo completar la acción',
            text:
                configuracion.mensajeError,
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#093D77'
        });
    } else if (
        configuracion.mensajeExito
    ) {
        Swal.fire({
            icon: 'success',
            title: 'Proceso completado',
            text:
                configuracion.mensajeExito,
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#093D77'
        });
    }
});