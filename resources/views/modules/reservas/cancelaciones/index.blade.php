@extends('layouts.main')

@section('titulo', 'Solicitudes de cancelación')

@section('content')
<link
    rel="stylesheet"
    href="{{ asset(
        'css/solicitudes-cancelacion.css'
    ) }}?v={{ filemtime(
        public_path(
            'css/solicitudes-cancelacion.css'
        )
    ) }}"
>

<main
    id="main"
    class="main pagina-cancelaciones"
>
    <div class="cancelacion-contenedor">
        <header class="cancelacion-encabezado">
            <div class="cancelacion-encabezado-contenido">
                <span class="cancelacion-modulo">
                    Cancelaciones y reembolsos
                </span>

                <h1>Solicitudes de cancelación</h1>

                <p>
                    Revisa las solicitudes pendientes,
                    consulta sus evidencias, documenta gastos
                    y toma una decisión administrativa.
                </p>
            </div>

            <div class="cancelacion-encabezado-acciones">
                <a
                    href="{{ route('reservas') }}"
                    class="btn-cancelacion secundario"
                >
                    <i class="bi bi-arrow-left"></i>
                    Reservas
                </a>
            </div>
        </header>

        @if (session('success'))
            <div class="cancelacion-alerta exito">
                <i class="bi bi-check-circle"></i>

                <p>
                    {{ session('success') }}
                </p>
            </div>
        @endif

        @if (session('error'))
            <div class="cancelacion-alerta error">
                <i
                    class="
                        bi
                        bi-exclamation-circle
                    "
                ></i>

                <p>
                    {{ session('error') }}
                </p>
            </div>
        @endif

        @if ($errors->any())
            <div class="cancelacion-alerta error">
                <i
                    class="
                        bi
                        bi-exclamation-circle
                    "
                ></i>

                <div>
                    <strong>
                        No se pudo completar la acción
                    </strong>

                    <ul class="mb-0 mt-2">
                        @foreach (
                            $errors->all() as $error
                        )
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <section class="resumen-reserva-grid">
            <div
                class="
                    resumen-reserva-item
                    destacado
                "
            >
                <span>Total de solicitudes</span>

                <strong>
                    {{
                        $metricas['total']
                        ?? $solicitudes->total()
                    }}
                </strong>

                <small>
                    Todos los estados
                </small>
            </div>

            <div class="resumen-reserva-item">
                <span>Pendientes de revisión</span>

                <strong
                    class="cancelacion-texto-primary"
                >
                    {{
                        $metricas['pendientes']
                        ?? 0
                    }}
                </strong>

                <small>
                    Requieren atención
                </small>
            </div>

            <div class="resumen-reserva-item">
                <span>Aprobadas</span>

                <strong
                    class="cancelacion-texto-exito"
                >
                    {{
                        $metricas['aprobadas']
                        ?? 0
                    }}
                </strong>

                <small>
                    Reservas canceladas
                </small>
            </div>

            <div class="resumen-reserva-item">
                <span>Rechazadas</span>

                <strong
                    class="cancelacion-texto-peligro"
                >
                    {{
                        $metricas['rechazadas']
                        ?? 0
                    }}
                </strong>

                <small>
                    Solicitudes no autorizadas
                </small>
            </div>
        </section>

        <section class="cancelacion-card">
            <div class="cancelacion-card-body">
                <form
                    action="{{ route(
                        'cancelaciones.solicitudes.index'
                    ) }}"
                    method="GET"
                    class="cancelacion-form-grid"
                >
                    <div class="cancelacion-campo">
                        <label for="buscar">
                            <span>
                                Buscar solicitud
                            </span>
                        </label>

                        <input
                            id="buscar"
                            type="search"
                            name="buscar"
                            value="{{ request('buscar') }}"
                            placeholder="Código, cliente, documento o paquete"
                        >
                    </div>

                    <div class="cancelacion-campo">
                        <label for="estado">
                            <span>
                                Estado de la solicitud
                            </span>
                        </label>

                        <select
                            id="estado"
                            name="estado"
                        >
                            <option value="">
                                Todos los estados
                            </option>

                            <option
                                value="pendiente"
                                @selected(
                                    request('estado') ===
                                    'pendiente'
                                )
                            >
                                Pendientes
                            </option>

                            <option
                                value="aprobada"
                                @selected(
                                    request('estado') ===
                                    'aprobada'
                                )
                            >
                                Aprobadas
                            </option>

                            <option
                                value="rechazada"
                                @selected(
                                    request('estado') ===
                                    'rechazada'
                                )
                            >
                                Rechazadas
                            </option>

                            <option
                                value="anulada"
                                @selected(
                                    request('estado') ===
                                    'anulada'
                                )
                            >
                                Anuladas
                            </option>
                        </select>
                    </div>

                    <div
                        class="
                            cancelacion-campo
                            ancho-completo
                        "
                    >
                        <div
                            class="
                                d-flex
                                flex-wrap
                                justify-content-end
                                gap-2
                            "
                        >
                            @if (
                                request('buscar') ||
                                request('estado')
                            )
                                <a
                                    href="{{ route(
                                        'cancelaciones.solicitudes.index'
                                    ) }}"
                                    class="
                                        btn-cancelacion
                                        secundario
                                    "
                                >
                                    <i
                                        class="
                                            bi
                                            bi-x-circle
                                        "
                                    ></i>

                                    Limpiar
                                </a>
                            @endif

                            <button
                                type="submit"
                                class="
                                    btn-cancelacion
                                    primario
                                "
                            >
                                <i class="bi bi-search"></i>
                                Buscar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </section>

        <div class="cancelacion-alerta informativa">
            <i class="bi bi-info-circle"></i>

            <div>
                <strong>
                    La solicitud no cancela inmediatamente
                    la reserva
                </strong>

                <p>
                    Abre el expediente para revisar la
                    evidencia, los pagos y los gastos antes
                    de aprobar o rechazar la solicitud.
                </p>
            </div>
        </div>

        @forelse ($solicitudes as $solicitud)
            @php
                $reserva = $solicitud->reserva;

                $estado = $solicitud->estado
                    ?: 'pendiente';

                $claseEstado = match ($estado) {
                    'aprobada' => 'aprobada',
                    'rechazada' => 'rechazada',
                    'anulada' => 'anulada',
                    default => 'pendiente',
                };

                $textoEstado = match ($estado) {
                    'aprobada' => 'Aprobada',
                    'rechazada' => 'Rechazada',
                    'anulada' => 'Anulada',
                    default => 'Pendiente de revisión',
                };

                $textoTipo = match (
                    $solicitud->tipo_cancelacion
                ) {
                    'decision_cliente' =>
                        'Decisión del cliente',

                    'fuerza_mayor' =>
                        'Fuerza mayor',

                    'responsabilidad_agencia' =>
                        'Responsabilidad de la agencia',

                    'problema_proveedor' =>
                        'Problema con proveedor',

                    'cambio_viaje' =>
                        'Cambio o reprogramación',

                    default =>
                        'Otro motivo',
                };

                $textoSolicitante = match (
                    $solicitud->solicitante
                ) {
                    'cliente' => 'Cliente',
                    'agencia' => 'Agencia',
                    'proveedor' => 'Proveedor',
                    'sistema' => 'Sistema',
                    default => ucfirst(
                        (string) $solicitud->solicitante
                    ),
                };

                $nombreCliente =
                    $reserva?->esGrupal()
                        ? (
                            $reserva->grupo
                                ?->nombre_grupo
                            ?: 'Grupo no disponible'
                        )
                        : (
                            $reserva?->cliente
                                ?->nombre_completo
                            ?: 'Cliente no disponible'
                        );

                $nombrePaquete =
                    $reserva?->destino
                        ?->nombre_paquete
                    ?: 'Paquete no disponible';

                $fechaSolicitud =
                    $solicitud->solicitado_at
                        ?->format('d/m/Y H:i')
                    ?: 'Sin fecha';

                $registradoPor =
                    $solicitud->solicitadoPor
                        ?->nombres
                    ?: 'Usuario no disponible';

                $registradoApellidos =
                    $solicitud->solicitadoPor
                        ?->apellidos
                    ?: '';

                $revisadoPor =
                    $solicitud->revisadoPor
                        ? (
                            trim(
                                (
                                    $solicitud
                                        ->revisadoPor
                                        ->nombres
                                    ?: ''
                                ) .
                                ' ' .
                                (
                                    $solicitud
                                        ->revisadoPor
                                        ->apellidos
                                    ?: ''
                                )
                            )
                            ?: 'Administrador'
                        )
                        : null;
            @endphp

            <article class="cancelacion-card">
                <div class="cancelacion-card-header">
                    <div>
                        <div
                            class="
                                d-flex
                                flex-wrap
                                align-items-center
                                gap-2
                                mb-2
                            "
                        >
                            <span class="cancelacion-modulo">
                                Solicitud #{{ $solicitud->id }}
                            </span>

                            <span
                                class="
                                    estado-solicitud
                                    {{ $claseEstado }}
                                "
                            >
                                @if ($estado === 'aprobada')
                                    <i
                                        class="
                                            bi
                                            bi-check-circle
                                        "
                                    ></i>
                                @elseif (
                                    $estado === 'rechazada'
                                )
                                    <i
                                        class="
                                            bi
                                            bi-x-circle
                                        "
                                    ></i>
                                @elseif (
                                    $estado === 'anulada'
                                )
                                    <i
                                        class="
                                            bi
                                            bi-slash-circle
                                        "
                                    ></i>
                                @else
                                    <i
                                        class="
                                            bi
                                            bi-hourglass-split
                                        "
                                    ></i>
                                @endif

                                {{ $textoEstado }}
                            </span>
                        </div>

                        <h2>
                            {{
                                $reserva?->codigo_reserva
                                ?: 'Reserva no disponible'
                            }}
                        </h2>

                        <p>
                            {{ $nombreCliente }}
                            ·
                            {{ $nombrePaquete }}
                        </p>
                    </div>

                    <a
                        href="{{ route(
                            'cancelaciones.solicitudes.show',
                            [
                                'solicitud' =>
                                    $solicitud->id
                            ]
                        ) }}"
                        class="
                            btn-cancelacion
                            primario
                        "
                    >
                        <i
                            class="
                                bi
                                bi-folder2-open
                            "
                        ></i>

                        Abrir expediente
                    </a>
                </div>

                <div class="cancelacion-card-body">
                    <div class="solicitud-datos-grid">
                        <div class="solicitud-dato">
                            <span>
                                Tipo de cancelación
                            </span>

                            <strong>
                                {{ $textoTipo }}
                            </strong>
                        </div>

                        <div class="solicitud-dato">
                            <span>Solicitante</span>

                            <strong>
                                {{ $textoSolicitante }}
                            </strong>
                        </div>

                        <div class="solicitud-dato">
                            <span>Fecha de solicitud</span>

                            <strong>
                                {{ $fechaSolicitud }}
                            </strong>
                        </div>

                        <div class="solicitud-dato">
                            <span>Registrada por</span>

                            <strong>
                                {{
                                    trim(
                                        $registradoPor .
                                        ' ' .
                                        $registradoApellidos
                                    )
                                }}
                            </strong>
                        </div>

                        <div
                            class="
                                solicitud-dato
                                ancho-completo
                            "
                        >
                            <span>Motivo</span>

                            <strong>
                                {{
                                    \Illuminate\Support\Str::limit(
                                        $solicitud->motivo
                                        ?: 'Sin motivo registrado',
                                        220
                                    )
                                }}
                            </strong>
                        </div>

                        @if ($revisadoPor)
                            <div
                                class="
                                    solicitud-dato
                                    ancho-completo
                                "
                            >
                                <span>
                                    Revisada por
                                </span>

                                <strong>
                                    {{ $revisadoPor }}
                                </strong>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="cancelacion-card-footer">
                    @if (
                        $estado === 'pendiente'
                    )
                        <span
                            class="
                                cancelacion-texto-muted
                            "
                        >
                            <i
                                class="
                                    bi
                                    bi-shield-check
                                "
                            ></i>

                            La reserva todavía no ha sido
                            cancelada.
                        </span>
                    @elseif (
                        $estado === 'aprobada'
                    )
                        <span
                            class="
                                cancelacion-texto-exito
                            "
                        >
                            <i
                                class="
                                    bi
                                    bi-check-circle
                                "
                            ></i>

                            Cancelación aprobada.
                        </span>
                    @elseif (
                        $estado === 'rechazada'
                    )
                        <span
                            class="
                                cancelacion-texto-peligro
                            "
                        >
                            <i
                                class="
                                    bi
                                    bi-x-circle
                                "
                            ></i>

                            Solicitud rechazada.
                        </span>
                    @else
                        <span
                            class="
                                cancelacion-texto-muted
                            "
                        >
                            <i
                                class="
                                    bi
                                    bi-slash-circle
                                "
                            ></i>

                            Solicitud anulada.
                        </span>
                    @endif

                    <a
                        href="{{ route(
                            'cancelaciones.solicitudes.show',
                            [
                                'solicitud' =>
                                    $solicitud->id
                            ]
                        ) }}"
                        class="
                            btn-cancelacion
                            outline-primary
                            pequeno
                        "
                    >
                        Ver detalles
                        <i
                            class="
                                bi
                                bi-arrow-right
                            "
                        ></i>
                    </a>
                </div>
            </article>
        @empty
            <section class="cancelacion-card">
                <div class="cancelacion-card-body">
                    <div class="gastos-vacio">
                        <i
                            class="
                                bi
                                bi-clipboard-x
                            "
                        ></i>

                        <strong>
                            No existen solicitudes
                        </strong>

                        <span>
                            No se encontraron solicitudes de
                            cancelación con los filtros
                            seleccionados.
                        </span>

                        @if (
                            request('buscar') ||
                            request('estado')
                        )
                            <div class="mt-3">
                                <a
                                    href="{{ route(
                                        'cancelaciones.solicitudes.index'
                                    ) }}"
                                    class="
                                        btn-cancelacion
                                        secundario
                                        pequeno
                                    "
                                >
                                    Limpiar filtros
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </section>
        @endforelse

        @if ($solicitudes->hasPages())
            <div class="mt-4">
                {{
                    $solicitudes
                        ->withQueryString()
                        ->links()
                }}
            </div>
        @endif
    </div>
</main>
@endsection
