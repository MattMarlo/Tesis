@extends('layouts.main')

@section('titulo', 'Reservas')

@section('content')
<link
    rel="stylesheet"
    href="{{ asset('css/reservas-listado.css') }}"
>

<main id="main" class="main pagina-reservas">
    <div class="reservas-encabezado">
        <div>
            <span class="reservas-modulo">
                Gestión de viajes
            </span>

            <h1>Reservas</h1>

            <p>
                Consulta las reservas individuales y grupales,
                sus viajeros, valores y estado de pago.
            </p>
        </div>

        <div class="acciones-nueva-reserva">
            <a
                href="{{ route('reservas_individual.create') }}"
                class="btn-reserva individual"
            >
                <i class="bi bi-person"></i>
                Nueva individual
            </a>

            <a
                href="{{ route('reservas_grupal.create') }}"
                class="btn-reserva grupal"
            >
                <i class="bi bi-people"></i>
                Nueva grupal
            </a>
        </div>
    </div>

    <section class="reservas-resumen">
        <div>
            <span>Total</span>
            <strong>{{ $resumen['total'] }}</strong>
        </div>

        <div>
            <span>Pendientes</span>
            <strong>{{ $resumen['pendientes'] }}</strong>
        </div>

        <div>
            <span>Confirmadas</span>
            <strong>{{ $resumen['confirmadas'] }}</strong>
        </div>

        <div>
            <span>Canceladas</span>
            <strong>{{ $resumen['canceladas'] }}</strong>
        </div>
    </section>

    <form
        class="filtros-reservas"
        action="{{ route('reservas') }}"
        method="GET"
    >
        <div class="buscador-reservas">
            <i class="bi bi-search"></i>

            <input
                type="search"
                name="buscar"
                value="{{ request('buscar') }}"
                placeholder="Código, cliente, grupo o paquete"
            >
        </div>

        <select name="tipo">
            <option value="">Todos los tipos</option>

            <option
                value="individual"
                @selected(request('tipo') === 'individual')
            >
                Individuales
            </option>

            <option
                value="grupal"
                @selected(request('tipo') === 'grupal')
            >
                Grupales
            </option>
        </select>

        <select name="estado">
            <option value="">Todos los estados</option>

            <option
                value="pendiente"
                @selected(request('estado') === 'pendiente')
            >
                Pendientes
            </option>

            <option
                value="confirmada"
                @selected(request('estado') === 'confirmada')
            >
                Confirmadas
            </option>

            <option
                value="cancelada"
                @selected(request('estado') === 'cancelada')
            >
                Canceladas
            </option>
        </select>

        <button type="submit">
            Filtrar
        </button>

        @if (
            request('buscar') ||
            request('tipo') ||
            request('estado')
        )
            <a href="{{ route('reservas') }}">
                Limpiar
            </a>
        @endif
    </form>

    <section class="reservas-listado-contenedor">
        <div class="tabla-reservas-responsive">
            <table class="tabla-reservas">
                <thead>
                    <tr>
                        <th>Reserva</th>
                        <th>Cliente o grupo</th>
                        <th>Paquete</th>
                        <th>Fecha del viaje</th>
                        <th>Viajeros</th>
                        <th>Valores</th>
                        <th>Estados</th>
                        <th class="columna-acciones">
                            Acciones
                        </th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($reservas as $reserva)
                        @php
                            $moneda = strtoupper(
                                $reserva->moneda ?: 'USD'
                            );

                            $total = (float)
                                $reserva->precio_total_viaje;

                            $pagado = (float)
                                ($reserva->total_pagado ?? 0);

                            $saldo = max(
                                0,
                                $total - $pagado
                            );

                            $cantidadViajeros =
                                $reserva->cantidad_viajeros
                                ?: (
                                    $reserva->tipo === 'grupal'
                                        ? (
                                            $reserva->grupo
                                                ?->clientes
                                                ?->count() ?? 0
                                        )
                                        : 1
                                );

                            $nombreTitular =
                                $reserva->tipo === 'grupal'
                                    ? (
                                        $reserva->grupo
                                            ?->nombre_grupo
                                        ?: 'Grupo sin nombre'
                                    )
                                    : (
                                        $reserva->cliente
                                            ?->nombre_completo
                                        ?: 'Cliente no disponible'
                                    );
                        @endphp

                        <tr>
                            <td>
                                <div class="reserva-codigo">
                                    <strong>
                                        {{ $reserva->codigo_reserva }}
                                    </strong>

                                    <span class="tipo-reserva {{ $reserva->tipo }}">
                                        {{ $reserva->tipo === 'grupal'
                                            ? 'Grupal'
                                            : 'Individual' }}
                                    </span>
                                </div>
                            </td>

                            <td>
                                <div class="reserva-titular">
                                    <strong>
                                        {{ $nombreTitular }}
                                    </strong>

                                    @if ($reserva->tipo === 'grupal')
                                        <small>
                                            {{ $reserva->grupo?->tipo_grupo === 'familiar'
                                                ? 'Grupo familiar'
                                                : 'Personas independientes' }}
                                        </small>

                                        @if (
                                            $reserva->grupo?->tipo_grupo ===
                                            'familiar'
                                        )
                                            <small>
                                                Paga:
                                                {{ $reserva->grupo
                                                    ?->responsablePago
                                                    ?->nombre_completo
                                                    ?: 'Sin asignar' }}
                                            </small>
                                        @endif
                                    @else
                                        <small>
                                            {{ $reserva->cliente
                                                ?->documento }}
                                        </small>
                                    @endif
                                </div>
                            </td>

                            <td>
                                <div class="reserva-paquete">
                                    <strong>
                                        {{ $reserva->destino
                                            ?->nombre_paquete
                                            ?: 'Paquete no disponible' }}
                                    </strong>

                                    <small>
                                        {{ $reserva->destino
                                            ?->ciudad_destino }},
                                        {{ $reserva->destino?->pais }}
                                    </small>
                                </div>
                            </td>

                            <td>
                                <div class="reserva-fecha">
                                    <i class="bi bi-calendar-event"></i>

                                    <span>
                                        {{ $reserva->fecha_viaje
                                            ?->format('d/m/Y') }}
                                    </span>
                                </div>
                            </td>

                            <td>
                                <span class="cantidad-viajeros">
                                    <i class="bi bi-people"></i>
                                    {{ $cantidadViajeros }}
                                </span>
                            </td>

                            <td>
                                <div class="reserva-montos">
                                    <span>
                                        Total:
                                        <strong>
                                            {{ $moneda }}
                                            {{ number_format($total, 2) }}
                                        </strong>
                                    </span>

                                    <span>
                                        Pagado:
                                        {{ $moneda }}
                                        {{ number_format($pagado, 2) }}
                                    </span>

                                    <span class="monto-pendiente">
                                        Saldo:
                                        {{ $moneda }}
                                        {{ number_format($saldo, 2) }}
                                    </span>
                                </div>
                            </td>

                            <td>
                                <div class="reserva-estados">
                                    <span class="estado {{ $reserva->estado }}">
                                        {{ ucfirst($reserva->estado) }}
                                    </span>

                                    <span class="estado-pago {{ $reserva->estado_pago }}">
                                        {{ ucfirst($reserva->estado_pago) }}
                                    </span>
                                </div>
                            </td>

                            <td>
                                <div class="acciones-reserva-listado">
                                    <button
                                        type="button"
                                        class="accion-reserva ver btn-ver-reserva"
                                        data-reserva-id="{{ $reserva->id }}"
                                        data-detalle-url="{{ route(
                                            'reservas.detalle',
                                            $reserva->id,
                                            false
                                        ) }}"
                                        title="Ver detalle"
                                    >
                                        <i class="bi bi-eye"></i>
                                    </button>

                                    @if (
                                        $reserva->estado === \App\Models\Reserva::ESTADO_PENDIENTE &&
                                        (float) ($reserva->total_pagado ?? 0) <= 0
                                    )
                                        @php
                                            $rutaEdicion = $reserva->esGrupal()
                                                ? route(
                                                    'reservas_grupal.edit',
                                                    $reserva->id
                                                )
                                                : route(
                                                    'reservas_individual.edit',
                                                    $reserva->id
                                                );
                                        @endphp

                                        <a
                                            href="{{ $rutaEdicion }}"
                                            class="accion-reserva editar"
                                            title="Editar reserva"
                                            aria-label="Editar reserva {{ $reserva->codigo_reserva }}"
                                        >
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                    @endif

                                    @if (!$reserva->estaCancelada())
                                        <a
                                            href="{{ route(
                                                'pagos',
                                                [
                                                    'reserva_id' =>
                                                        $reserva->id
                                                ]
                                            ) }}"
                                            class="accion-reserva pago"
                                            title="Gestionar pagos"
                                        >
                                            <i class="bi bi-cash-coin"></i>
                                        </a>

                                        <button
                                            type="button"
                                            class="accion-reserva cancelar btn-cancelar-reserva"
                                            data-cancelar-url="{{ route(
                                                'reservas.cancelar',
                                                $reserva->id,
                                                false
                                            ) }}"
                                            data-codigo="{{ $reserva->codigo_reserva }}"
                                            title="Cancelar reserva"
                                        >
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="reservas-vacio">
                                    <i class="bi bi-calendar2-x"></i>

                                    <strong>
                                        No se encontraron reservas
                                    </strong>

                                    <span>
                                        Registra una reserva o modifica
                                        los filtros.
                                    </span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($reservas->hasPages())
        <div class="paginacion-reservas">
            {{ $reservas->links() }}
        </div>
    @endif
