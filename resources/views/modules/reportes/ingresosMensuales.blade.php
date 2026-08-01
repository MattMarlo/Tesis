@extends('layouts.main')

@section('titulo', $titulo)

@section('content')

<link
    rel="stylesheet"
    href="{{ asset('css/reporte-financiero.css') }}"
>

@php
    $periodo = $mes
        ? $nombreMes . ' de ' . $anio
        : 'Año ' . $anio;

    $totalMetodos = collect(
        $metodos_pago
    )->sum('total');
@endphp

<main
    id="main"
    class="main pagina-reporte"
>
    <header class="reporte-encabezado">
        <div>
            <h1>Reporte financiero</h1>

            <p>
                Consulta los pagos registrados y saldos
                pendientes de la agencia.
            </p>
        </div>

        <form
            method="GET"
            action="{{ route('reportes.ingresos') }}"
            class="reporte-filtros"
        >
            <div class="reporte-filtro">
                <label for="anio">
                    Año
                </label>

                <select
                    id="anio"
                    name="anio"
                >
                    @foreach ($years as $year)
                        <option
                            value="{{ $year }}"
                            @selected(
                                (int) $year ===
                                (int) $anio
                            )
                        >
                            {{ $year }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="reporte-filtro">
                <label for="mes">
                    Mes
                </label>

                <select
                    id="mes"
                    name="mes"
                >
                    <option value="">
                        Todos los meses
                    </option>

                    @foreach (range(1, 12) as $numeroMes)
                        <option
                            value="{{ $numeroMes }}"
                            @selected(
                                (int) $mes ===
                                $numeroMes
                            )
                        >
                            {{
                                ucfirst(
                                    \Carbon\Carbon::create(
                                        $anio,
                                        $numeroMes,
                                        1
                                    )
                                        ->locale('es')
                                        ->translatedFormat('F')
                                )
                            }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button
                type="submit"
                class="btn-filtrar-reporte"
            >
                <i class="bi bi-funnel"></i>
                Consultar
            </button>

            <a
                href="{{ route('reportes.ingresos') }}"
                class="btn-limpiar-reporte"
                title="Restablecer filtros"
            >
                <i class="bi bi-arrow-counterclockwise"></i>
            </a>
        </form>
    </header>

    <section class="reporte-resumen">
        <article class="tarjeta-reporte">
            <span class="tarjeta-reporte-icono">
                <i class="bi bi-cash-stack"></i>
            </span>

            <div class="tarjeta-reporte-contenido">
                <span>Total cobrado</span>

                <strong>
                    USD {{
                        number_format(
                            $total_cobrado,
                            2
                        )
                    }}
                </strong>

                <small>{{ $periodo }}</small>
            </div>
        </article>

        <article class="tarjeta-reporte">
            <span class="tarjeta-reporte-icono">
                <i class="bi bi-receipt"></i>
            </span>

            <div class="tarjeta-reporte-contenido">
                <span>Pagos registrados</span>

                <strong>
                    {{ $cantidad_pagos }}
                </strong>

                <small>
                    No incluye pagos anulados
                </small>
            </div>
        </article>

        <article class="tarjeta-reporte">
            <span class="tarjeta-reporte-icono">
                <i class="bi bi-calculator"></i>
            </span>

            <div class="tarjeta-reporte-contenido">
                <span>Promedio por pago</span>

                <strong>
                    USD {{
                        number_format(
                            $promedio_pago,
                            2
                        )
                    }}
                </strong>

                <small>{{ $periodo }}</small>
            </div>
        </article>

        <article class="tarjeta-reporte">
            <span class="tarjeta-reporte-icono">
                <i class="bi bi-exclamation-circle"></i>
            </span>

            <div class="tarjeta-reporte-contenido">
                <span>Saldo pendiente</span>

                <strong>
                    USD {{
                        number_format(
                            $saldo_pendiente,
                            2
                        )
                    }}
                </strong>

                <small>
                    {{ $reservas_con_saldo }}
                    reservas activas con saldo
                </small>
            </div>
        </article>
    </section>

    <section class="reporte-contenido-grid">
        <article class="panel-reporte">
            <header class="panel-reporte-encabezado">
                <h2>
                    {{
                        $mes
                            ? 'Ingresos por día'
                            : 'Ingresos por mes'
                    }}
                </h2>

                <span>{{ $periodo }}</span>
            </header>

            <div class="panel-grafico">
                <canvas
                    id="graficoIngresos"
                ></canvas>
            </div>
        </article>

        <article class="panel-reporte">
            <header class="panel-reporte-encabezado">
                <h2>Métodos de pago</h2>

                <span>{{ $periodo }}</span>
            </header>

            @if ($cantidad_pagos > 0)
                <div class="lista-metodos-pago">
                    @foreach ($metodos_pago as $metodo)
                        @php
                            $porcentaje =
                                $totalMetodos > 0
                                    ? (
                                        $metodo['total'] /
                                        $totalMetodos
                                    ) * 100
                                    : 0;
                        @endphp

                        <div class="metodo-pago">
                            <div class="metodo-pago-datos">
                                <strong>
                                    {{ $metodo['nombre'] }}
                                </strong>

                                <span>
                                    USD {{
                                        number_format(
                                            $metodo['total'],
                                            2
                                        )
                                    }}
                                </span>
                            </div>

                            <small>
                                {{ $metodo['cantidad'] }}
                                {{
                                    $metodo['cantidad'] === 1
                                        ? 'pago'
                                        : 'pagos'
                                }}
                            </small>

                            <div class="metodo-pago-barra">
                                <div
                                    class="metodo-pago-progreso"
                                    style="width: {{
                                        number_format(
                                            $porcentaje,
                                            2,
                                            '.',
                                            ''
                                        )
                                    }}%"
                                ></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="reporte-sin-datos">
                    No existen pagos registrados
                    para el periodo seleccionado.
                </div>
            @endif
        </article>
    </section>
</main>

<script>
    window.configuracionReporteFinanciero = {
        labels: @json($labels),
        datos: @json($data),
        tipo: @json($tipo),
        periodo: @json($periodo),
        errores: @json($errors->toArray()),
        mensajeError: @json(session('error'))
    };
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script
    src="{{ asset('js/reporte-financiero.js') }}"
></script>

@endsection