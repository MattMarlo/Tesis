@extends('layouts.main')


@section('content')
<div class="container-fluid mt-4 px-4">

    <!-- Encabezado con selectores de año y mes -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="fw-bold">
                <i class="bi bi-bar-chart-line-fill text-primary"></i>
                Ingresos Mensuales
            </h1>
            
            @if($mes)
                <p class="text-muted small">Ingresos diarios de {{ \Carbon\Carbon::create($anio, $mes, 1)->locale('es')->translatedFormat('F Y') }}</p>
                
            @else
                <p class="text-muted small">Análisis de ingresos por mes</p>
            @endif
        </div>
        <div class="d-flex gap-2 align-items-center">
            <form method="GET" action="{{ route('reportes.ingresos') }}" class="d-flex gap-2">
                <!-- Selector de año -->
                <select name="anio" class="form-select" onchange="this.form.submit()">
                    @foreach($years as $year)
                        <option value="{{ $year }}" {{ $year == $anio ? 'selected' : '' }}>
                            {{ $year }}
                        </option>
                    @endforeach
                </select>

                <!-- Selector de mes (opcional) -->
                <select name="mes" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Todos los meses --</option>
                    @foreach(range(1, 12) as $m)
                        <option value="{{ $m }}" {{ $mes == $m ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create($anio, $m, 1)->locale('es')->translatedFormat('F') }}
                        </option>
                    @endforeach
                </select>

                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-search"></i> Filtrar
                </button>
            </form>
            <a href="{{ route('reportes.ingresos') }}" class="btn btn-outline-secondary" title="Restablecer">
                <i class="bi bi-arrow-repeat"></i>
            </a>
        </div>
    </div>

    <!-- Tarjetas resumen -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0 text-center h-100">
                <div class="card-body">
                    <i class="bi bi-cash-stack text-success display-5"></i>
                    <h3 class="mt-3">${{ number_format($totalIngresos, 2) }}</h3>
                    @if($mes)
                        <p class="text-muted mb-0">Total de Ingresos en el año</p>
                    @else
                        <p class="text-muted mb-0">Ingresos del año {{ $anio }}</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 text-center h-100">
                <div class="card-body">
                    <i class="bi bi-credit-card text-primary display-5"></i>
                    <h3 class="mt-3">{{ $totalPagos }}</h3>
                    @if($mes)
                        <p class="text-muted mb-0">Pagos en el mes</p>
                    @else
                        <p class="text-muted mb-0">Pagos en {{ $anio }}</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 text-center h-100">
                <div class="card-body">
                    <i class="bi bi-calendar-week text-warning display-5"></i>
                    <h3 class="mt-3">${{ number_format($ingresosMesActual ?? 0, 2) }}</h3>
                    <p class="text-muted mb-0">Ingresos del Mes de {{$nombreMes}}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 text-center h-100">
                <div class="card-body">
                    <i class="bi bi-graph-up-arrow text-info display-5"></i>
                    <h3 class="mt-3">${{ number_format($promedioDiario, 2) }}</h3>
                    @if($mes)
                        <p class="text-muted mb-0">Promedio Diario</p>
                    @else
                        <p class="text-muted mb-0">Promedio Diario (aprox.)</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Gráfico -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white">
            @if($mes)
                <h5 class="fw-bold mb-0"><i class="bi bi-graph-up me-2"></i>Evolución Diaria</h5>
            @else
                <h5 class="fw-bold mb-0"><i class="bi bi-graph-up me-2"></i>Evolución Mensual</h5>
            @endif
        </div>
        <div class="card-body">
            <canvas id="ingresosChart" style="width:100%; max-height:400px;"></canvas>
        </div>
    </div>

</div>

<!-- Incluir Chart.js desde CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('ingresosChart').getContext('2d');

        const labels = @json($labels);
        const data = @json($data);
        const isMonthly = @json($mes ? false : true); // true = mensual, false = diario

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: isMonthly ? 'Ingresos por mes ($)' : 'Ingresos por día ($)',
                    data: data,
                    backgroundColor: isMonthly
                        ? 'rgba(54, 162, 235, 0.6)'
                        : 'rgba(255, 159, 64, 0.6)',
                    borderColor: isMonthly
                        ? 'rgba(54, 162, 235, 1)'
                        : 'rgba(255, 159, 64, 1)',
                    borderWidth: 2,
                    borderRadius: 6,
                    tension: 0.2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: true },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return '$' + context.parsed.y.toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value;
                            }
                        }
                    }
                }
            }
        });
    });
</script>
@endsection