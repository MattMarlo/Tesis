@extends('layouts.main')

@section('titulo', 'Expediente de cancelación')

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
@php
    $moneda = strtoupper(
        $reserva->moneda ?: 'USD'
    );

    $usuarioActual = auth()->user();

    $esAdministrador =
        $usuarioActual &&
        $usuarioActual->isAdmin();

    $esPendiente =
        $solicitud->estado ===
        \App\Models\SolicitudCancelacion::
            ESTADO_PENDIENTE;

    $esAprobada =
        $solicitud->estado ===
        \App\Models\SolicitudCancelacion::
            ESTADO_APROBADA;

    $esRechazada =
        $solicitud->estado ===
        \App\Models\SolicitudCancelacion::
            ESTADO_RECHAZADA;

    $esAnulada =
        $solicitud->estado ===
        \App\Models\SolicitudCancelacion::
            ESTADO_ANULADA;

    $responsable = $reserva->esGrupal()
        ? (
            $reserva->grupo?->nombre_grupo
            ?? 'Grupo sin nombre'
        )
        : trim(
            ($reserva->cliente?->nombres ?? '') .
            ' ' .
            ($reserva->cliente?->apellidos ?? '')
        );

    $nombrePaquete =
        $reserva->destino?->nombre_paquete
        ?? $reserva->destino?->etiqueta
        ?? $reserva->destino?->pais
        ?? 'Paquete no disponible';

    $tiposSinDescuento = [
        \App\Models\SolicitudCancelacion::
            TIPO_RESPONSABILIDAD_AGENCIA,

        \App\Models\SolicitudCancelacion::
            TIPO_PROBLEMA_PROVEEDOR,
    ];

    $permiteDescontarGastos = !in_array(
        $solicitud->tipo_cancelacion,
        $tiposSinDescuento,
        true
    );

    $gastos =
        $reserva->gastosCancelacion;
@endphp

<main
    id="main"
    class="
        main
        pagina-solicitud-cancelacion
        pagina-expediente-cancelacion
    "
