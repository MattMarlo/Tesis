<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ReporteService;

class ReporteController extends Controller
{
    protected $reporteService;

    public function __construct(ReporteService $reporteService)
    {
        $this->reporteService = $reporteService;
    }

   
    public function ingresosMensuales(Request $request)
    {
        // Obtener parámetros
        $anio = $request->input('anio', now()->year);
        $mes = $request->input('mes', null); // null = vista mensual, si es número = vista diaria

        // Obtener años disponibles
        $years = $this->reporteService->obtenerAñosDisponibles();

        if ($mes) {
            //  Vista DIARIA: ingresos por día de ese mes
            $reporte = $this->reporteService->obtenerIngresosDiarios($anio, $mes);

            if (!$reporte) {
                return back()->with('error', 'Parámetros inválidos');
            }

            return view('modules.reportes.ingresosMensuales', [
                'labels'            => $reporte['labels'],
                'data'              => $reporte['data'],
                'totalIngresos'     => $reporte['total_ingresos'],
                'totalPagos'        => $reporte['total_pagos'],
                'promedioDiario'    => $reporte['promedio_diario'],
                'ingresosMesActual' => $reporte['total_ingresos'], // Para compatibilidad con vista
                'years'             => $years,
                'anio'              => $anio,
                'mes'               => $mes,
                'tipo'              => 'diaria'
            ]);
        }

        //  Vista MENSUAL (por mes del año)
        $reporte = $this->reporteService->obtenerIngresosMensuales($anio);

        return view('modules.reportes.ingresosMensuales', [
            'labels'            => $reporte['labels'],
            'data'              => $reporte['data'],
            'totalIngresos'     => $reporte['total_ingresos'],
            'totalPagos'        => $reporte['total_pagos'],
            'promedioDiario'    => $reporte['promedio_diario'],
            'ingresosMesActual' => $reporte['ingresos_mes_actual'],
            'years'             => $years,
            'anio'              => $anio,
            'mes'               => null,
            'tipo'              => 'mensual'
        ]);
    }

    /**
     * Muestra métricas generales de pagos vs esperado
     */
    public function metricasPagos()
    {
        $metricas = $this->reporteService->obtenerMetricasPagos();

        return view('modules.reportes.metricasPagos', compact('metricas'));
    }

}
