@extends('layouts.main')

@section('titulo', 'Gastos documentados')

@section('content')
<main id="main" class="main">
    <div class="container-fluid py-4">
        <div
            class="d-flex flex-wrap justify-content-between
                   align-items-start gap-3 mb-4"
        >
            <div>
                <span class="text-primary fw-semibold small">
                    RESERVAS Y REEMBOLSOS
                </span>

                <h1 class="h3 mb-1">
                    Gastos documentados
                </h1>

                <p class="text-muted mb-0">
                    Registra y revisa los costos no recuperables
                    asociados con reservas canceladas.
                </p>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <a
                    href="{{ route('reservas.riesgo') }}"
                    class="btn btn-outline-warning"
                >
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Reservas en riesgo
                </a>

                <a
                    href="{{ route('devoluciones.index') }}"
                    class="btn btn-outline-primary"
                >
                    <i class="bi bi-arrow-counterclockwise me-1"></i>
                    Devoluciones
                </a>

                <a
                    href="{{ route('reservas') }}"
                    class="btn btn-outline-secondary"
                >
                    <i class="bi bi-arrow-left me-1"></i>
                    Reservas
                </a>
            </div>
        </div>

        @if (session('success'))
            <div
                class="alert alert-success alert-dismissible fade show"
                role="alert"
            >
                <i class="bi bi-check-circle me-2"></i>
                {{ session('success') }}

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                ></button>
            </div>
        @endif

        @if (session('error'))
            <div
                class="alert alert-danger alert-dismissible fade show"
                role="alert"
            >
                <i class="bi bi-exclamation-circle me-2"></i>
                {{ session('error') }}

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                ></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>
                    Revisa la información:
                </strong>

                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div
                            class="d-flex align-items-center
                                   justify-content-between"
                        >
                            <div>
                                <span class="text-muted small">
                                    Pendientes de revisión
                                </span>

                                <div class="fs-3 fw-bold text-warning">
                                    {{ $metricas['pendientes'] }}
                                </div>
                            </div>

                            <div
                                class="rounded-circle bg-warning-subtle
                                       text-warning p-3"
                            >
                                <i class="bi bi-hourglass-split fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div
                            class="d-flex align-items-center
                                   justify-content-between"
                        >
                            <div>
                                <span class="text-muted small">
                                    Gastos aprobados
                                </span>

                                <div class="fs-3 fw-bold text-success">
                                    {{ $metricas['aprobados'] }}
                                </div>
                            </div>

                            <div
                                class="rounded-circle bg-success-subtle
                                       text-success p-3"
                            >
                                <i class="bi bi-check2-circle fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <span class="text-muted small">
                            Total aprobado
                        </span>

                        <div class="fs-3 fw-bold text-primary">
                            USD
                            {{ number_format(
                                $metricas['total_aprobado'],
                                2
                            ) }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <span class="text-muted small">
                            Reservas canceladas
                        </span>

                        <div class="fs-3 fw-bold">
                            {{ $metricas['reservas_canceladas'] }}
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form
                    method="GET"
                    action="{{ route('gastos-cancelacion.index') }}"
                    class="row g-3 align-items-end"
                >
                    <div class="col-12 col-lg-7">
                        <label
                            for="buscar"
                            class="form-label fw-semibold"
                        >
                            Buscar reserva o cliente
                        </label>

                        <input
                            id="buscar"
                            name="buscar"
                            type="search"
                            class="form-control"
                            value="{{ $busqueda }}"
                            placeholder="Código, nombre, apellido o documento"
                        >
                    </div>

                    <div class="col-12 col-md-6 col-lg-3">
                        <label
                            for="estado"
                            class="form-label fw-semibold"
                        >
                            Estado del gasto
                        </label>

                        <select
                            id="estado"
                            name="estado"
                            class="form-select"
                        >
                            <option value="">
                                Todos
                            </option>

                            <option
                                value="pendiente"
                                @selected(
                                    $estadoSeleccionado ===
                                    'pendiente'
                                )
                            >
                                Pendientes
                            </option>

                            <option
                                value="aprobado"
                                @selected(
                                    $estadoSeleccionado ===
                                    'aprobado'
                                )
                            >
                                Aprobados
                            </option>

                            <option
                                value="rechazado"
                                @selected(
                                    $estadoSeleccionado ===
                                    'rechazado'
                                )
                            >
                                Rechazados
                            </option>

                            <option
                                value="anulado"
                                @selected(
                                    $estadoSeleccionado ===
                                    'anulado'
                                )
                            >
                                Anulados
                            </option>
                        </select>
                    </div>

                    <div class="col-12 col-md-6 col-lg-2">
                        <button
                            type="submit"
                            class="btn btn-primary w-100"
                        >
                            <i class="bi bi-search me-1"></i>
                            Buscar
                        </button>
                    </div>
                </form>
            </div>
        </section>

        @forelse ($reservas as $reserva)
            @php
                $responsable = $reserva->esGrupal()
                    ? (
                        $reserva->grupo?->nombre_grupo ??
                        'Grupo sin nombre'
                    )
                    : (
                        $reserva->cliente?->nombre_completo ??
                        'Cliente no disponible'
                    );

                $moneda = $reserva->moneda ?: 'USD';

                $totalAprobado = (float) $reserva
                    ->gastosCancelacion
                    ->where(
                        'estado',
                        \App\Models\GastoCancelacion::
                            ESTADO_APROBADO
                    )
                    ->sum('monto');

                $estadoReembolso = match (
                    $reserva->estado_reembolso
                ) {
                    \App\Models\Reserva::
                        REEMBOLSO_PENDIENTE =>
                            'Pendiente',

                    \App\Models\Reserva::
                        REEMBOLSO_PARCIAL =>
                            'Parcial',

                    \App\Models\Reserva::
                        REEMBOLSO_COMPLETADO =>
                            'Completado',

                    \App\Models\Reserva::
                        REEMBOLSO_SIN_REEMBOLSO =>
                            'Sin reembolso',

                    default =>
                        'No aplica',
                };
            @endphp

            <article class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <div
                        class="d-flex flex-wrap justify-content-between
                               align-items-start gap-3"
                    >
                        <div>
                            <div
                                class="d-flex flex-wrap
                                       align-items-center gap-2"
                            >
                                <strong class="text-primary">
                                    {{ $reserva->codigo_reserva }}
                                </strong>

                                <span
                                    class="badge {{
                                        $reserva->esGrupal()
                                            ? 'text-bg-info'
                                            : 'text-bg-primary'
                                    }}"
                                >
                                    {{ $reserva->esGrupal()
                                        ? 'Grupal'
                                        : 'Individual' }}
                                </span>

                                @if ($reserva->cancelacion_automatica)
                                    <span class="badge text-bg-danger">
                                        Cancelación automática
                                    </span>
                                @endif
                            </div>

                            <div class="mt-1">
                                <span class="fw-semibold">
                                    {{ $responsable }}
                                </span>

                                <span class="text-muted mx-1">
                                    ·
                                </span>

                                <span class="text-muted">
                                    {{ $reserva->destino?->nombre_paquete
                                        ?? $reserva->destino?->etiqueta
                                        ?? 'Paquete no disponible' }}
                                </span>
                            </div>

                            <small class="text-muted">
                                Cancelada:
                                {{ $reserva->fecha_cancelacion
                                    ?->format('d/m/Y H:i')
                                    ?? 'Sin fecha' }}
                            </small>
                        </div>

                        <span class="badge text-bg-secondary">
                            Reembolso: {{ $estadoReembolso }}
                        </span>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-4">
                            <div
                                class="border rounded-3 p-3 h-100
                                       bg-light"
                            >
                                <small class="text-muted d-block">
                                    Pagado al cancelar
                                </small>

                                <strong class="fs-5">
                                    {{ $moneda }}
                                    {{ number_format(
                                        (float) $reserva
                                            ->monto_pagado_al_cancelar,
                                        2
                                    ) }}
                                </strong>
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div
                                class="border rounded-3 p-3 h-100
                                       bg-light"
                            >
                                <small class="text-muted d-block">
                                    Gastos aprobados
                                </small>

                                <strong class="fs-5 text-danger">
                                    {{ $moneda }}
                                    {{ number_format(
                                        $totalAprobado,
                                        2
                                    ) }}
                                </strong>
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div
                                class="border border-success rounded-3
                                       p-3 h-100 bg-success-subtle"
                            >
                                <small class="text-muted d-block">
                                    Reembolso documentado
                                </small>

                                <strong class="fs-5 text-success">
                                    {{ $moneda }}
                                    {{ number_format(
                                        $reserva
                                            ->monto_reembolsable_documentado,
                                        2
                                    ) }}
                                </strong>
                            </div>
                        </div>
                    </div>

                    <details class="border rounded-3 mb-4">
                        <summary
                            class="p-3 fw-semibold text-primary"
                            style="cursor: pointer"
                        >
                            <i class="bi bi-file-earmark-plus me-1"></i>
                            Registrar gasto y comprobante
                        </summary>

                        <div class="border-top p-3">
                            <form
                                method="POST"
                                action="{{ route(
                                    'gastos-cancelacion.store',
                                    $reserva
                                ) }}"
                                enctype="multipart/form-data"
                                class="row g-3"
                            >
                                @csrf

                                <div class="col-12 col-md-6">
                                    <label
                                        class="form-label fw-semibold"
                                        for="proveedor_{{ $reserva->id }}"
                                    >
                                        Proveedor *
                                    </label>

                                    <input
                                        id="proveedor_{{ $reserva->id }}"
                                        name="proveedor"
                                        type="text"
                                        class="form-control"
                                        maxlength="150"
                                        placeholder="Aerolínea, hotel u operador"
                                        required
                                    >
                                </div>

                                <div class="col-12 col-md-6">
                                    <label
                                        class="form-label fw-semibold"
                                        for="concepto_{{ $reserva->id }}"
                                    >
                                        Concepto *
                                    </label>

                                    <input
                                        id="concepto_{{ $reserva->id }}"
                                        name="concepto"
                                        type="text"
                                        class="form-control"
                                        maxlength="200"
                                        placeholder="Penalidad o servicio no recuperable"
                                        required
                                    >
                                </div>

                                <div class="col-12 col-md-4">
                                    <label
                                        class="form-label fw-semibold"
                                        for="monto_{{ $reserva->id }}"
                                    >
                                        Monto *
                                    </label>

                                    <div class="input-group">
                                        <span class="input-group-text">
                                            {{ $moneda }}
                                        </span>

                                        <input
                                            id="monto_{{ $reserva->id }}"
                                            name="monto"
                                            type="number"
                                            class="form-control"
                                            min="0.01"
                                            step="0.01"
                                            required
                                        >
                                    </div>
                                </div>

                                <div class="col-12 col-md-4">
                                    <label
                                        class="form-label fw-semibold"
                                        for="documento_{{ $reserva->id }}"
                                    >
                                        Número de documento
                                    </label>

                                    <input
                                        id="documento_{{ $reserva->id }}"
                                        name="numero_documento"
                                        type="text"
                                        class="form-control"
                                        maxlength="100"
                                    >
                                </div>

                                <div class="col-12 col-md-4">
                                    <label
                                        class="form-label fw-semibold"
                                        for="fecha_{{ $reserva->id }}"
                                    >
                                        Fecha del comprobante
                                    </label>

                                    <input
                                        id="fecha_{{ $reserva->id }}"
                                        name="fecha_documento"
                                        type="date"
                                        class="form-control"
                                        max="{{ now()->toDateString() }}"
                                    >
                                </div>

                                <div class="col-12">
                                    <label
                                        class="form-label fw-semibold"
                                        for="archivo_{{ $reserva->id }}"
                                    >
                                        Comprobante privado *
                                    </label>

                                    <input
                                        id="archivo_{{ $reserva->id }}"
                                        name="archivo"
                                        type="file"
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
                                        class="form-label fw-semibold"
                                        for="observaciones_{{ $reserva->id }}"
                                    >
                                        Observaciones
                                    </label>

                                    <textarea
                                        id="observaciones_{{ $reserva->id }}"
                                        name="observaciones"
                                        class="form-control"
                                        rows="2"
                                        maxlength="2000"
                                    ></textarea>
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
                        </div>
                    </details>

                    <h2 class="h6 mb-3">
                        Comprobantes registrados
                    </h2>

                    @forelse (
                        $reserva->gastosCancelacion as $gasto
                    )
                        @php
                            $claseEstado = match (
                                $gasto->estado
                            ) {
                                'aprobado' =>
                                    'text-bg-success',

                                'rechazado' =>
                                    'text-bg-danger',

                                'anulado' =>
                                    'text-bg-secondary',

                                default =>
                                    'text-bg-warning',
                            };
                        @endphp

                        <div class="border rounded-3 p-3 mb-3">
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
                                            class="badge {{ $claseEstado }}"
                                        >
                                            {{ ucfirst($gasto->estado) }}
                                        </span>
                                    </div>

                                    <div class="text-muted small mt-1">
                                        {{ $gasto->proveedor }}

                                        @if ($gasto->numero_documento)
                                            · Documento:
                                            {{ $gasto->numero_documento }}
                                        @endif
                                    </div>

                                    <small class="text-muted">
                                        Registrado por
                                        {{ $gasto->registradoPor?->nombres
                                            ?? 'Usuario' }}
                                        ·
                                        {{ $gasto->created_at
                                            ?->format('d/m/Y H:i') }}
                                    </small>
                                </div>

                                <div class="text-end">
                                    <strong class="d-block fs-5">
                                        {{ $moneda }}
                                        {{ number_format(
                                            (float) $gasto->monto,
                                            2
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

                            <div
                                class="d-flex flex-wrap gap-2 mt-3"
                            >
                                <a
                                    href="{{ route(
                                        'gastos-cancelacion.descargar',
                                        $gasto
                                    ) }}"
                                    class="btn btn-sm btn-outline-primary"
                                >
                                    <i class="bi bi-download me-1"></i>
                                    Descargar comprobante
                                </a>

                                @if (
                                    auth()->user()->isAdmin() &&
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

                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-success"
                                            onclick="return confirm(
                                                '¿Aprobar este gasto y recalcular el reembolso?'
                                            )"
                                        >
                                            <i class="bi bi-check-lg me-1"></i>
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
                                            class="border rounded p-3 mt-2"
                                            style="min-width: 290px"
                                        >
                                            @csrf
                                            @method('PATCH')

                                            <label class="form-label small">
                                                Motivo del rechazo
                                            </label>

                                            <textarea
                                                name="motivo_revision"
                                                class="form-control
                                                       form-control-sm"
                                                rows="2"
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
                                    auth()->user()->isAdmin() &&
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
                                            class="border rounded p-3 mt-2"
                                            style="min-width: 290px"
                                        >
                                            @csrf
                                            @method('PATCH')

                                            <label class="form-label small">
                                                Motivo de anulación
                                            </label>

                                            <textarea
                                                name="motivo_anulacion"
                                                class="form-control
                                                       form-control-sm"
                                                rows="2"
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
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted">
                            <i
                                class="bi bi-file-earmark-x
                                       fs-1 d-block mb-2"
                            ></i>

                            Esta reserva todavía no tiene
                            gastos documentados.
                        </div>
                    @endforelse
                </div>
            </article>
        @empty
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    <i
                        class="bi bi-folder2-open
                               fs-1 text-muted d-block mb-3"
                    ></i>

                    <h2 class="h5">
                        No hay reservas canceladas
                    </h2>

                    <p class="text-muted mb-0">
                        Cuando una reserva sea cancelada,
                        aparecerá en este apartado.
                    </p>
                </div>
            </div>
        @endforelse

        @if ($reservas->hasPages())
            <div class="mt-4">
                {{ $reservas->links() }}
            </div>
        @endif
    </div>
</main>
@endsection