>
    <header class="solicitud-encabezado">
        <div>
            <span class="solicitud-modulo">
                Cancelaciones y reembolsos
            </span>

            <h1>Expediente de cancelación</h1>

            <p>
                Revisa la solicitud, los pagos, los comprobantes
                y el reembolso estimado antes de tomar una decisión.
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a
                href="{{ route(
                    'cancelaciones.solicitudes.index'
                ) }}"
                class="boton-volver"
            >
                <i class="bi bi-list-check"></i>
                Solicitudes
            </a>

            <a
                href="{{ route('reservas') }}"
                class="boton-volver"
            >
                <i class="bi bi-arrow-left"></i>
                Reservas
            </a>
        </div>
    </header>

    @if (session('success'))
        <div class="alerta-solicitud exito">
            <i class="bi bi-check-circle"></i>

            <div>
                <strong>Proceso completado</strong>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="alerta-solicitud error">
            <i class="bi bi-exclamation-circle"></i>

            <div>
                <strong>No se pudo completar la acción</strong>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    @if (session('info'))
        <div class="alerta-solicitud informacion">
            <i class="bi bi-info-circle"></i>

            <div>
                <strong>Información</strong>
                <span>{{ session('info') }}</span>
            </div>
        </div>
    @endif

    @if ($errors->any())
        <div class="alerta-solicitud error">
            <i class="bi bi-exclamation-circle"></i>

            <div>
                <strong>Revisa la información</strong>

                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <section class="expediente-identificacion">
        <div>
            <span>Solicitud</span>
            <strong>#{{ $solicitud->id }}</strong>
        </div>

        <div>
            <span>Reserva</span>
            <strong>{{ $reserva->codigo_reserva }}</strong>
        </div>

        <div>
            <span>Registrada</span>
            <strong>
                {{ $solicitud->solicitado_at
                    ?->format('d/m/Y H:i') }}
            </strong>
        </div>

        <div>
            <span>Estado</span>

            <strong
                class="estado-expediente
                    estado-{{ $solicitud->estado }}"
            >
                {{ $solicitud->estado_legible }}
            </strong>
        </div>
    </section>

    <section class="expediente-progreso">
        <div class="paso completado">
            <span>1</span>

            <div>
                <strong>Solicitud recibida</strong>
                <small>Información registrada</small>
            </div>
        </div>

        <div
            class="paso {{
                $esPendiente
                    ? 'activo'
                    : 'completado'
            }}"
        >
            <span>2</span>

            <div>
                <strong>Revisión documental</strong>
                <small>Comprobantes y gastos</small>
            </div>
        </div>

        <div
            class="paso {{
                $esAprobada ||
                $esRechazada ||
                $esAnulada
                    ? 'completado'
                    : ''
            }}"
        >
            <span>3</span>

            <div>
                <strong>Decisión administrativa</strong>
                <small>Aprobar o rechazar</small>
            </div>
        </div>

        <div
            class="paso {{
                $esAprobada
                    ? 'activo'
                    : ''
            }}"
        >
            <span>4</span>

            <div>
                <strong>Devolución</strong>
                <small>Solo después de cancelar</small>
            </div>
        </div>
    </section>

    <div class="row g-4 align-items-start">
        <div class="col-12 col-xl-8">
            <section class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <div
                        class="d-flex align-items-center
                               justify-content-between gap-3"
                    >
                        <div>
                            <span class="text-primary small fw-bold">
                                INFORMACIÓN DE LA SOLICITUD
                            </span>

                            <h2 class="h5 mb-0 mt-1">
                                Motivo y evidencia
                            </h2>
                        </div>

                        <span class="badge text-bg-light border">
                            {{ $solicitud->tipo_legible }}
                        </span>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-4">
                            <div class="dato-expediente">
                                <span>Solicitante</span>

                                <strong>
                                    {{ ucfirst(
                                        $solicitud->solicitante
                                    ) }}
                                </strong>
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="dato-expediente">
                                <span>Canal</span>

                                <strong>
                                    {{ $solicitud
                                        ->canal_legible }}
                                </strong>
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="dato-expediente">
                                <span>Registrado por</span>

                                <strong>
                                    {{ trim(
                                        ($solicitud
                                            ->solicitadoPor
                                            ?->nombres ?? '') .
                                        ' ' .
                                        ($solicitud
                                            ->solicitadoPor
                                            ?->apellidos ?? '')
                                    ) ?: 'Usuario no disponible' }}
                                </strong>
                            </div>
                        </div>
                    </div>

                    <div class="motivo-expediente">
                        <span>Motivo detallado</span>
                        <p>{{ $solicitud->motivo }}</p>
                    </div>

                    @if (
                        $solicitud
                            ->referencia_comunicacion
                    )
                        <div class="referencia-expediente">
                            <i class="bi bi-chat-left-text"></i>

                            <div>
                                <span>
                                    Referencia de comunicación
                                </span>

                                <strong>
                                    {{ $solicitud
                                        ->referencia_comunicacion }}
                                </strong>
                            </div>
                        </div>
                    @endif

                    @if (
                        $solicitud
                            ->observaciones_internas
                    )
                        <div class="referencia-expediente">
                            <i class="bi bi-journal-text"></i>

                            <div>
                                <span>
                                    Observaciones internas
                                </span>

                                <strong>
                                    {{ $solicitud
                                        ->observaciones_internas }}
                                </strong>
                            </div>
                        </div>
                    @endif

                    <div class="evidencia-expediente">
                        <div>
                            <span>Evidencia del motivo</span>

                            @if (
                                $solicitud
                                    ->tieneEvidencia()
                            )
                                <strong>
                                    {{ $solicitud
                                        ->evidencia_nombre_original }}
                                </strong>

                                <small>
                                    {{ $solicitud
                                        ->tamano_evidencia_legible }}
                                </small>
                            @else
                                <strong>
                                    Sin evidencia adjunta
                                </strong>

                                <small>
                                    No era obligatoria para
                                    este tipo de solicitud.
                                </small>
                            @endif
                        </div>

                        @if (
                            $solicitud
                                ->tieneEvidencia()
                        )
                            <a
                                href="{{ route(
                                    'cancelaciones.solicitudes.evidencia',
                                    $solicitud
                                ) }}"
                                class="btn btn-outline-primary btn-sm"
                            >
                                <i class="bi bi-download me-1"></i>
                                Descargar
                            </a>
                        @endif
                    </div>
                </div>
            </section>

            <section class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <div
                        class="d-flex flex-wrap align-items-center
                               justify-content-between gap-3"
                    >
                        <div>
                            <span class="text-primary small fw-bold">
                                LIQUIDACIÓN
                            </span>

                            <h2 class="h5 mb-0 mt-1">
                                Gastos documentados
                            </h2>
                        </div>

                        <span class="badge text-bg-warning">
                            {{ $cantidadGastosPendientes }}
                            pendiente(s)
                        </span>
                    </div>
                </div>

                <div class="card-body">
                    @if (!$permiteDescontarGastos)
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>

                            Cuando la cancelación es responsabilidad
                            de la agencia o del proveedor, sus costos
                            no pueden descontarse al cliente.
                        </div>
                    @elseif ($esPendiente)
                        <details
                            class="detalles-adicionales mb-4"
                            @if ($errors->has('archivo'))
                                open
                            @endif
                        >
                            <summary>
                                <i class="bi bi-file-earmark-plus"></i>
                                Registrar gasto y comprobante
                            </summary>

                            <form
                                method="POST"
                                action="{{ route(
                                    'gastos-cancelacion.store',
                                    $reserva
                                ) }}"
                                enctype="multipart/form-data"
                                class="row g-3 p-3 pt-1"
                            >
                                @csrf

                                <input
                                    type="hidden"
                                    name="solicitud_id"
                                    value="{{ $solicitud->id }}"
                                >

                                <div class="col-12 col-md-6">
                                    <label
                                        for="proveedor"
                                        class="form-label fw-semibold"
                                    >
                                        Proveedor *
                                    </label>

                                    <input
                                        id="proveedor"
                                        type="text"
                                        name="proveedor"
                                        class="form-control"
                                        maxlength="150"
                                        value="{{ old('proveedor') }}"
                                        placeholder="Aerolínea, hotel u operador"
                                        required
                                    >
                                </div>

                                <div class="col-12 col-md-6">
                                    <label
                                        for="concepto"
                                        class="form-label fw-semibold"
                                    >
                                        Concepto *
                                    </label>

                                    <input
                                        id="concepto"
                                        type="text"
                                        name="concepto"
                                        class="form-control"
                                        maxlength="200"
                                        value="{{ old('concepto') }}"
                                        placeholder="Penalidad o servicio no recuperable"
                                        required
                                    >
                                </div>

                                <div class="col-12 col-md-4">
                                    <label
                                        for="monto"
                                        class="form-label fw-semibold"
                                    >
                                        Monto *
                                    </label>

                                    <div class="input-group">
                                        <span class="input-group-text">
                                            {{ $moneda }}
                                        </span>

                                        <input
                                            id="monto"
                                            type="number"
                                            name="monto"
                                            class="form-control"
                                            min="0.01"
                                            step="0.01"
                                            value="{{ old('monto') }}"
                                            required
                                        >
                                    </div>
                                </div>

                                <div class="col-12 col-md-4">
                                    <label
                                        for="numeroDocumento"
                                        class="form-label fw-semibold"
                                    >
                                        Número de documento
                                    </label>

                                    <input
                                        id="numeroDocumento"
                                        type="text"
                                        name="numero_documento"
                                        class="form-control"
                                        maxlength="100"
                                        value="{{ old(
                                            'numero_documento'
                                        ) }}"
                                    >
                                </div>

                                <div class="col-12 col-md-4">
                                    <label
                                        for="fechaDocumento"
                                        class="form-label fw-semibold"
                                    >
                                        Fecha del comprobante
                                    </label>

                                    <input
                                        id="fechaDocumento"
                                        type="date"
                                        name="fecha_documento"
                                        class="form-control"
                                        max="{{ now()->toDateString() }}"
                                        value="{{ old(
                                            'fecha_documento'
                                        ) }}"
                                    >
                                </div>

                                <div class="col-12">
                                    <label
                                        for="archivo"
                                        class="form-label fw-semibold"
                                    >
                                        Comprobante privado *
                                    </label>

                                    <input
                                        id="archivo"
                                        type="file"
                                        name="archivo"
                                        class="form-control"
                                        accept=".pdf,.jpg,.jpeg,.png,.webp"
                                        required
                                    >

                                    <small class="text-muted">
                                        PDF, JPG, PNG o WEBP.
                                        Máximo 10 MB.
                                    </small>
                                </div>

                                <div class="col-12">
                                    <label
                                        for="observaciones"
                                        class="form-label fw-semibold"
                                    >
                                        Observaciones
                                    </label>

                                    <textarea
                                        id="observaciones"
                                        name="observaciones"
                                        class="form-control"
                                        rows="3"
                                        maxlength="2000"
                                    >{{ old('observaciones') }}</textarea>
                                </div>

                                <div class="col-12 text-end">
                                    <button
                                        type="submit"
                                        class="btn btn-primary"
                                    >
                                        <i class="bi bi-shield-lock me-1"></i>
                                        Guardar comprobante
                                    </button>
                                </div>
                            </form>
                        </details>
                    @endif

                    <h3 class="h6 mb-3">
                        Comprobantes registrados
                    </h3>

                    @forelse ($gastos as $gasto)
                        @php
                            $claseEstado = match (
                                $gasto->estado
                            ) {
                                \App\Models\GastoCancelacion::
                                    ESTADO_APROBADO =>
                                        'text-bg-success',

                                \App\Models\GastoCancelacion::
                                    ESTADO_RECHAZADO =>
                                        'text-bg-danger',

                                \App\Models\GastoCancelacion::
                                    ESTADO_ANULADO =>
                                        'text-bg-secondary',

                                default =>
                                    'text-bg-warning',
                            };
                        @endphp

                        <article class="comprobante-expediente">
                            <div
                                class="d-flex flex-wrap
                                       justify-content-between gap-3"
                            >
                                <div>
                                    <div
                                        class="d-flex flex-wrap
                                               align-items-center gap-2"
                                    >
                                        <strong>
                                            {{ $gasto->concepto }}
                                        </strong>

                                        <span
                                            class="badge
                                                {{ $claseEstado }}"
                                        >
                                            {{ ucfirst(
                                                $gasto->estado
                                            ) }}
                                        </span>
                                    </div>

                                    <div class="text-muted small mt-1">
                                        {{ $gasto->proveedor }}

                                        @if (
                                            $gasto
                                                ->numero_documento
                                        )
                                            · Documento:
                                            {{ $gasto
                                                ->numero_documento }}
                                        @endif
                                    </div>

                                    <small class="text-muted">
                                        Registrado por
                                        {{ $gasto
                                            ->registradoPor
                                            ?->nombres
                                            ?? 'Usuario' }}

                                        ·

                                        {{ $gasto->created_at
                                            ?->format('d/m/Y H:i') }}
                                    </small>
                                </div>

                                <div class="text-end">
                                    <strong class="fs-5 d-block">
                                        {{ $moneda }}
                                        {{ number_format(
                                            (float) $gasto->monto,
                                            2,
                                            '.',
                                            ','
                                        ) }}
                                    </strong>

                                    <small class="text-muted">
                                        {{ $gasto->tamano_legible }}
                                    </small>
                                </div>
                            </div>

                            @if ($gasto->observaciones)
                                <p class="small mt-3 mb-0">
                                    {{ $gasto->observaciones }}
                                </p>
                            @endif

                            @if ($gasto->motivo_revision)
                                <div
                                    class="alert alert-light border
                                           small mt-3 mb-0"
                                >
                                    <strong>Revisión:</strong>
                                    {{ $gasto->motivo_revision }}
                                </div>
                            @endif

                            <div class="d-flex flex-wrap gap-2 mt-3">
                                <a
                                    href="{{ route(
                                        'gastos-cancelacion.descargar',
                                        $gasto
                                    ) }}"
                                    class="btn btn-sm
                                           btn-outline-primary"
                                >
                                    <i class="bi bi-download me-1"></i>
                                    Descargar
                                </a>

                                @if (
                                    $esAdministrador &&
                                    $esPendiente &&
                                    $gasto->estaPendiente()
                                )
                                    <form
                                        method="POST"
                                        action="{{ route(
                                            'gastos-cancelacion.aprobar',
                                            $gasto
                                        ) }}"
                                    >
                                        @csrf
                                        @method('PATCH')

                                        <input
                                            type="hidden"
                                            name="solicitud_id"
                                            value="{{ $solicitud->id }}"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-success"
                                            onclick="return confirm(
                                                '¿Aprobar este gasto?'
                                            )"
                                        >
                                            <i class="bi bi-check-lg"></i>
                                            Aprobar
                                        </button>
                                    </form>

                                    <details>
                                        <summary
                                            class="btn btn-sm
                                                   btn-outline-danger"
                                        >
                                            Rechazar
                                        </summary>

                                        <form
                                            method="POST"
                                            action="{{ route(
                                                'gastos-cancelacion.rechazar',
                                                $gasto
                                            ) }}"
                                            class="formulario-revision"
                                        >
                                            @csrf
                                            @method('PATCH')

                                            <input
                                                type="hidden"
                                                name="solicitud_id"
                                                value="{{ $solicitud->id }}"
                                            >

                                            <label>
                                                Motivo del rechazo
                                            </label>

                                            <textarea
                                                name="motivo_revision"
                                                rows="3"
                                                minlength="10"
                                                required
                                            ></textarea>

                                            <button
                                                type="submit"
                                                class="btn btn-sm
                                                       btn-danger mt-2"
                                            >
                                                Confirmar rechazo
                                            </button>
                                        </form>
                                    </details>
                                @endif

                                @if (
                                    $esAdministrador &&
                                    $esPendiente &&
                                    $gasto->estaAprobado()
                                )
                                    <details>
                                        <summary
                                            class="btn btn-sm
                                                   btn-outline-secondary"
                                        >
                                            Anular gasto
                                        </summary>

                                        <form
                                            method="POST"
                                            action="{{ route(
                                                'gastos-cancelacion.anular',
                                                $gasto
                                            ) }}"
                                            class="formulario-revision"
                                        >
                                            @csrf
                                            @method('PATCH')

                                            <input
                                                type="hidden"
                                                name="solicitud_id"
                                                value="{{ $solicitud->id }}"
                                            >

                                            <label>
                                                Motivo de anulación
                                            </label>

                                            <textarea
                                                name="motivo_anulacion"
                                                rows="3"
                                                minlength="10"
                                                required
                                            ></textarea>

                                            <button
                                                type="submit"
                                                class="btn btn-sm
                                                       btn-secondary mt-2"
                                            >
                                                Confirmar anulación
                                            </button>
                                        </form>
                                    </details>
                                @endif
                            </div>
                        </article>
                    @empty
                        <div class="expediente-vacio">
                            <i class="bi bi-file-earmark-x"></i>

                            <strong>
                                No existen gastos documentados
                            </strong>

                            <span>
                                Si no hay costos no recuperables,
                                el administrador deberá confirmarlo
                                al aprobar la cancelación.
                            </span>
                        </div>
                    @endforelse
                </div>
            </section>

            @if (
                $esPendiente &&
                (
                    $usuarioActual->id ===
                        $solicitud
                            ->solicitado_por_user_id ||
                    $esAdministrador
                )
            )
                <section class="card border-0 shadow-sm">
                    <div class="card-body">
                        <details>
                            <summary
                                class="btn btn-sm
                                       btn-outline-secondary"
                            >
                                Anular esta solicitud
                            </summary>

                            <form
                                method="POST"
                                action="{{ route(
                                    'cancelaciones.solicitudes.anular',
                                    $solicitud
                                ) }}"
                                class="formulario-revision mt-3"
                            >
                                @csrf
                                @method('PATCH')

                                <label>
                                    Motivo de anulación
                                </label>

                                <textarea
                                    name="motivo_anulacion"
                                    rows="3"
                                    minlength="10"
                                    required
                                ></textarea>

                                <button
                                    type="submit"
                                    class="btn btn-secondary mt-2"
                                >
                                    Confirmar anulación
                                </button>
                            </form>
                        </details>
                    </div>
                </section>
            @endif
        </div>

        <div class="col-12 col-xl-4">
            <aside class="panel-lateral-expediente">
                <section class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <span class="text-primary small fw-bold">
                            RESUMEN FINANCIERO
                        </span>

                        <h2 class="h5 mb-0 mt-1">
                            Liquidación estimada
                        </h2>
                    </div>

                    <div class="card-body">
                        <div class="dato-financiero">
                            <span>Pagado por el cliente</span>

                            <strong>
                                {{ $moneda }}
                                {{ number_format(
                                    $pagadoBruto,
                                    2,
                                    '.',
                                    ','
                                ) }}
                            </strong>
                        </div>

                        <div class="dato-financiero pendiente">
                            <span>Gastos pendientes</span>

                            <strong>
                                {{ $moneda }}
                                {{ number_format(
                                    $gastosPendientes,
                                    2,
                                    '.',
                                    ','
                                ) }}
                            </strong>
                        </div>

                        <div class="dato-financiero gastos">
                            <span>Gastos aprobados</span>

                            <strong>
                                {{ $moneda }}
                                {{ number_format(
                                    $gastosAprobados,
                                    2,
                                    '.',
                                    ','
                                ) }}
                            </strong>
                        </div>

                        <div class="dato-financiero reembolso">
                            <span>Reembolso estimado</span>

                            <strong>
                                {{ $moneda }}
                                {{ number_format(
                                    $reembolsoEstimado,
                                    2,
                                    '.',
                                    ','
                                ) }}
                            </strong>
                        </div>

                        <small class="nota-financiera">
                            El reembolso definitivo se establece
                            únicamente al aprobar la cancelación.
                        </small>
                    </div>
                </section>

                <section class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <span class="text-primary small fw-bold">
                            RESERVA
                        </span>

                        <h2 class="h6 mb-0 mt-1">
                            {{ $reserva->codigo_reserva }}
                        </h2>
                    </div>

                    <div class="card-body">
                        <div class="dato-reserva-lateral">
                            <span>Cliente o grupo</span>
                            <strong>{{ $responsable }}</strong>
                        </div>

                        <div class="dato-reserva-lateral">
                            <span>Paquete</span>
                            <strong>{{ $nombrePaquete }}</strong>
                        </div>

                        <div class="dato-reserva-lateral">
                            <span>Fecha del viaje</span>

                            <strong>
                                {{ $reserva->fecha_viaje
                                    ?->format('d/m/Y')
                                    ?? 'Sin fecha' }}
                            </strong>
                        </div>

                        <div class="dato-reserva-lateral">
                            <span>Estado actual</span>

                            <strong>
                                {{ ucfirst(
                                    $reserva->estado
                                ) }}
                            </strong>
                        </div>
                    </div>
                </section>

                @if (
                    $esAdministrador &&
                    $esPendiente
                )
                    <section class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3">
                            <span class="text-primary small fw-bold">
                                DECISIÓN ADMINISTRATIVA
                            </span>

                            <h2 class="h5 mb-0 mt-1">
                                Resolver solicitud
                            </h2>
                        </div>

                        <div class="card-body">
                            @if (
                                $cantidadGastosPendientes > 0
                            )
                                <div class="alert alert-warning small">
                                    <i
                                        class="bi bi-hourglass-split
                                               me-1"
                                    ></i>

                                    Debes aprobar o rechazar todos
                                    los gastos pendientes.
                                </div>
                            @endif

                            <form
                                method="POST"
                                action="{{ route(
                                    'cancelaciones.solicitudes.aprobar',
                                    $solicitud
                                ) }}"
                            >
                                @csrf
                                @method('PATCH')

                                @if (
                                    $gastosAprobados <= 0
                                )
                                    <div
                                        class="form-check
                                               confirmacion-sin-gastos"
                                    >
                                        <input
                                            id="confirmarSinGastos"
                                            type="checkbox"
                                            name="confirmar_sin_gastos"
                                            value="1"
                                            class="form-check-input"
                                            required
                                        >

                                        <label
                                            for="confirmarSinGastos"
                                            class="form-check-label"
                                        >
                                            Confirmo que no existen
                                            gastos no reembolsables
                                            pendientes de documentar.
                                        </label>
                                    </div>
                                @endif

                                <label
                                    for="observacionRevision"
                                    class="form-label fw-semibold
                                           small mt-3"
                                >
                                    Observación de aprobación
                                </label>

                                <textarea
                                    id="observacionRevision"
                                    name="observacion_revision"
                                    class="form-control"
                                    rows="3"
                                    maxlength="1000"
                                ></textarea>

                                <button
                                    type="submit"
                                    class="btn btn-success w-100 mt-3"
                                    @disabled(
                                        $cantidadGastosPendientes > 0
                                    )
                                    onclick="return confirm(
                                        '¿Aprobar y cancelar definitivamente esta reserva?'
                                    )"
                                >
                                    <i class="bi bi-check-circle me-1"></i>
                                    Aprobar cancelación
                                </button>
                            </form>

                            <hr>

                            <details>
                                <summary
                                    class="btn btn-outline-danger
                                           w-100"
                                >
                                    Rechazar solicitud
                                </summary>

                                <form
                                    method="POST"
                                    action="{{ route(
                                        'cancelaciones.solicitudes.rechazar',
                                        $solicitud
                                    ) }}"
                                    class="mt-3"
                                >
                                    @csrf
                                    @method('PATCH')

                                    <label
                                        for="motivoRechazo"
                                        class="form-label fw-semibold
                                               small"
                                    >
                                        Motivo del rechazo
                                    </label>

                                    <textarea
                                        id="motivoRechazo"
                                        name="motivo_revision"
                                        class="form-control"
                                        rows="3"
                                        minlength="10"
                                        maxlength="1000"
                                        required
                                    ></textarea>

                                    <button
                                        type="submit"
                                        class="btn btn-danger
                                               w-100 mt-2"
                                    >
                                        Confirmar rechazo
                                    </button>
                                </form>
                            </details>
                        </div>
                    </section>
                @else
                    <section class="card border-0 shadow-sm">
                        <div class="card-body text-center py-4">
                            @if ($esAprobada)
                                <i
                                    class="bi bi-check-circle
                                           text-success fs-1"
                                ></i>

                                <h2 class="h6 mt-3">
                                    Cancelación aprobada
                                </h2>

                                <p class="text-muted small">
                                    La reserva fue cancelada y su
                                    devolución puede ser procesada.
                                </p>

                                <a
                                    href="{{ route(
                                        'devoluciones.index'
                                    ) }}"
                                    class="btn btn-outline-primary"
                                >
                                    Ir a devoluciones
                                </a>
                            @elseif ($esRechazada)
                                <i
                                    class="bi bi-x-circle
                                           text-danger fs-1"
                                ></i>

                                <h2 class="h6 mt-3">
                                    Solicitud rechazada
                                </h2>

                                <p class="text-muted small mb-0">
                                    {{ $solicitud
                                        ->motivo_revision }}
                                </p>
                            @elseif ($esAnulada)
                                <i
                                    class="bi bi-dash-circle
                                           text-secondary fs-1"
                                ></i>

                                <h2 class="h6 mt-3">
                                    Solicitud anulada
                                </h2>

                                <p class="text-muted small mb-0">
                                    {{ $solicitud
                                        ->motivo_revision }}
                                </p>
                            @else
                                <i
                                    class="bi bi-clock-history
                                           text-warning fs-1"
                                ></i>

                                <h2 class="h6 mt-3">
                                    Pendiente de administrador
                                </h2>
                            @endif
                        </div>
                    </section>
                @endif
            </aside>
        </div>
    </div>
</main>
@endsection