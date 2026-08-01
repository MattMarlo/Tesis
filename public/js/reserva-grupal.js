$(function () {
    const $formulario = $('#formularioReservaGrupal');
    const $destino = $('#destino_id');
    const $selectorCliente = $('#selectorCliente');
    const $lista = $('#listaIntegrantes');
    const $responsable = $('#responsable_pago_id');

    const configuracion =
        window.configuracionReservaGrupal || {};

    const esEdicion =
        configuracion.modo === 'editar';

    let integrantes = [];
    let enviando = false;

    function mostrarError(campo, mensaje) {
        $('#' + campo)
            .toggleClass('input-error', Boolean(mensaje));

        $('#' + campo + 'Error')
            .text(mensaje || '')
            .toggle(Boolean(mensaje));

        return !mensaje;
    }

    function escapar(texto) {
        return $('<div>')
            .text(String(texto || ''))
            .html();
    }

    function crearFecha(valor) {
        if (!valor) {
            return null;
        }

        const partes = String(valor)
            .split('-')
            .map(Number);

        if (partes.length !== 3) {
            return null;
        }

        return new Date(
            partes[0],
            partes[1] - 1,
            partes[2]
        );
    }

    function formatearFecha(valor) {
        const fecha = crearFecha(valor);

        if (!fecha) {
            return 'Sin registrar';
        }

        return new Intl.DateTimeFormat(
            'es-EC',
            {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            }
        ).format(fecha);
    }

    function formatearDinero(valor, moneda) {
        const numero = Number(valor || 0);
        const codigo = moneda || 'USD';

        try {
            return new Intl.NumberFormat(
                'es-EC',
                {
                    style: 'currency',
                    currency: codigo,
                    minimumFractionDigits: 2
                }
            ).format(numero);
        } catch (error) {
            return `${codigo} ${numero.toFixed(2)}`;
        }
    }

    function calcularEdad(nacimientoTexto, viajeTexto) {
        const nacimiento = crearFecha(nacimientoTexto);
        const viaje = crearFecha(viajeTexto);

        if (!nacimiento || !viaje) {
            return null;
        }

        let edad =
            viaje.getFullYear() -
            nacimiento.getFullYear();

        const antesDelCumpleanos =
            viaje.getMonth() < nacimiento.getMonth() ||
            (
                viaje.getMonth() === nacimiento.getMonth() &&
                viaje.getDate() < nacimiento.getDate()
            );

        if (antesDelCumpleanos) {
            edad--;
        }

        return edad;
    }

    function determinarTarifa(edad) {
        if (edad < 2) {
            return {
                categoria: 'Infante',
                porcentaje: 0
            };
        }

        if (edad < 12) {
            return {
                categoria: 'Niño',
                porcentaje: 50
            };
        }

        if (edad > 60) {
            return {
                categoria: 'Adulto mayor',
                porcentaje: 50
            };
        }

        return {
            categoria: 'Adulto',
            porcentaje: 100
        };
    }

    function datosPaquete() {
        const $opcion =
            $destino.find('option:selected');

        if (!$destino.val()) {
            return null;
        }

        return {
            id: Number($destino.val()),
            nombre: $opcion.data('nombre'),
            origen: $opcion.data('origen'),
            destino:
                $opcion.data('destino') ||
                $opcion.data('pais'),
            fechaSalida:
                $opcion.data('fecha-salida'),
            fechaRegreso:
                $opcion.data('fecha-regreso'),
            precio: Number(
                $opcion.data('precio') || 0
            ),
            moneda:
                $opcion.data('moneda') || 'USD',
            capacidad: Number(
                $opcion.data('capacidad') || 0
            )
        };
    }

    function calcularIntegrantes() {
        const paquete = datosPaquete();

        integrantes = integrantes.map(function (integrante) {

            if (
                !paquete ||
                !integrante.fechaNacimiento ||
                paquete.precio <= 0
            ) {
                return {
                    ...integrante,
                    edad: null,
                    categoria: null,
                    porcentaje: null,
                    precioBase: null,
                    precioFinal: null
                };
            }

            const edad = calcularEdad(
                integrante.fechaNacimiento,
                paquete.fechaSalida
            );

            if (edad === null || edad < 0) {
                return {
                    ...integrante,
                    edad: null,
                    categoria: null,
                    porcentaje: null,
                    precioBase: null,
                    precioFinal: null
                };
            }

            const tarifa = determinarTarifa(edad);

            return {
                ...integrante,
                edad: edad,
                categoria: tarifa.categoria,
                porcentaje: tarifa.porcentaje,
                precioBase: paquete.precio,
                precioFinal:
                    paquete.precio *
                    (tarifa.porcentaje / 100)
            };
        });
    }

    function actualizarPaquete() {
        const paquete = datosPaquete();

        if (!paquete) {
            $('#resumenPaqueteGrupal').addClass(
                'oculto'
            );

            calcularIntegrantes();
            renderizarIntegrantes();
            return;
        }

        $('#grupoRuta').text(
            `${paquete.origen || 'Sin origen'} → ` +
            `${paquete.destino || 'Sin destino'}`
        );

        $('#grupoSalida').text(
            formatearFecha(paquete.fechaSalida)
        );

        $('#grupoRegreso').text(
            formatearFecha(paquete.fechaRegreso)
        );

        $('#grupoPrecio').text(
            formatearDinero(
                paquete.precio,
                paquete.moneda
            )
        );

        $('#grupoCapacidad').text(
            paquete.capacidad > 0
                ? `${paquete.capacidad} viajeros`
                : 'Sin configurar'
        );

        $('#resumenPaqueteGrupal').removeClass(
            'oculto'
        );

        mostrarError(
            'destino_id',
            paquete.fechaSalida &&
            paquete.precio > 0 &&
            paquete.capacidad > 0
                ? ''
                : 'El paquete no tiene fecha, precio o capacidad válida.'
        );

        calcularIntegrantes();
        renderizarIntegrantes();
    }

    function obtenerClienteSeleccionado() {
        const $opcion =
            $selectorCliente.find('option:selected');

        if (!$selectorCliente.val()) {
            return null;
        }

        return {
            id: Number($selectorCliente.val()),
            nombre: $opcion.data('nombre'),
            documento: $opcion.data('documento'),
            fechaNacimiento:
                $opcion.data('fecha-nacimiento'),
            completo:
                String($opcion.data('completo')) === '1',
            editarUrl: $opcion.data('editar-url'),
            esLider: false
        };
    }

    function agregarIntegrante(cliente, restaurando = false) {
        if (
            integrantes.some(
                integrante =>
                    integrante.id === cliente.id
            )
        ) {
            if (!restaurando) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Cliente ya agregado',
                    text: 'Este cliente ya forma parte del grupo.',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#093D77'
                });
            }

            return;
        }

        if (!cliente.completo) {
            Swal.fire({
                icon: 'warning',
                title: 'Información incompleta',
                html:
                    'Completa la fecha de nacimiento, nacionalidad ' +
                    'y tipo de documento antes de agregarlo.<br><br>' +
                    `<a href="${cliente.editarUrl}" ` +
                    'target="_blank">Editar cliente</a>',
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#093D77'
            });

            return;
        }

        if (
            !restaurando &&
            integrantes.length === 0
        ) {
            cliente.esLider = true;
        }
        integrantes.push(cliente);

        calcularIntegrantes();
        renderizarIntegrantes();

        $selectorCliente.val('');
        mostrarError('integrantes', '');
    }

    function iniciales(nombre) {
        return String(nombre || '')
            .split(/\s+/)
            .filter(Boolean)
            .slice(0, 2)
            .map(parte => parte.charAt(0))
            .join('')
            .toUpperCase();
    }

    function renderizarIntegrantes() {
        if (!integrantes.length) {
            $lista.html(`
                <div id="sinIntegrantes" class="sin-integrantes">
                    <i class="bi bi-people"></i>
                    <strong>Aún no agregas integrantes</strong>
                    <span>Selecciona un cliente y pulsa Agregar.</span>
                </div>
            `);
        } else {
            const paquete = datosPaquete();

            const contenido = integrantes.map(
                function (integrante, indice) {
                    const edad =
                        integrante.edad === null ||
                        integrante.edad === undefined
                            ? 'Pendiente'
                            : `${integrante.edad} años`;

                    const categoria =
                        integrante.categoria ||
                        'Selecciona un paquete';

                    const precio =
                        integrante.precioFinal === null ||
                        integrante.precioFinal === undefined
                            ? '—'
                            : formatearDinero(
                                integrante.precioFinal,
                                paquete?.moneda
                            );

                    return `
                        <div class="integrante-fila"
                             data-id="${integrante.id}">
                            <input
                                type="hidden"
                                name="integrantes[${indice}][cliente_id]"
                                value="${integrante.id}"
                            >

                            <input
                                type="hidden"
                                name="integrantes[${indice}][es_lider]"
                                value="${integrante.esLider ? 1 : 0}"
                            >

                            <div class="integrante-persona">
                                <span class="integrante-iniciales">
                                    ${escapar(iniciales(integrante.nombre))}
                                </span>

                                <div>
                                    <strong>
                                        ${escapar(integrante.nombre)}
                                    </strong>

                                    <small>
                                        ${escapar(integrante.documento)}
                                    </small>
                                </div>
                            </div>

                            <div class="integrante-dato">
                                <span>Edad en el viaje</span>
                                <strong>${escapar(edad)}</strong>
                            </div>

                            <div class="integrante-dato">
                                <span>Categoría</span>
                                <strong>${escapar(categoria)}</strong>
                            </div>

                            <div class="integrante-dato">
                                <span>Valor asignado</span>
                                <strong>${escapar(precio)}</strong>
                            </div>

                            <div class="integrante-acciones">
                                ${integrante.edad !== null && integrante.edad < 18
                                    ? `
                                        <button
                                            type="button"
                                            class="btn-lider"
                                            disabled
                                            title="Los menores de edad no pueden ser líderes"
                                        >
                                            Menor de edad
                                        </button>
                                    `
                                    : `
                                        <button
                                            type="button"
                                            class="btn-lider ${integrante.esLider
                                                ? 'seleccionado'
                                                : ''}"
                                            data-accion="lider"
                                            data-id="${integrante.id}"
                                        >
                                            ${integrante.esLider
                                                ? 'Líder'
                                                : 'Hacer líder'}
                                        </button>
                                    `
                                }

                                <button
                                    type="button"
                                    class="btn-quitar-integrante"
                                    data-accion="quitar"
                                    data-id="${integrante.id}"
                                    title="Quitar integrante"
                                >
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </div>
                        </div>
                    `;
                }
            ).join('');

            $lista.html(contenido);
        }

        $('#contadorIntegrantes').text(
            `${integrantes.length} ` +
            `${integrantes.length === 1
                ? 'integrante'
                : 'integrantes'}`
        );

        actualizarResponsables();
        actualizarResumen();
    }

    function actualizarResponsables() {
        const valorAnterior = String(
            $responsable.val() ||
            configuracion.responsableAnterior ||
            ''
        );

        $responsable.html(
            '<option value="">Selecciona al responsable</option>'
        );

        integrantes
        .filter(
            integrante =>
                integrante.edad !== null &&
                integrante.edad >= 18
        )
        .forEach(function (integrante) {
            const $opcion = $('<option>', {
                value: integrante.id,
                text: integrante.nombre
            });

            if (
                String(integrante.id) ===
                valorAnterior
            ) {
                $opcion.prop('selected', true);
            }

            $responsable.append($opcion);
        });
    }

    function actualizarResumen() {
        const paquete = datosPaquete();

        const precioSinDescuento = paquete
            ? paquete.precio * integrantes.length
            : 0;

        const total = integrantes.reduce(
            (suma, integrante) =>
                suma +
                Number(integrante.precioFinal || 0),
            0
        );

        const descuento =
            precioSinDescuento - total;

        $('#resumenCantidad').text(
            integrantes.length
        );

        $('#resumenPrecioBase').text(
            paquete
                ? formatearDinero(
                    precioSinDescuento,
                    paquete.moneda
                )
                : '—'
        );

        $('#resumenDescuento').text(
            paquete
                ? formatearDinero(
                    descuento,
                    paquete.moneda
                )
                : '—'
        );

        $('#resumenTotal').text(
            paquete
                ? formatearDinero(
                    total,
                    paquete.moneda
                )
                : '—'
        );

        const tipo = $(
            'input[name="tipo_grupo"]:checked'
        ).val();

        if (tipo === 'familiar') {
            $('#textoModalidadPago').text(
                'El responsable seleccionado realizará los pagos del total familiar.'
            );
        } else if (tipo === 'independiente') {
            $('#textoModalidadPago').text(
                'Cada integrante tendrá su propio valor y saldo pendiente.'
            );
        } else {
            $('#textoModalidadPago').text(
                'Selecciona el tipo de grupo para definir cómo se registrarán los pagos.'
            );
        }
    }

    function actualizarTipoGrupo() {
        const tipo = $(
            'input[name="tipo_grupo"]:checked'
        ).val();

        const esFamiliar = tipo === 'familiar';

        $('#seccionResponsablePago').toggleClass(
            'oculto',
            !esFamiliar
        );

        $responsable.prop(
            'required',
            esFamiliar
        );

        if (!esFamiliar) {
            $responsable.val('');
            mostrarError(
                'responsable_pago_id',
                ''
            );
        }

        mostrarError(
            'tipo_grupo',
            tipo
                ? ''
                : 'Selecciona el tipo de grupo.'
        );

        actualizarResumen();
    }

    $('#btnAgregarIntegrante').on(
        'click',
        function () {
            const cliente =
                obtenerClienteSeleccionado();

            if (!cliente) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Selecciona un cliente',
                    text: 'Elige un cliente antes de agregarlo.',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#093D77'
                });

                return;
            }

            agregarIntegrante(cliente);
        }
    );

    $lista.on(
        'click',
        '[data-accion]',
        function () {
            const accion = $(this).data('accion');
            const id = Number($(this).data('id'));

            if (accion === 'lider') {
                integrantes = integrantes.map(
                    integrante => ({
                        ...integrante,
                        esLider: integrante.id === id
                    })
                );
            }

            if (accion === 'quitar') {
                integrantes = integrantes.filter(
                    integrante =>
                        integrante.id !== id
                );

                if (
                    integrantes.length &&
                    !integrantes.some(
                        integrante =>
                            integrante.esLider
                    )
                ) {
                    integrantes[0].esLider = true;
                }
            }

            renderizarIntegrantes();
        }
    );

    $destino.on('change', actualizarPaquete);

    $('input[name="tipo_grupo"]').on(
        'change',
        actualizarTipoGrupo
    );

    $('#nombre_grupo').on(
        'blur input',
        function () {
            const valor = $.trim($(this).val());

            mostrarError(
                'nombre_grupo',
                valor.length >= 3
                    ? ''
                    : 'Ingresa un nombre de al menos 3 caracteres.'
            );
        }
    );

    $responsable.on('change', function () {
        mostrarError(
            'responsable_pago_id',
            $(this).val()
                ? ''
                : 'Selecciona al responsable del pago.'
        );
    });

    function validarFormulario() {
        const nombre = $.trim(
            $('#nombre_grupo').val()
        );

        const tipo = $(
            'input[name="tipo_grupo"]:checked'
        ).val();

        const paquete = datosPaquete();

        const lideres = integrantes.filter(
            integrante => integrante.esLider
        ).length;

        const adultos = integrantes.filter(
            integrante =>
                integrante.edad !== null &&
                integrante.edad >= 18
        ).length;

        let valido = true;

        valido = mostrarError(
            'nombre_grupo',
            nombre.length >= 3
                ? ''
                : 'Ingresa un nombre de al menos 3 caracteres.'
        ) && valido;

        valido = mostrarError(
            'tipo_grupo',
            tipo
                ? ''
                : 'Selecciona el tipo de grupo.'
        ) && valido;

        valido = mostrarError(
            'destino_id',
            paquete &&
            paquete.precio > 0 &&
            paquete.fechaSalida &&
            paquete.capacidad > 0
                ? ''
                : 'Selecciona un paquete válido.'
        ) && valido;

        let errorIntegrantes = '';

        if (integrantes.length < 2) {
            errorIntegrantes =
                'Agrega al menos dos integrantes.';
        } else if (adultos === 0) {
            errorIntegrantes =
                'El grupo debe incluir al menos una persona mayor de edad.';
        }else if (lideres !== 1) {
            errorIntegrantes =
                'Selecciona exactamente un líder.';
        } else if (
            integrantes.some(
                integrante =>
                    integrante.precioFinal === null
            )
        ) {
            errorIntegrantes =
                'No se pudo calcular la tarifa de todos los integrantes.';
        } else if (
            paquete &&
            integrantes.length > paquete.capacidad
        ) {
            errorIntegrantes =
                'La cantidad de integrantes supera la capacidad total del paquete.';
        }

        valido = mostrarError(
            'integrantes',
            errorIntegrantes
        ) && valido;

        if (
            tipo === 'familiar' &&
            !$responsable.val()
        ) {
            valido = mostrarError(
                'responsable_pago_id',
                'Selecciona al responsable del pago familiar.'
            ) && valido;
        }

        return valido;
    }

    $formulario.on('submit', function (evento) {
        evento.preventDefault();

        if (enviando) {
            return;
        }

        if (!validarFormulario()) {
            Swal.fire({
                icon: 'error',
                title: 'Revisa la información',
                text: 'Corrige los datos señalados antes de continuar.',
                confirmButtonText: 'Corregir',
                confirmButtonColor: '#093D77'
            });

            return;
        }

        const paquete = datosPaquete();

        const total = integrantes.reduce(
            (suma, integrante) =>
                suma +
                Number(integrante.precioFinal || 0),
            0
        );

        Swal.fire({
            icon: 'question',
            title: esEdicion
                ? '¿Guardar los cambios del grupo?'
                : '¿Registrar la reserva grupal?',
            html:
                `${escapar($('#nombre_grupo').val())}<br>` +
                `${integrantes.length} integrantes<br>` +
                `Total: <strong>${formatearDinero(
                    total,
                    paquete.moneda
                )}</strong>`,
            showCancelButton: true,
            confirmButtonText: esEdicion
                ? 'Sí, guardar cambios'
                : 'Sí, registrar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#093D77',
            cancelButtonColor: '#6C7780',
            reverseButtons: true
        }).then(function (resultado) {
            if (!resultado.isConfirmed) {
                return;
            }

            enviando = true;

            const $boton = $formulario.find(
                'button[type="submit"]'
            );

            $boton
                .prop('disabled', true)
                .addClass('cargando');

            $boton.find('span').text(
                esEdicion
                    ? 'Guardando cambios...'
                    : 'Registrando reserva...'
            );

            $boton.find('i')
                .removeClass()
                .addClass('bi bi-arrow-repeat');

            $formulario[0].submit();
        });
    });

    function restaurarIntegrantes() {
        const anteriores =
            configuracion.integrantesAnteriores || [];

        anteriores.forEach(function (anterior) {
            const id = Number(
                anterior.cliente_id
            );

            const $opcion =
                $selectorCliente.find(
                    `option[value="${id}"]`
                );

            if (!$opcion.length) {
                return;
            }

            agregarIntegrante(
                {
                    id: id,
                    nombre: $opcion.data('nombre'),
                    documento:
                        $opcion.data('documento'),
                    fechaNacimiento:
                        $opcion.data(
                            'fecha-nacimiento'
                        ),
                    completo:
                        String(
                            $opcion.data('completo')
                        ) === '1',
                    editarUrl:
                        $opcion.data('editar-url'),
                    esLider:
                        anterior.es_lider === true ||
                        anterior.es_lider === 1 ||
                        String(anterior.es_lider) === '1'
                },
                true
            );
        });
    }

    function mostrarErroresServidor() {
        const errores =
            configuracion.errores || {};

        const mensajes = [];

        Object.keys(errores).forEach(
            function (campo) {
                const mensaje =
                    errores[campo][0];

                if (
                    campo.startsWith(
                        'integrantes.'
                    )
                ) {
                    mostrarError(
                        'integrantes',
                        mensaje
                    );
                } else {
                    mostrarError(
                        campo,
                        mensaje
                    );
                }

                mensajes.push(mensaje);
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
        } else if (configuracion.mensajeError) {
            Swal.fire({
                icon: 'error',
                title: esEdicion
                    ? 'No se pudo actualizar la reserva'
                    : 'No se pudo registrar la reserva',
                text: configuracion.mensajeError,
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#093D77'
            });
        }
    }

    actualizarPaquete();
    restaurarIntegrantes();
    actualizarTipoGrupo();
    mostrarErroresServidor();
});