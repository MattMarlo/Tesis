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

    const viajerosReserva =
        configuracion.viajerosReserva || [];

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

    const modalViajero = bootstrap.Modal
        .getOrCreateInstance(
            document.getElementById('modalViajero')
        );

    const $formVuelo =
        $('#formularioVuelo');

    const $formBoleto =
        $('#formularioBoleto');

    const $formAlojamiento =
        $('#formularioAlojamiento');

    const $formGuia =
        $('#formularioGuia');

    const $formViajero = $('#formularioViajero');

    const accionesIniciales = {
        vuelo: $formVuelo.attr('action'),
        alojamiento:
            $formAlojamiento.attr('action'),
        guia: $formGuia.attr('action'),
        viajero: $formViajero.attr('action')
    };

    let enviando = false;

    let contextoVueloActivo = false;

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

    function ocultarContextoVuelo() {
        contextoVueloActivo = false;

        colocar('#vueloTareaId', '');

        $('#vueloContextoTarea').prop(
            'hidden',
            true
        );

        $('#vueloContextoNombre').text(
            'Vuelo'
        );

        $('#vueloContextoProgramacion').text(
            ''
        );
    }

    function separarRutaVuelo(ubicacion) {
        const texto = String(
            ubicacion || ''
        ).trim();

        if (!texto) {
            return {
                origen: '',
                destino: ''
            };
        }

        const partes = texto
            .split(/\s+(?:-|–|—|→)\s+/)
            .map(parte => parte.trim())
            .filter(Boolean);

        if (partes.length < 2) {
            return {
                origen: '',
                destino: ''
            };
        }

        return {
            origen: partes[0],
            destino: partes
                .slice(1)
                .join(' - ')
        };
    }

    function fechaHoraDeTarea(
        fecha,
        hora
    ) {
        if (!fecha || !hora) {
            return '';
        }

        return `${fecha}T${String(hora).slice(0, 5)}`;
    }

    function ajustarLlegadaVuelo(
        salida,
        llegada
    ) {
        if (!salida || !llegada) {
            return llegada || '';
        }

        const fechaSalida = new Date(salida);
        const fechaLlegada = new Date(llegada);

        if (
            Number.isNaN(fechaSalida.getTime()) ||
            Number.isNaN(fechaLlegada.getTime())
        ) {
            return llegada;
        }

        if (fechaLlegada <= fechaSalida) {
            fechaLlegada.setDate(
                fechaLlegada.getDate() + 1
            );

            return valorFechaHora(fechaLlegada);
        }

        return llegada;
    }

    function resolverTramoContextual(
        origen,
        destino
    ) {
        const normalizar = valor =>
            String(valor || '')
                .trim()
                .toLocaleLowerCase('es');

        const ciudadSalida = normalizar(
            datosPaquete.ciudadSalida
        );

        const origenNormalizado = normalizar(
            origen
        );

        const destinoNormalizado = normalizar(
            destino
        );

        if (
            ciudadSalida &&
            destinoNormalizado === ciudadSalida
        ) {
            return 'regreso';
        }

        if (
            ciudadSalida &&
            origenNormalizado === ciudadSalida
        ) {
            return 'ida';
        }

        return 'conexion';
    }

    function prepararFormularioNuevoVuelo() {
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
        $('#vueloEstado').val('pendiente');

        ocultarContextoVuelo();
    }

    function abrirVueloGeneral() {
        prepararFormularioNuevoVuelo();

        $('#vueloEstado').val('confirmado');

        precargarVuelo('ida');

        modalVuelo.show();
    }

    function abrirVueloDesdeTarea(boton) {
        const $boton = $(boton);

        prepararFormularioNuevoVuelo();

        contextoVueloActivo = true;

        const tareaId = String(
            $boton.data('tarea-id') || ''
        );

        const nombre = String(
            $boton.data('nombre') ||
            'Vuelo del itinerario'
        );

        const descripcion = String(
            $boton.data('descripcion') || ''
        );

        const fecha = String(
            $boton.data('fecha') || ''
        );

        const horaInicio = String(
            $boton.data('hora-inicio') || ''
        );

        const horaFin = String(
            $boton.data('hora-fin') || ''
        );

        const ubicacion = String(
            $boton.data('ubicacion') || ''
        );

        const ruta = separarRutaVuelo(
            ubicacion
        );

        const salida = fechaHoraDeTarea(
            fecha,
            horaInicio
        );

        const llegadaBase = fechaHoraDeTarea(
            fecha,
            horaFin
        );

        const llegada = ajustarLlegadaVuelo(
            salida,
            llegadaBase
        );

        const tramo = resolverTramoContextual(
            ruta.origen,
            ruta.destino
        );

        colocar('#vueloTareaId', tareaId);

        $('#vueloContextoTarea').prop(
            'hidden',
            false
        );

        $('#vueloContextoNombre').text(
            nombre
        );

        const programacion = [
            fecha,
            horaInicio && horaFin
                ? `${horaInicio} – ${horaFin}`
                : horaInicio
                    ? `Desde las ${horaInicio}`
                    : horaFin
                        ? `Hasta las ${horaFin}`
                        : '',
            ubicacion
        ].filter(Boolean);

        $('#vueloContextoProgramacion').text(
            programacion.join(' · ')
        );

        $('#tituloModalVuelo').text(
            'Gestionar vuelo del itinerario'
        );

        colocar('#vueloTipoTramo', tramo);
        colocar('#vueloCiudadOrigen', ruta.origen);
        colocar('#vueloCiudadDestino', ruta.destino);
        colocar('#vueloSalida', salida);
        colocar('#vueloLlegada', llegada);
        colocar(
            '#vueloMoneda',
            datosPaquete.moneda || 'USD'
        );
        colocar(
            '#vueloObservaciones',
            descripcion
        );

        modalVuelo.show();
    }

    function prepararFormularioNuevoAlojamiento() {
        limpiarFormulario($formAlojamiento);
        $formAlojamiento.attr('action', accionesIniciales.alojamiento);
        $('#alojamientoMetodo').prop('disabled', true);
        $('#tituloModalAlojamiento').text('Agregar alojamiento');
        colocar('#alojamientoTareaId', '');
        $('#alojamientoContextoTarea').prop('hidden', true);
        $('#alojamientoEstado').val('pendiente');
        $('#alojamientoCantidad').val(1);
    }

    function abrirAlojamientoDesdeTarea(boton) {
        const $boton = $(boton);
        prepararFormularioNuevoAlojamiento();

        const fecha = String($boton.data('fecha') || '');
        const horaInicio = String($boton.data('hora-inicio') || '');
        const horaFin = String($boton.data('hora-fin') || '');
        const ubicacion = String($boton.data('ubicacion') || '');
        const nombre = String($boton.data('nombre') || 'Alojamiento del itinerario');
        const descripcion = String($boton.data('descripcion') || '');
        const entrada = fechaHoraDeTarea(fecha, horaInicio) || fechaHoraInicial(fecha);
        let salida = fechaHoraDeTarea(fecha, horaFin);

        if (!salida && entrada) {
            const fechaSalida = new Date(entrada);
            fechaSalida.setDate(fechaSalida.getDate() + 1);
            salida = valorFechaHora(fechaSalida);
        } else {
            salida = ajustarLlegadaVuelo(entrada, salida);
        }

        colocar('#alojamientoTareaId', $boton.data('tarea-id'));
        colocar('#alojamientoHotel', datosPaquete.hotel || nombre);
        colocar('#alojamientoCiudad', ubicacion || datosPaquete.ciudadDestino);
        colocar('#alojamientoPais', datosPaquete.paisDestino);
        colocar('#alojamientoEntrada', entrada);
        colocar('#alojamientoSalida', salida);
        colocar('#alojamientoMoneda', datosPaquete.moneda || 'USD');
        colocar('#alojamientoObservaciones', descripcion);

        $('#tituloModalAlojamiento').text('Gestionar alojamiento del itinerario');
        $('#alojamientoContextoTarea').prop('hidden', false);
        $('#alojamientoContextoNombre').text(nombre);
        $('#alojamientoContextoProgramacion').text(
            [fecha, horaInicio, horaFin, ubicacion].filter(Boolean).join(' \u00b7 ')
        );
        modalAlojamiento.show();
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
            abrirVueloGeneral();
        }
    );

    $(document).on(
        'click',
        '.btn-gestion-especializada' +
            '[data-tipo-gestion="vuelo"]',
        function (evento) {
            evento.preventDefault();
            evento.stopImmediatePropagation();

            abrirVueloDesdeTarea(this);
        }
    );

    $('#vueloTipoTramo').on(
        'change',
        function () {
            const creandoVuelo =
                $('#vueloMetodo').prop(
                    'disabled'
                );

            if (
                !creandoVuelo ||
                contextoVueloActivo
            ) {
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

            ocultarContextoVuelo();

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
            prepararFormularioNuevoAlojamiento();
            $('#alojamientoEstado').val('confirmado');

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
        '.btn-gestion-especializada[data-tipo-gestion="alojamiento"]',
        function (evento) {
            evento.preventDefault();
            evento.stopImmediatePropagation();
            abrirAlojamientoDesdeTarea(this);
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

            colocar('#alojamientoTareaId', '');
            $('#alojamientoContextoTarea').prop('hidden', true);

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

            const personaId = Number($(this).data('persona-id'));
            const personaTipo = String($(this).data('persona-tipo'));

            const vuelo = vuelos.find(
                elemento =>
                    Number(elemento.id) ===
                    vueloId
            );

            const boleto = (
                vuelo?.boletos || []
            ).find(
                elemento => personaTipo === 'viajero'
                    ? Number(elemento.viajero_reserva_id) === personaId
                    : Number(elemento.cliente_id) === personaId
            );

            limpiarFormulario($formBoleto);

            $formBoleto.attr(
                'action',
                `${base}/vuelos/${vueloId}/boletos`
            );

            $('#boletoClienteId').val(personaTipo === 'cliente' ? personaId : '');
            $('#boletoViajeroReservaId').val(personaTipo === 'viajero' ? personaId : '');

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

    $('#btnNuevoViajero').on('click', function () {
        limpiarFormulario($formViajero);
        $formViajero.attr('action', accionesIniciales.viajero);
        $('#viajeroMetodo').prop('disabled', true);
        $('#tituloModalViajero').text('Agregar acompañante');
        modalViajero.show();
    });

    $(document).on('click', '.btnEditarViajero', function () {
        const viajeroId = Number($(this).data('viajero-id'));
        const viajero = viajerosReserva.find(
            elemento => Number(elemento.id) === viajeroId
        );
        if (!viajero) return;
        limpiarFormulario($formViajero);
        $formViajero.attr('action', `${base}/viajeros/${viajero.id}`);
        $('#viajeroMetodo').prop('disabled', false);
        $('#tituloModalViajero').text('Editar acompañante');
        colocar('#viajeroNombres', viajero.nombres);
        colocar('#viajeroApellidos', viajero.apellidos);
        colocar('#viajeroNacimiento', String(viajero.fecha_nacimiento || '').slice(0, 10));
        colocar('#viajeroTipoDocumento', viajero.tipo_documento);
        colocar('#viajeroDocumento', viajero.documento);
        modalViajero.show();
    });

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

        if ($formulario.is('#formularioViajero')) {
            const tipo = String($('#viajeroTipoDocumento').val() || '').trim();
            const documento = String($('#viajeroDocumento').val() || '').trim();
            const parejaValida = Boolean(tipo) === Boolean(documento);
            $('#viajeroTipoDocumento, #viajeroDocumento')
                .toggleClass('input-error', !parejaValida);
            valido = valido && parejaValida;
        }

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
            formularioGuia: 'el guía',
            formularioViajero: 'el viajero'
        };

        return nombres[formulario.id] ||
            'la información';
    }

    function validarFechasVuelo(mostrarAlerta = false) {
        const $salida = $('#vueloSalida');
        const $llegada = $('#vueloLlegada');
        const $mensaje = $('#vueloFechasError');
        const salida = $salida.val();
        const llegada = $llegada.val();

        const limiteInicio = datosPaquete.fechaSalida
            ? datosPaquete.fechaSalida + 'T00:00'
            : '';
        const limiteFin = datosPaquete.fechaRegreso
            ? datosPaquete.fechaRegreso + 'T23:59'
            : '';

        $salida.attr({ min: limiteInicio, max: limiteFin });
        $llegada.attr({ min: salida || limiteInicio, max: limiteFin });

        let mensaje = '';

        if (salida && llegada && llegada <= salida) {
            mensaje = 'La fecha y hora de llegada debe ser posterior a la salida.';
        } else if (
            limiteInicio &&
            ((salida && salida < limiteInicio) || (llegada && llegada < limiteInicio))
        ) {
            mensaje = 'Las fechas del vuelo no pueden ser anteriores al inicio del paquete.';
        } else if (
            limiteFin &&
            ((salida && salida > limiteFin) || (llegada && llegada > limiteFin))
        ) {
            mensaje = 'Las fechas del vuelo no pueden superar la fecha de regreso del paquete.';
        }

        const fechasInvalidas = Boolean(mensaje);

        $salida.add($llegada)
            .toggleClass('input-error', fechasInvalidas)
            .attr('aria-invalid', fechasInvalidas ? 'true' : 'false');
        $mensaje.text(mensaje).prop('hidden', !fechasInvalidas);
        $llegada.get(0)?.setCustomValidity(
            mensaje
        );

        if (fechasInvalidas && mostrarAlerta) {
            $llegada.trigger('focus');
        }

        return !fechasInvalidas;
    }

    function validarDatosBasicosVuelo() {
        const $aerolinea = $('#vueloAerolinea');
        const $fechaCompra = $('#vueloFechaCompra');
        const $costo = $('#vueloCosto');
        const $errorAerolinea = $('#vueloAerolineaError');
        const $errorCompra = $('#vueloFechaCompraError');
        const $errorCosto = $('#vueloCostoError');
        const aerolineaInvalida = $.trim($aerolinea.val()).length < 2;
        const hoy = new Date();
        const fechaMinima = new Date(
            hoy.getFullYear() - 1,
            hoy.getMonth(),
            hoy.getDate()
        );
        const formatoFecha = fecha => [
            fecha.getFullYear(),
            String(fecha.getMonth() + 1).padStart(2, '0'),
            String(fecha.getDate()).padStart(2, '0')
        ].join('-');
        const minimoCompra = formatoFecha(fechaMinima);
        const maximoCompra = formatoFecha(hoy);
        const fechaCompra = $fechaCompra.val();
        const costo = $costo.val();
        const costoInvalido = costo !== '' && (
            !Number.isFinite(Number(costo)) || Number(costo) < 0
        );
        let mensajeCompra = '';

        $fechaCompra.attr({ min: minimoCompra, max: maximoCompra });

        if (fechaCompra && fechaCompra < minimoCompra) {
            mensajeCompra = 'La fecha de compra no puede tener más de un año de antigüedad.';
        } else if (fechaCompra && fechaCompra > maximoCompra) {
            mensajeCompra = 'La fecha de compra no puede ser futura.';
        }

        $aerolinea
            .toggleClass('input-error', aerolineaInvalida)
            .attr('aria-invalid', aerolineaInvalida ? 'true' : 'false');
        $errorAerolinea.prop('hidden', !aerolineaInvalida);
        $fechaCompra
            .toggleClass('input-error', Boolean(mensajeCompra))
            .attr('aria-invalid', mensajeCompra ? 'true' : 'false');
        $errorCompra.text(mensajeCompra).prop('hidden', !mensajeCompra);
        $costo
            .toggleClass('input-error', costoInvalido)
            .attr('aria-invalid', costoInvalido ? 'true' : 'false');
        $errorCosto.prop('hidden', !costoInvalido);

        return !aerolineaInvalida && !mensajeCompra && !costoInvalido;
    }

    function mostrarValidacionCampoVuelo(selector, mensaje) {
        const $campo = $(selector);
        const idMensaje = $campo.attr('id') + 'Validacion';
        let $mensaje = $('#' + idMensaje);

        if (!$mensaje.length) {
            $mensaje = $('<small>', {
                id: idMensaje,
                class: 'mensaje-validacion-vuelo',
                role: 'alert',
                hidden: true
            }).insertAfter($campo);
        }

        $campo
            .toggleClass('input-error', Boolean(mensaje))
            .attr('aria-invalid', mensaje ? 'true' : 'false');
        $mensaje.text(mensaje).prop('hidden', !mensaje);

        return !mensaje;
    }

    function validarCamposVuelo() {
        const valores = {
            aerolinea: $.trim($('#vueloAerolinea').val()),
            numero: $.trim($('#vueloNumero').val()),
            origen: $.trim($('#vueloCiudadOrigen').val()),
            destino: $.trim($('#vueloCiudadDestino').val()),
            aeropuertoOrigen: $.trim($('#vueloAeropuertoOrigen').val()),
            aeropuertoDestino: $.trim($('#vueloAeropuertoDestino').val()),
            terminalSalida: $.trim($('#vueloTerminalSalida').val()),
            terminalLlegada: $.trim($('#vueloTerminalLlegada').val()),
            localizador: $.trim($('#vueloLocalizador').val()),
            equipaje: $.trim($('#vueloEquipaje').val()),
            proveedor: $.trim($('#vueloProveedor').val())
        };
        const estado = $('#vueloEstado').val();
        const formatoNombre = /^(?=(?:.*\p{L}){2})[\p{L}\p{N}\s.&'’-]+$/u;
        const formatoCiudad = /^[\p{L}][\p{L}\s.'’-]{1,119}$/u;
        const formatoAeropuerto = /^(?=(?:.*\p{L}){2})[\p{L}\p{N}\s.'’&(),\/-]{3,150}$/u;
        const formatoTerminal = /^[\p{L}\p{N}][\p{L}\p{N}\s.-]{1,49}$/u;
        let valido = true;

        valido = mostrarValidacionCampoVuelo(
            '#vueloAerolinea',
            formatoNombre.test(valores.aerolinea)
                ? ''
                : 'Ingresa una aerolínea válida con al menos dos letras.'
        ) && valido;
        valido = mostrarValidacionCampoVuelo(
            '#vueloNumero',
            !valores.numero && estado !== 'confirmado'
                ? ''
                : /^[A-Z0-9]{2,3}[\s-]?[0-9]{1,4}[A-Z]?$/i.test(valores.numero)
                    ? ''
                    : 'Usa un número de vuelo válido, por ejemplo LA 1447 o AV8374.'
        ) && valido;
        valido = mostrarValidacionCampoVuelo(
            '#vueloCiudadOrigen',
            formatoCiudad.test(valores.origen)
                ? ''
                : 'Ingresa una ciudad de origen válida.'
        ) && valido;
        valido = mostrarValidacionCampoVuelo(
            '#vueloCiudadDestino',
            !formatoCiudad.test(valores.destino)
                ? 'Ingresa una ciudad de destino válida.'
                : valores.origen.toLocaleLowerCase() === valores.destino.toLocaleLowerCase()
                    ? 'La ciudad de destino debe ser diferente del origen.'
                    : ''
        ) && valido;
        valido = mostrarValidacionCampoVuelo(
            '#vueloAeropuertoOrigen',
            !valores.aeropuertoOrigen || formatoAeropuerto.test(valores.aeropuertoOrigen)
                ? ''
                : 'Ingresa un aeropuerto válido con al menos tres caracteres.'
        ) && valido;
        valido = mostrarValidacionCampoVuelo(
            '#vueloAeropuertoDestino',
            !valores.aeropuertoDestino || formatoAeropuerto.test(valores.aeropuertoDestino)
                ? ''
                : 'Ingresa un aeropuerto válido con al menos tres caracteres.'
        ) && valido;

        ['terminalSalida', 'terminalLlegada'].forEach(function (campo) {
            const selector = campo === 'terminalSalida'
                ? '#vueloTerminalSalida'
                : '#vueloTerminalLlegada';
            valido = mostrarValidacionCampoVuelo(
                selector,
                !valores[campo] || formatoTerminal.test(valores[campo])
                    ? ''
                    : 'Ingresa una terminal válida de al menos dos caracteres.'
            ) && valido;
        });

        valido = mostrarValidacionCampoVuelo(
            '#vueloLocalizador',
            !valores.localizador || /^[A-Z0-9]{5,12}$/i.test(valores.localizador)
                ? ''
                : 'El localizador debe tener entre 5 y 12 letras o números.'
        ) && valido;
        valido = mostrarValidacionCampoVuelo(
            '#vueloEquipaje',
            !valores.equipaje || valores.equipaje.length >= 3
                ? ''
                : 'Describe el equipaje con al menos tres caracteres.'
        ) && valido;
        valido = mostrarValidacionCampoVuelo(
            '#vueloProveedor',
            !valores.proveedor || formatoNombre.test(valores.proveedor)
                ? ''
                : 'Ingresa un proveedor válido con al menos dos letras.'
        ) && valido;

        return valido;
    }

    function validarCamposBoleto() {
        const estado = $('#boletoEstado').val();
        const numero = $.trim($('#boletoNumero').val());
        const asiento = $.trim($('#boletoAsiento').val());
        const clase = $.trim($('#boletoClase').val());
        const observaciones = $.trim($('#boletoObservaciones').val());
        const archivo = $('#boletoArchivo')[0]?.files[0];
        let valido = true;
        let errorNumero = '';
        let errorArchivo = '';

        if (estado === 'emitido' && !numero) {
            errorNumero = 'Ingresa el número del boleto cuando está emitido.';
        } else if (
            numero &&
            !/^[A-Z0-9]+(?:-[A-Z0-9]+)*$/i.test(numero)
        ) {
            errorNumero = 'Usa al menos 3 caracteres entre letras y números; también puedes usar guiones.';
        } else if (numero && (numero.length < 3 || numero.length > 30)) {
            errorNumero = 'El número del boleto debe tener entre 3 y 30 caracteres.';
        }

        if (archivo) {
            const extension = archivo.name.split('.').pop().toLowerCase();
            const extensiones = ['pdf', 'jpg', 'jpeg', 'png'];

            if (!extensiones.includes(extension)) {
                errorArchivo = 'Selecciona un archivo PDF, JPG, JPEG o PNG.';
            } else if (archivo.size > 5 * 1024 * 1024) {
                errorArchivo = 'El archivo no puede superar los 5 MB.';
            }
        }

        valido = mostrarValidacionCampoVuelo(
            '#boletoNumero',
            errorNumero
        ) && valido;
        valido = mostrarValidacionCampoVuelo(
            '#boletoAsiento',
            !asiento || /^[0-9]{1,3}[A-Z]$/i.test(asiento)
                ? ''
                : 'Ingresa un asiento válido, por ejemplo 14A.'
        ) && valido;
        valido = mostrarValidacionCampoVuelo(
            '#boletoClase',
            !clase || (/^[\p{L}][\p{L}\s.'’-]+$/u.test(clase) && clase.length >= 2)
                ? ''
                : 'Ingresa una clase válida, por ejemplo Económica.'
        ) && valido;
        valido = mostrarValidacionCampoVuelo(
            '#boletoArchivo',
            errorArchivo
        ) && valido;
        valido = mostrarValidacionCampoVuelo(
            '#boletoObservaciones',
            !observaciones || observaciones.length >= 3
                ? ''
                : 'Las observaciones deben tener al menos tres caracteres.'
        ) && valido;

        return valido;
    }

    $('#vueloSalida, #vueloLlegada, #vueloAerolinea, #vueloFechaCompra, #vueloCosto').on(
        'input change',
        function () {
            validarFechasVuelo(false);
            validarDatosBasicosVuelo();
        }
    );

    $('#formularioVuelo').on(
        'input change',
        'input:not([type="date"]):not([type="datetime-local"]), select',
        validarCamposVuelo
    );

    $('#formularioBoleto').on(
        'input change',
        'input, select, textarea',
        function () {
            if (this.id === 'boletoAsiento') {
                this.value = this.value.toUpperCase();
            }

            validarCamposBoleto();
        }
    );

    $('#modalVuelo').on('shown.bs.modal', function () {
        validarFechasVuelo(false);
        validarDatosBasicosVuelo();
    });

    $(
        '#formularioVuelo, ' +
        '#formularioBoleto, ' +
        '#formularioAlojamiento, ' +
        '#formularioGuia, ' +
        '#formularioViajero'
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
                        '#094c90'
                });

                return;
            }

            if (
                this.id ===
                'formularioVuelo'
            ) {
                    if (!validarDatosBasicosVuelo()) {
                        $('#vueloAerolinea.input-error, #vueloFechaCompra.input-error, #vueloCosto.input-error')
                            .first()
                            .trigger('focus');
                        return;
                    }

                    if (!validarCamposVuelo()) {
                        $('#formularioVuelo .input-error')
                            .first()
                            .trigger('focus');
                        return;
                    }

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
                            confirmButtonColor: '#094c90'
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
                            confirmButtonColor: '#094c90'
                        });

                        return;
                    }

                    if (!validarFechasVuelo(true)) {
                        return;
                    }
            }

            if (
                this.id ===
                'formularioAlojamiento'
            ) {
                if (
                    typeof window.validarFormularioHospedaje === 'function' &&
                    !window.validarFormularioHospedaje(true)
                ) {
                    return;
                }
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
                        confirmButtonColor: '#094c90'
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
                        confirmButtonColor: '#094c90'
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
                        confirmButtonColor: '#094c90'
                    });

                    return;
                }
            }

            if (
                this.id ===
                'formularioBoleto'
            ) {
                if (!validarCamposBoleto()) {
                    $('#formularioBoleto .input-error')
                        .first()
                        .trigger('focus');
                    return;
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
                    '#094c90',
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
                    '#094c90',
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
                    '#962234',
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
            confirmButtonColor: '#094c90'
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
            confirmButtonColor: '#094c90'
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
            confirmButtonColor: '#094c90'
        });
    }
});
