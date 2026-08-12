@extends('layouts.main')

@section('titulo', 'Pagos')

@section('content')
<link
    rel="stylesheet"
    href="https://cdn.datatables.net/3.0.1/css/dataTables.bootstrap5.min.css"
>
<link
    rel="stylesheet"
    href="https://cdn.datatables.net/buttons/4.0.1/css/buttons.bootstrap5.min.css"
>
<link
    rel="stylesheet"
    href="{{ asset('css/pagos-listado.css') }}?v={{ filemtime(public_path('css/pagos-listado.css')) }}"
>

<main id="main" class="main pagina-pagos">
    <header class="pagos-encabezado">
        <div>
            <span class="pagos-modulo">
                Gestión financiera
            </span>

            <h1>Pagos</h1>

            <p>
                Registra cobros y consulta el historial de cada reserva.
            </p>
        </div>

        <a
            href="{{ route('reservas') }}"
            class="btn-volver-reservas"
        >
            <i class="bi bi-calendar-check"></i>
            Ver reservas
        </a>
    </header>

    <section class="resumen-pagos">
        <article class="resumen-pago">
            <span>Total recibido</span>

            <strong>
                USD {{ number_format(
                    $metricas['cobrado'],
                    2,
                    '.',
                    ','
                ) }}
            </strong>

            <small>
                {{ $metricas['total_trx'] }}
                transacciones registradas
            </small>
        </article>

        <article class="resumen-pago">
            <span>Saldo pendiente</span>

            <strong>
                USD {{ number_format(
                    $metricas['pendiente'],
                    2,
                    '.',
                    ','
                ) }}
            </strong>

            <small>
                {{ $metricas['reservas_deuda'] }}
                reservas con deuda
            </small>
        </article>

        <article class="resumen-pago">
            <span>Avance de cobro</span>

            <strong>
                {{ $metricas['tasa_cobro'] }}%
            </strong>

            <small>
                Sobre las reservas activas
            </small>
        </article>

        <article class="resumen-pago">
            <span>Sin pagos</span>

            <strong>
                USD {{ number_format(
                    $metricas['sin_iniciar_monto'],
                    2,
                    '.',
                    ','
                ) }}
            </strong>

            <small>
                Reservas que aún no registran abonos
            </small>
        </article>
    </section>

    <form
        method="GET"
        action="{{ route('pagos') }}"
        class="filtros-pagos"
    >
        @if ($reservaFiltroId)
            <input
                type="hidden"
                name="reserva_id"
                value="{{ $reservaFiltroId }}"
            >
        @endif

        <div class="buscar-pagos">
            <i class="bi bi-search"></i>

            <input
                id="buscarPagos"
                type="search"
                placeholder="Buscar código, cliente, grupo o paquete"
                autocomplete="off"
            >
        </div>

        <select name="estado">
            <option value="todos">
                Todos los estados
            </option>

            <option
                value="sin pago"
                @selected($filtros['estado'] === 'sin pago')
            >
                Sin pago
            </option>

            <option
                value="parcial"
                @selected($filtros['estado'] === 'parcial')
            >
                Pago parcial
            </option>

            <option
                value="completado"
                @selected($filtros['estado'] === 'completado')
            >
                Pago completado
            </option>

            <option
                value="cancelada"
                @selected($filtros['estado'] === 'cancelada')
            >
                Reserva cancelada
            </option>
        </select>

        <select name="metodo">
            <option value="todos">
                Todos los métodos
            </option>

            <option
                value="efectivo"
                @selected($filtros['metodo'] === 'efectivo')
            >
                Efectivo
            </option>

            <option
                value="transferencia"
                @selected($filtros['metodo'] === 'transferencia')
            >
                Transferencia
            </option>

            <option
                value="tarjeta"
                @selected($filtros['metodo'] === 'tarjeta')
            >
                Tarjeta
            </option>

            <option
                value="otro"
                @selected($filtros['metodo'] === 'otro')
            >
                Otro
            </option>
        </select>

        <button type="submit">
            Filtrar
        </button>

        @if (
            $filtros['estado'] !== 'todos' ||
            $filtros['metodo'] !== 'todos' ||
            $reservaFiltroId
        )
            <a href="{{ route('pagos') }}">
                Limpiar
            </a>
        @endif
    </form>

    <section class="contenedor-tabla-pagos">
        <div class="tabla-pagos-responsive">
            <table
                id="tablaPagos"
                class="tabla-pagos"
            >
                <thead>
                    <tr>
                        <th>Reserva</th>
                        <th>Cliente o grupo</th>
                        <th>Modalidad</th>
                        <th>Valores</th>
                        <th>Último pago</th>
                        <th>Estado</th>
                        <th class="columna-acciones">
                            Acciones
                        </th>
                    </tr>
                </thead>

                <tbody id="cuerpoTablaPagos">
                    @foreach ($reservas as $reserva)
                        <tr
                            class="fila-pago"
                            data-busqueda="{{ mb_strtolower(
                                $reserva['codigo_reserva'] . ' ' .
                                $reserva['cliente_grupo'] . ' ' .
                                ($reserva['paquete'] ?? '')
                            ) }}"
                        >
                            <td>
                                <strong class="codigo-reserva-pago">
                                    {{ $reserva['codigo_reserva'] }}
                                </strong>

                                <small>
                                    {{ ucfirst($reserva['tipo']) }}
                                </small>
                            </td>

                            <td>
                                <strong>
                                    {{ $reserva['cliente_grupo'] }}
                                </strong>

                                <small>
                                    {{ $reserva['paquete']
                                        ?: 'Paquete no disponible' }}
                                </small>

                                @if ($reserva['responsable_pago'])
                                    <small>
                                        Responsable:
                                        {{ $reserva['responsable_pago'] }}
                                    </small>
                                @endif
                            </td>

                            <td>
                                <span class="modalidad-pago">
                                    {{ $reserva['modalidad_pago'] }}
                                </span>

                                <small>
                                    {{ $reserva['cantidad_viajeros'] }}
                                    viajero(s)
                                </small>
                            </td>

                            <td data-order="{{ $reserva['precio_total'] }}">
                                <div class="valores-reserva-pago">
                                    <span>
                                        Total:
                                        <strong>
                                            {{ $reserva['moneda'] }}
                                            {{ number_format(
                                                $reserva['precio_total'],
                                                2,
                                                '.',
                                                ','
                                            ) }}
                                        </strong>
                                    </span>

                                    <span class="valor-pagado">
                                        Pagado:
                                        {{ $reserva['moneda'] }}
                                        {{ number_format(
                                            $reserva['pagado'],
                                            2,
                                            '.',
                                            ','
                                        ) }}
                                    </span>

                                    <span class="valor-pendiente">
                                        Saldo:
                                        {{ $reserva['moneda'] }}
                                        {{ number_format(
                                            $reserva['pendiente'],
                                            2,
                                            '.',
                                            ','
                                        ) }}
                                    </span>
                                </div>
                            </td>

                            <td
                                data-order="{{
                                    $reserva['id_ultimo_pago'] ?: 0
                                }}"
                            >
                                <strong>
                                    {{ $reserva['metodo'] }}
                                </strong>

                                <small>
                                    {{ $reserva['fecha_ultimo_pago']
                                        ?: 'Sin pagos registrados' }}
                                </small>
                            </td>

                            <td>
                                <span
                                    class="estado-cobro estado-{{ \Illuminate\Support\Str::slug(
                                        $reserva['estado']
                                    ) }}"
                                >
                                    {{ $reserva['estado'] }}
                                </span>

                                <div class="barra-cobro">
                                    <span
                                        style="width: {{ $reserva['porcentaje'] }}%"
                                    ></span>
                                </div>

                                <small>
                                    {{ $reserva['porcentaje'] }}%
                                </small>
                            </td>

                            <td>
                                <div class="acciones-pago">
                                    <button
                                        type="button"
                                        class="accion-pago historial btn-historial-pagos"
                                        data-url="{{ route(
                                            'pagos.lista',
                                            $reserva['reserva_id'],
                                            false
                                        ) }}"
                                        data-codigo="{{ $reserva['codigo_reserva'] }}"
                                        title="Ver historial"
                                    >
                                        <i class="bi bi-clock-history"></i>
                                    </button>

                                    @if ($reserva['tipo'] === 'grupal')
                                        <button
                                            type="button"
                                            class="accion-pago grupo btn-desglose-pago"
                                            data-url="{{ route(
                                                'pagos.grupo',
                                                $reserva['reserva_id'],
                                                false
                                            ) }}"
                                            title="Ver desglose grupal"
                                        >
                                            <i class="bi bi-people"></i>
                                        </button>
                                    @endif

                                    @if (
                                        $reserva['puede_cobrar'] &&
                                        $reserva['modalidad_pago'] !==
                                            'Pago por integrante'
                                    )
                                        <button
                                            type="button"
                                            class="accion-pago cobrar btn-cobrar-reserva"
                                            data-reserva-id="{{ $reserva['reserva_id'] }}"
                                            data-codigo="{{ $reserva['codigo_reserva'] }}"
                                            data-nombre="{{ $reserva['nombre_pagador'] }}"
                                            data-cliente-id="{{ $reserva['cliente_pago_id'] }}"
                                            data-saldo="{{ $reserva['pendiente'] }}"
                                            data-moneda="{{ $reserva['moneda'] }}"
                                            title="Registrar pago"
                                        >
                                            <i class="bi bi-cash-coin"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</main>

