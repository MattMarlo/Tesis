<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReporteService
{
    
    public function obtenerAñosDisponibles()
    {
        return DB::table('pagos')
            ->select(DB::raw('DISTINCT YEAR(fecha_pago) as year'))
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();
    }

    
    public function obtenerIngresosMensuales($anio)
    {
        // Validar año
        $anio = (int) $anio;
        if ($anio < 2000 || $anio > 2100) {
            $anio = now()->year;
        }

        // Obtener pagos agrupados por mes
        $ingresosMensuales = DB::table('pagos')
            ->select(
                DB::raw('MONTH(fecha_pago) as mes'),
                DB::raw('SUM(monto_depositado) as total')
            )
            ->whereYear('fecha_pago', $anio)
            ->groupBy('mes')
            ->orderBy('mes', 'asc')
            ->get();

        // Preparar datos para todos los 12 meses
        $labels = [];
        $data = [];
        $totalIngresosPorMes = [];

        for ($mes = 1; $mes <= 12; $mes++) {
            $labels[] = date('M', mktime(0, 0, 0, $mes, 1));
            $registro = $ingresosMensuales->firstWhere('mes', $mes);
            $monto = $registro ? (float) $registro->total : 0.0;
            $data[] = round($monto, 2);
            $totalIngresosPorMes[$mes] = $monto;
        }

        // Calcular métricas exactas
        $totalIngresos = array_sum($data);
        $totalPagos = DB::table('pagos')
            ->whereYear('fecha_pago', $anio)
            ->count();

        // Obtener ingresos del mes actual (solo si es el año actual)
        $ingresosMesActual = 0.0;
        if ($anio == now()->year) {
            $ingresosMesActual = round(
                (float) DB::table('pagos')
                    ->whereMonth('fecha_pago', now()->month)
                    ->whereYear('fecha_pago', $anio)
                    ->sum('monto_depositado'),
                2
            );
        }

        // Promedio diario exacto del año
        $diasDelAnio = date('z', mktime(0, 0, 0, 12, 31, $anio)) + 1;
        $promedioDiario = $diasDelAnio > 0 ? round($totalIngresos / $diasDelAnio, 2) : 0.0;

        // Ingreso máximo y mínimo
        $ingresoMaximo = !empty($data) ? max($data) : 0.0;
        $datosConIngresos = array_filter($data);
        $ingresoMinimo = !empty($datosConIngresos) ? min($datosConIngresos) : 0.0;

        // Ingresos acumulados hasta la fecha actual (hasta hoy)
        $ingresosHastaHoy = (float) DB::table('pagos')
            ->whereDate('fecha_pago', '<=', Carbon::now()->toDateString())
            ->sum('monto_depositado');

        return [
            'labels'                => $labels,
            'data'                  => $data,
            'total_ingresos'        => round($totalIngresos, 2),
            'ingresos_hasta_hoy'    => round($ingresosHastaHoy, 2),
            'total_pagos'           => $totalPagos,
            'ingresos_mes_actual'   => $ingresosMesActual,
            'promedio_diario'       => $promedioDiario,
            'ingreso_maximo'        => $ingresoMaximo,
            'ingreso_minimo'        => $ingresoMinimo,
            'mes_con_mayor_ingreso' => $this->obtenerMesConMayorIngreso($totalIngresosPorMes)
        ];
    }

    
    public function obtenerIngresosDiarios($anio, $mes)
    {
        // Validar año y mes
        $anio = (int) $anio;
        $mes = (int) $mes;

        if ($anio < 2000 || $anio > 2100 || $mes < 1 || $mes > 12) {
            return null;
        }

        // Obtener pagos agrupados por día
        $ingresosDiarios = DB::table('pagos')
            ->select(
                DB::raw('DAY(fecha_pago) as dia'),
                DB::raw('SUM(monto_depositado) as total')
            )
            ->whereYear('fecha_pago', $anio)
            ->whereMonth('fecha_pago', $mes)
            ->groupBy('dia')
            ->orderBy('dia', 'asc')
            ->get();

        // Preparar datos para todos los días del mes
        $diasDelMes = cal_days_in_month(CAL_GREGORIAN, $mes, $anio);
        $labels = [];
        $data = [];
        $totalIngresosPorDia = [];

        for ($dia = 1; $dia <= $diasDelMes; $dia++) {
            $labels[] = $dia;
            $registro = $ingresosDiarios->firstWhere('dia', $dia);
            $monto = $registro ? (float) $registro->total : 0.0;
            $data[] = round($monto, 2);
            $totalIngresosPorDia[$dia] = $monto;
        }

        // Calcular métricas exactas
        $totalIngresos = array_sum($data);
        $totalPagos = DB::table('pagos')
            ->whereYear('fecha_pago', $anio)
            ->whereMonth('fecha_pago', $mes)
            ->count();

        // Promedio diario exacto del mes
        $promedioDiario = $diasDelMes > 0 ? round($totalIngresos / $diasDelMes, 2) : 0.0;

        // Ingreso máximo y mínimo
        $ingresoMaximo = !empty($data) ? max($data) : 0.0;
        $datosConIngresos = array_filter($data);
        $ingresoMinimo = !empty($datosConIngresos) ? min($datosConIngresos) : 0.0;

        // Calcular días sin ingresos
        $diasSinIngresos = count(array_filter($data, fn($v) => $v == 0));

        return [
            'labels'                 => $labels,
            'data'                   => $data,
            'total_ingresos'         => round($totalIngresos, 2),
            // Ingresos acumulados hasta la fecha actual (hasta hoy)
            'ingresos_hasta_hoy'     => round((float) DB::table('pagos')
                                            ->whereDate('fecha_pago', '<=', Carbon::now()->toDateString())
                                            ->sum('monto_depositado'), 2),
            'total_pagos'            => $totalPagos,
            'promedio_diario'        => $promedioDiario,
            'ingreso_maximo'         => $ingresoMaximo,
            'ingreso_minimo'         => $ingresoMinimo,
            'dia_con_mayor_ingreso'  => $this->obtenerDiaConMayorIngreso($totalIngresosPorDia),
            'dias_sin_ingresos'      => $diasSinIngresos,
            'total_dias'             => $diasDelMes
        ];
    }

    /**
     * Obtiene el nombre del mes con mayor ingreso
     */
    private function obtenerMesConMayorIngreso(array $ingresosPorMes)
    {
        if (empty($ingresosPorMes)) {
            return null;
        }

        $mesConMayor = array_key_first($ingresosPorMes);
        foreach ($ingresosPorMes as $mes => $ingreso) {
            if ($ingreso > ($ingresosPorMes[$mesConMayor] ?? 0)) {
                $mesConMayor = $mes;
            }
        }

        return [
            'mes' => (int) $mesConMayor,
            'nombre' => date('F', mktime(0, 0, 0, $mesConMayor, 1)),
            'ingreso' => (float) $ingresosPorMes[$mesConMayor]
        ];
    }

    
    private function obtenerDiaConMayorIngreso(array $ingresosPorDia)
    {
        if (empty($ingresosPorDia)) {
            return null;
        }

        $diaConMayor = array_key_first($ingresosPorDia);
        foreach ($ingresosPorDia as $dia => $ingreso) {
            if ($ingreso > ($ingresosPorDia[$diaConMayor] ?? 0)) {
                $diaConMayor = $dia;
            }
        }

        return [
            'dia' => (int) $diaConMayor,
            'ingreso' => (float) $ingresosPorDia[$diaConMayor]
        ];
    }

    
    public function obtenerMetricasPagos()
    {
        $totalPagosInfo = DB::table('pagos')
            ->select(
                DB::raw('SUM(monto_depositado) as total_monto'),
                DB::raw('COUNT(id) as total_trx')
            )
            ->first();

        $totalEsperado = DB::table('reservas')
            ->sum('precio_total_viaje');

        $totalPagos = $totalPagosInfo?->total_monto ?? 0.0;
        $totalTrx = $totalPagosInfo?->total_trx ?? 0;
        $totalEsperado = (float) $totalEsperado;

        $cobrado = (float) $totalPagos;
        $tasaCobro = $totalEsperado > 0 ? round(($cobrado / $totalEsperado) * 100, 2) : 0.0;

        $pendiente = $totalEsperado - $cobrado;
        if ($pendiente < 0) $pendiente = 0.0;

        $reservasConDeuda = DB::table('reservas')
            ->whereRaw('precio_total_viaje > (SELECT COALESCE(SUM(monto_depositado), 0) FROM pagos WHERE pagos.reserva_id = reservas.id)')
            ->count();

        return [
            'total_pagos'      => round($cobrado, 2),
            'total_trx'        => $totalTrx,
            'total_esperado'   => round($totalEsperado, 2),
            'cobrado'          => round($cobrado, 2),
            'tasa_cobro'       => $tasaCobro,
            'pendiente'        => round($pendiente, 2),
            'reservas_deuda'   => $reservasConDeuda
        ];
    }
}