</main>

<div
    class="modal fade"
    id="modalDetalleReserva"
    tabindex="-1"
    aria-hidden="true"
>
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content detalle-reserva-modal">
            <div class="modal-header">
                <div>
                    <span id="detalleTipo" class="detalle-tipo">
                        Reserva
                    </span>

                    <h2
                        id="detalleCodigo"
                        class="modal-title"
                    >
                        —
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
                <div
                    id="detalleCargando"
                    class="detalle-cargando"
                >
                    <div class="spinner-border" role="status"></div>
                    <span>Cargando información...</span>
                </div>

                <div
                    id="detalleContenido"
                    class="oculto"
                >
                    <section class="detalle-bloque">
                        <h3>Información del viaje</h3>

                        <div class="detalle-grid">
                            <div>
                                <span>Paquete</span>
                                <strong id="detallePaquete">—</strong>
                            </div>

                            <div>
                                <span>Ruta</span>
                                <strong id="detalleRuta">—</strong>
                            </div>

                            <div>
                                <span>Salida</span>
                                <strong id="detalleSalida">—</strong>
                            </div>

                            <div>
                                <span>Regreso</span>
                                <strong id="detalleRegreso">—</strong>
                            </div>
                        </div>
                    </section>

                    <section class="detalle-bloque">
                        <h3>Resumen económico</h3>

                        <div class="detalle-valores">
                            <div>
                                <span>Total</span>
                                <strong id="detalleTotal">—</strong>
                            </div>

                            <div>
                                <span>Pagado</span>
                                <strong id="detallePagado">—</strong>
                            </div>

                            <div>
                                <span>Saldo pendiente</span>
                                <strong id="detalleSaldo">—</strong>
                            </div>
                        </div>
                    </section>

                    <section class="detalle-bloque">
                        <div class="detalle-bloque-titulo">
                            <h3>Viajeros</h3>
                            <span id="detalleCantidad">0</span>
                        </div>

                        <div class="detalle-tabla-responsive">
                            <table class="tabla-viajeros">
                                <thead>
                                    <tr>
                                        <th>Viajero</th>
                                        <th>Edad</th>
                                        <th>Categoría</th>
                                        <th>Tarifa</th>
                                        <th>Valor</th>
                                        <th>Responsabilidad</th>
                                    </tr>
                                </thead>

                                <tbody id="detalleViajeros"></tbody>
                            </table>
                        </div>
                    </section>

                    <section
                        id="detalleCancelacion"
                        class="detalle-cancelacion oculto"
                    >
                        <h3>Información de cancelación</h3>

                        <p id="detalleMotivoCancelacion"></p>

                        <small id="detalleFechaCancelacion"></small>
                    </section>
                </div>
            </div>

            <div class="modal-footer">
                <button
                    type="button"
                    class="btn btn-secondary"
                    data-bs-dismiss="modal"
                >
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    window.configuracionReservas = {
        token: @json(csrf_token()),
        mensajeExito: @json(session('success')),
        mensajeError: @json(session('error'))
    };
</script>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="{{ asset('js/reservas-listado.js') }}"></script>
@endsection