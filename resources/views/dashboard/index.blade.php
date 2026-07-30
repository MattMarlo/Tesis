@extends('layouts.main')

@section('title', 'Inicio')

@section('header', 'Resumen general')

@section('content')

<link
    rel="stylesheet"
    href="{{ asset('css/dashboard.css') }}"
>

<section class="dashboard">

    <div class="bienvenida-dashboard">
        <div>
            <h2>
                Hola, {{ auth()->user()->nombres }}
            </h2>

            <p>
                Este es el resumen actualizado de Passion Travel.
            </p>
        </div>

        <span>
            {{ now()->locale('es')->translatedFormat(
                'd \d\e F \d\e Y'
            ) }}
        </span>
    </div>

    <div class="estadisticas-dashboard">

        <article class="estadistica-dashboard">
            <div>
                <span>Reservas registradas hoy</span>
                <strong>{{ $reservasHoy }}</strong>
            </div>

            <i class="bi bi-calendar-check"></i>
        </article>

        <article class="estadistica-dashboard">
            <div>
                <span>Clientes activos</span>
                <strong>{{ $clientesActivos }}</strong>
            </div>

            <i class="bi bi-people"></i>
        </article>

        <article class="estadistica-dashboard">
            <div>
                <span>Ingresos del mes</span>

                <strong>
                    ${{ number_format($ingresosMes, 2) }}
                </strong>
            </div>

            <i class="bi bi-currency-dollar"></i>
        </article>

        <article class="estadistica-dashboard">
            <div>
                <span>Prerreservas pendientes</span>
                <strong>{{ $prereservasPendientes }}</strong>
            </div>

            <i class="bi bi-file-earmark-text"></i>
        </article>

    </div>

    <div class="resumen-secundario">

        <a href="{{ route('reservas') }}">
            <i class="bi bi-airplane"></i>

            <div>
                <strong>{{ $viajesProximos }}</strong>
                <span>Viajes próximos</span>
            </div>
        </a>

        <a href="{{ route('pagos') }}">
            <i class="bi bi-wallet2"></i>

            <div>
                <strong>{{ $pagosPendientes }}</strong>
                <span>Reservas con pagos pendientes</span>
            </div>
        </a>

        <a href="{{ route('destinos') }}">
            <i class="bi bi-map"></i>

            <div>
                <strong>{{ $paquetesPublicados }}</strong>
                <span>Paquetes publicados</span>
            </div>
        </a>

    </div>

    <div class="panel-reservas-recientes">

        <div class="encabezado-panel-dashboard">
            <div>
                <h3>Reservas recientes</h3>
                <p>Últimas reservas registradas en el sistema.</p>
            </div>

            <a href="{{ route('reservas') }}">
                Ver todas
                <i class="bi bi-arrow-right"></i>
            </a>
        </div>

        @if($reservasRecientes->isNotEmpty())
            <div class="tabla-dashboard-contenedor">

                <table class="tabla-dashboard">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Cliente</th>
                            <th>Paquete</th>
                            <th>Fecha de viaje</th>
                            <th>Estado</th>
                            <th>Pago</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($reservasRecientes as $reserva)
                            <tr>
                                <td>
                                    <strong>
                                        {{ $reserva->codigo_reserva }}
                                    </strong>
                                </td>

                                <td>
                                    {{ $reserva->cliente
                                        ? $reserva->cliente->nombres .
                                            ' ' .
                                            $reserva->cliente->apellidos
                                        : 'Cliente no disponible' }}
                                </td>

                                <td>
                                    {{ $reserva->destino
                                        ? (
                                            $reserva->destino
                                                ->nombre_paquete
                                            ?: $reserva->destino->pais
                                        )
                                        : 'Paquete no disponible' }}
                                </td>

                                <td>
                                    {{ $reserva->fecha_viaje
                                        ? \Carbon\Carbon::parse(
                                            $reserva->fecha_viaje
                                        )->format('d/m/Y')
                                        : 'Sin fecha' }}
                                </td>

                                <td>
                                    <span class="estado-reserva estado-{{
                                        $reserva->estado
                                    }}">
                                        {{ ucfirst($reserva->estado) }}
                                    </span>
                                </td>

                                <td>
                                    <span class="estado-pago estado-pago-{{
                                        $reserva->estado_pago
                                    }}">
                                        {{ ucfirst(
                                            $reserva->estado_pago
                                        ) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

            </div>
        @else
            <div class="dashboard-vacio">
                <i class="bi bi-calendar-x"></i>
                <p>No existen reservas registradas.</p>

                <a href="{{ route('reservas_individual.create') }}">
                    Registrar reserva
                </a>
            </div>
        @endif

    </div>

</section>

@endsection