{{-- Modal para registrar un pago --}}
<div
    class="modal fade"
    id="modalRegistrarPago"
    tabindex="-1"
    aria-hidden="true"
>
    <div class="modal-dialog modal-dialog-centered">
        <form
            id="formularioRegistrarPago"
            method="POST"
            action="{{ route('pagos.store') }}"
            class="modal-content modal-pago"
            novalidate
        >
            @csrf

            <input
                type="hidden"
                name="reserva_id"
                id="pagoReservaId"
            >

            <input
                type="hidden"
                name="cliente_id"
                id="pagoClienteId"
            >

            <div class="modal-header">
                <div>
                    <span>Registrar pago</span>
                    <h2 id="pagoCodigoReserva">Reserva</h2>
                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Cerrar"
                ></button>
            </div>

            <div class="modal-body">
                <div class="resumen-cobro-modal">
                    <span id="pagoNombreCliente">
                        Cliente
                    </span>

                    <strong id="pagoSaldoDisponible">
                        USD 0.00
                    </strong>

                    <small>Saldo máximo disponible</small>
                </div>

                <div class="campo-pago">
                    <label for="pagoMonto">
                        Monto recibido <span>*</span>
                    </label>

                    <input
                        id="pagoMonto"
                        name="monto_depositado"
                        type="number"
                        min="0.01"
                        step="0.01"
                        required
                    >

                    <small
                        id="pagoMontoError"
                        class="mensaje-error"
                    ></small>
                </div>

                <div class="campo-pago">
                    <label for="pagoMetodo">
                        Método de pago <span>*</span>
                    </label>

                    <select
                        id="pagoMetodo"
                        name="metodo_pago"
                        required
                    >
                        <option value="">
                            Selecciona una opción
                        </option>
                        <option value="efectivo">
                            Efectivo
                        </option>
                        <option value="transferencia">
                            Transferencia
                        </option>
                        <option value="tarjeta">
                            Tarjeta
                        </option>
                        <option value="otro">
                            Otro
                        </option>
                    </select>

                    <small
                        id="pagoMetodoError"
                        class="mensaje-error"
                    ></small>
                </div>

                <div class="campo-pago">
                    <label for="pagoReferencia">
                        Comprobante o referencia
                    </label>

                    <input
                        id="pagoReferencia"
                        name="referencia"
                        type="text"
                        maxlength="100"
                        placeholder="Ejemplo: transferencia 45821"
                    >

                    <small>
                        Obligatorio para transferencia y tarjeta.
                    </small>

                    <small
                        id="pagoReferenciaError"
                        class="mensaje-error"
                    ></small>
                </div>
            </div>

            <div class="modal-footer">
                <button
                    type="button"
                    class="btn-secundario-pago"
                    data-bs-dismiss="modal"
                >
                    Cancelar
                </button>

                <button
                    type="submit"
                    class="btn-principal-pago"
                >
                    <span>Registrar pago</span>
                    <i class="bi bi-check-lg"></i>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Modal de desglose grupal --}}
