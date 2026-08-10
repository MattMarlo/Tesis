@extends('layouts.main')

@section('titulo', 'Reservas en riesgo')

@section('content')
<link rel="stylesheet" href="{{ asset('css/reservas-riesgo.css') }}">

<main class="pagina-riesgos">
    <header class="riesgos-encabezado">
        <div>
            <span>CONTROL DE COBRANZA</span>
            <h1>Reservas en riesgo</h1>
            <p>
                Saldos vencidos, períodos de gracia y cancelaciones que
                requieren una liquidación antes de afectar dinero del cliente.
            </p>
        </div>

        <a href="{{ route('pagos') }}">
            <i class="bi bi-wallet2"></i> Gestionar pagos
        </a>
    </header>

    <section class="resumen-riesgos">
        <article>
            <span>En gracia</span>
            <strong>{{ $resumen['activas'] }}</strong>
        </article>
        <article>
            <span>Revisión de cancelación</span>
            <strong>{{ $resumen['revision'] }}</strong>
        </article>
        <article>
            <span>Saldo expuesto</span>
            <strong>USD {{ number_format($resumen['saldo'], 2, '.', ',') }}</strong>
        </article>
    </section>

    <form method="GET" class="filtros-riesgos">
        <label for="estadoRiesgo">Mostrar</label>
        <select id="estadoRiesgo" name="estado" onchange="this.form.submit()">
            <option value="pendientes" @selected($estado === 'pendientes')>Pendientes</option>
            <option value="activa" @selected($estado === 'activa')>En gracia</option>
            <option value="revision_cancelacion" @selected($estado === 'revision_cancelacion')>Revisión de cancelación</option>
            <option value="regularizada" @selected($estado === 'regularizada')>Regularizadas</option>
            <option value="cancelada" @selected($estado === 'cancelada')>Canceladas</option>
            <option value="todas" @selected($estado === 'todas')>Todas</option>
        </select>
    </form>

    <section class="contenedor-riesgos">
        <div class="tabla-riesgos-responsive">
            <table class="tabla-riesgos">
                <thead>
                    <tr>
                        <th>Reserva</th>
                        <th>Cliente / paquete</th>
                        <th>Incumplimiento</th>
                        <th>Valores</th>
                        <th>Plazo</th>
                        <th>Estado</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($riesgos as $riesgo)
                        @php
                            $reserva = $riesgo->reserva;
                            $nombre = $reserva?->esGrupal()
                                ? $reserva?->grupo?->nombre_grupo
                                : $reserva?->cliente?->nombre_completo;
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $reserva?->codigo_reserva }}</strong>
                                <small>{{ ucfirst($reserva?->tipo ?? '') }}</small>
                            </td>
                            <td>
                                <strong>{{ $nombre ?: 'Sin información' }}</strong>
                                <small>{{ $reserva?->destino?->nombre_paquete }}</small>
                            </td>
                            <td>
                                {{ $riesgo->tipo === 'anticipo_vencido'
                                    ? 'Anticipo vencido'
                                    : 'Saldo final vencido' }}
                            </td>
                            <td>
                                <strong>
                                    {{ $reserva?->moneda ?: 'USD' }}
                                    {{ number_format($reserva?->saldo_pendiente ?? 0, 2, '.', ',') }}
                                </strong>
                                <small>
                                    Neto pagado:
                                    {{ number_format($reserva?->total_pagado ?? 0, 2, '.', ',') }}
                                </small>
                            </td>
                            <td>
                                <strong>{{ $riesgo->fecha_limite_regularizacion?->format('d/m/Y H:i') }}</strong>
                                <small>
                                    {{ now()->gt($riesgo->fecha_limite_regularizacion)
                                        ? 'Plazo vencido'
                                        : 'Dentro de la gracia' }}
                                </small>
                            </td>
                            <td>
                                <span class="estado-riesgo estado-{{ $riesgo->estado }}">
                                    {{ ucfirst(str_replace('_', ' ', $riesgo->estado)) }}
                                </span>
                            </td>
                            <td>
                                @if ($reserva && !$reserva->estaCancelada())
                                    <div class="acciones-riesgo">
                                        <a href="{{ route('pagos', [
                                            'reserva_id' => $reserva->id,
                                            'abrir_cobro' => 1,
                                        ]) }}">
                                            Cobrar
                                        </a>

                                        @if (
                                            $riesgo->estado ===
                                            \App\Models\ReservaRiesgo::ESTADO_REVISION_CANCELACION
                                        )
                                            <a
                                                class="accion-liquidar"
                                                href="{{ route('reservas', [
                                                    'buscar' =>
                                                        $reserva->codigo_reserva,
                                                ]) }}"
                                            >
                                                Liquidar
                                            </a>
                                        @endif
                                    </div>
                                @else
                                    <a href="{{ route('devoluciones.index') }}">
                                        Ver devolución
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="sin-riesgos">
                                No existen reservas en este estado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($riesgos->hasPages())
        <div class="paginacion-riesgos">{{ $riesgos->links() }}</div>
    @endif
</main>
@endsection
