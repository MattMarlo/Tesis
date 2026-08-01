<?php

namespace App\Services;

use App\Models\Pago;
use App\Models\Reserva;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReporteService
{
    public function obtenerAniosDisponibles(): array
    {
        $anios = Pago::registrados()
            ->whereNotNull('fecha_pago')
            ->get(['fecha_pago'])
            ->map(
                fn (Pago $pago) =>
                    $pago->fecha_pago->year
            )
            ->push(now()->year)
            ->unique()
            ->sortDesc()
            ->values();

        return $anios->all();
    }

    public function obtenerReporte(
        int $anio,
        ?int $mes = null
    ): array {
        $inicio = $mes
            ? Carbon::create(
                $anio,
                $mes,
                1
            )->startOfMonth()
            : Carbon::create(
                $anio,
                1,
                1
            )->startOfYear();

        $fin = $mes
            ? $inicio->copy()->endOfMonth()
            : $inicio->copy()->endOfYear();

        $pagos = Pago::registrados()
            ->whereBetween(
                'fecha_pago',
                [
                    $inicio,
                    $fin,
                ]
            )
            ->orderBy('fecha_pago')
            ->get();

        $grafico = $mes
            ? $this->agruparPorDia(
                $pagos,
                $inicio
            )
            : $this->agruparPorMes(
                $pagos,
                $anio
            );

        $totalCobrado = (float)
            $pagos->sum(
                'monto_depositado'
            );

        $cantidadPagos =
            $pagos->count();

        $promedioPago =
            $cantidadPagos > 0
                ? $totalCobrado /
                    $cantidadPagos
                : 0;

        $saldos = $this
            ->obtenerSaldosPendientes();

        return [
            'labels' =>
                $grafico['labels'],

            'data' =>
                $grafico['data'],

            'total_cobrado' =>
                round($totalCobrado, 2),

            'cantidad_pagos' =>
                $cantidadPagos,

            'promedio_pago' =>
                round($promedioPago, 2),

            'saldo_pendiente' =>
                $saldos['saldo_pendiente'],

            'reservas_con_saldo' =>
                $saldos['reservas_con_saldo'],

            'metodos_pago' =>
                $this->agruparPorMetodo(
                    $pagos
                ),
        ];
    }

    private function agruparPorMes(
        Collection $pagos,
        int $anio
    ): array {
        $labels = [];
        $data = [];

        for ($mes = 1; $mes <= 12; $mes++) {
            $fecha = Carbon::create(
                $anio,
                $mes,
                1
            )->locale('es');

            $labels[] = ucfirst(
                $fecha->translatedFormat('M')
            );

            $total = $pagos
                ->filter(
                    fn (Pago $pago) =>
                        $pago->fecha_pago
                            ->month === $mes
                )
                ->sum('monto_depositado');

            $data[] = round(
                (float) $total,
                2
            );
        }

        return compact(
            'labels',
            'data'
        );
    }

    private function agruparPorDia(
        Collection $pagos,
        Carbon $inicio
    ): array {
        $labels = [];
        $data = [];

        $dias = $inicio->daysInMonth;

        for ($dia = 1; $dia <= $dias; $dia++) {
            $labels[] = (string) $dia;

            $total = $pagos
                ->filter(
                    fn (Pago $pago) =>
                        $pago->fecha_pago
                            ->day === $dia
                )
                ->sum('monto_depositado');

            $data[] = round(
                (float) $total,
                2
            );
        }

        return compact(
            'labels',
            'data'
        );
    }

    private function agruparPorMetodo(
        Collection $pagos
    ): array {
        $nombres = [
            Pago::METODO_EFECTIVO =>
                'Efectivo',

            Pago::METODO_TRANSFERENCIA =>
                'Transferencia',

            Pago::METODO_TARJETA =>
                'Tarjeta',

            Pago::METODO_OTRO =>
                'Otro',
        ];

        return collect($nombres)
            ->map(
                function (
                    string $nombre,
                    string $metodo
                ) use ($pagos) {
                    return [
                        'metodo' =>
                            $metodo,

                        'nombre' =>
                            $nombre,

                        'cantidad' =>
                            $pagos
                                ->where(
                                    'metodo_pago',
                                    $metodo
                                )
                                ->count(),

                        'total' =>
                            round(
                                (float) $pagos
                                    ->where(
                                        'metodo_pago',
                                        $metodo
                                    )
                                    ->sum(
                                        'monto_depositado'
                                    ),
                                2
                            ),
                    ];
                }
            )
            ->values()
            ->all();
    }

    private function obtenerSaldosPendientes(): array
    {
        $reservas = Reserva::query()
            ->where(
                'estado',
                '!=',
                Reserva::ESTADO_CANCELADA
            )
            ->withSum(
                'pagos as pagado_reporte',
                'monto_depositado'
            )
            ->get([
                'id',
                'precio_total_viaje',
            ]);

        $saldos = $reservas->map(
            function (Reserva $reserva) {
                return max(
                    0,
                    (float)
                        $reserva
                            ->precio_total_viaje -
                    (float)
                        ($reserva
                            ->pagado_reporte ?? 0)
                );
            }
        );

        return [
            'saldo_pendiente' =>
                round(
                    (float) $saldos->sum(),
                    2
                ),

            'reservas_con_saldo' =>
                $saldos
                    ->filter(
                        fn (float $saldo) =>
                            $saldo > 0
                    )
                    ->count(),
        ];
    }
}