<div
    class="modal fade"
    id="modalDesglosePago"
    tabindex="-1"
    aria-hidden="true"
>
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content modal-pago">
            <div class="modal-header">
                <div>
                    <span>Desglose grupal</span>
                    <h2 id="desgloseNombreGrupo">Grupo</h2>
                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Cerrar"
                ></button>
            </div>

            <div class="modal-body">
                <div
                    id="desgloseCargando"
                    class="estado-cargando-pago"
                >
                    <div class="spinner-border"></div>
                    <span>Cargando información...</span>
                </div>

                <div
                    id="desgloseContenido"
                    class="oculto"
                >
                    <div
                        id="resumenDesglose"
                        class="resumen-desglose"
                    ></div>

                    <div class="tabla-pagos-responsive">
                        <table class="tabla-integrantes-pago">
                            <thead>
                                <tr>
                                    <th>Integrante</th>
                                    <th>Tarifa</th>
                                    <th>Asignado</th>
                                    <th>Pagado</th>
                                    <th>Saldo</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>

                            <tbody id="cuerpoDesglosePago"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal del historial --}}
<div
    class="modal fade"
    id="modalHistorialPagos"
    tabindex="-1"
    aria-hidden="true"
>
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content modal-pago">
            <div class="modal-header">
                <div>
                    <span>Historial de pagos</span>
                    <h2 id="historialCodigoReserva">Reserva</h2>
                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Cerrar"
                ></button>
            </div>

            <div class="modal-body">
                <div
                    id="historialCargando"
                    class="estado-cargando-pago"
                >
                    <div class="spinner-border"></div>
                    <span>Cargando historial...</span>
                </div>

                <div
                    id="historialContenido"
                    class="oculto"
                >
                    <div
                        id="historialResumen"
                        class="historial-resumen"
                    ></div>

                    <div id="listaHistorialPagos"></div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal para editar un pago --}}
<div
    class="modal fade"
    id="modalEditarPago"
    tabindex="-1"
    aria-hidden="true"
>
    <div class="modal-dialog modal-dialog-centered">
        <form
            id="formularioEditarPago"
            method="POST"
            action=""
            class="modal-content modal-pago"
            novalidate
        >
            @csrf
            @method('PUT')

            <input
                type="hidden"
                name="reserva_id"
                id="editarReservaId"
            >

            <div class="modal-header">
                <div>
                    <span>Corregir transacción</span>
                    <h2 id="editarPagoTitulo">Pago</h2>
                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Cerrar"
                ></button>
            </div>

            <div class="modal-body">
                <div class="campo-pago">
                    <label for="editarPagoMonto">
                        Monto <span>*</span>
                    </label>

                    <input
                        id="editarPagoMonto"
                        name="monto_depositado"
                        type="number"
                        min="0.01"
                        step="0.01"
                        required
                    >
                </div>

                <div class="campo-pago">
                    <label for="editarPagoMetodo">
                        Método <span>*</span>
                    </label>

                    <select
                        id="editarPagoMetodo"
                        name="metodo_pago"
                        required
                    >
                        <option value="efectivo">
                            Efectivo
                        </option>
                        <option value="transferencia">
                            Transferencia
                        </option>
                        <option value="tarjeta">
                            Tarjeta
                        </option>
                        <option value="otro">
                            Otro
                        </option>
                    </select>
                </div>

                <div class="campo-pago">
                    <label for="editarPagoReferencia">
                        Referencia
                    </label>

                    <input
                        id="editarPagoReferencia"
                        name="referencia"
                        type="text"
                        maxlength="100"
                    >
                </div>
            </div>

            <div class="modal-footer">
                <button
                    type="button"
                    class="btn-secundario-pago"
                    data-bs-dismiss="modal"
                >
                    Cancelar
                </button>

                <button
                    type="submit"
                    class="btn-principal-pago"
                >
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>
</div>

<div
    class="modal fade"
    id="modalAnularPago"
    tabindex="-1"
    aria-labelledby="tituloModalAnularPago"
    aria-hidden="true"
>
    <div class="modal-dialog modal-dialog-centered">
        <form
            id="formularioAnularPago"
            method="POST"
            action=""
            class="modal-content modal-pago"
        >
            @csrf
            @method('DELETE')

            <input
                type="hidden"
                name="reserva_id"
                id="anularReservaId"
            >

            <div class="modal-header">
                <div>
                    <span>Anulación</span>
                    <h2 id="tituloModalAnularPago">
                        Anular pago
                    </h2>
                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Cerrar"
                ></button>
            </div>

            <div class="modal-body">
                <p>
                    El pago permanecerá en el historial, pero dejará de sumarse al total recibido.
                </p>

                <div class="campo-pago">
                    <label for="anularMotivo">
                        Motivo de la anulación <span>*</span>
                    </label>

                    <textarea
                        id="anularMotivo"
                        name="motivo_anulacion"
                        rows="5"
                        minlength="10"
                        maxlength="500"
                        placeholder="Explica por qué se anula este pago..."
                        required
                    ></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button
                    type="button"
                    class="btn-secundario-pago"
                    data-bs-dismiss="modal"
                >
                    Cancelar
                </button>

                <button
                    type="submit"
                    class="btn-principal-pago"
                >
                    Sí, anular pago
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    window.configuracionPagos = {
        token: @json(csrf_token()),
        basePagos: @json(route('pagos', [], false)),
        mensajeExito: @json(session('success')),
        mensajeError: @json(session('error')),
        errores: @json($errors->toArray())
    };
</script>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script
    src="https://cdn.datatables.net/3.0.1/js/dataTables.min.js"
></script>
<script
    src="https://cdn.datatables.net/3.0.1/js/dataTables.bootstrap5.min.js"
></script>
<script
    src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"
></script>
<script
    src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"
></script>
<script
    src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"
></script>
<script
    src="https://cdn.datatables.net/buttons/4.0.1/js/dataTables.buttons.min.js"
></script>
<script
    src="https://cdn.datatables.net/buttons/4.0.1/js/buttons.bootstrap5.min.js"
></script>
<script
    src="https://cdn.datatables.net/buttons/4.0.1/js/buttons.html5.min.js"
></script>
<script
    src="{{ asset('js/pagos-listado.js') }}?v={{ filemtime(public_path('js/pagos-listado.js')) }}"
></script>
@endsection
