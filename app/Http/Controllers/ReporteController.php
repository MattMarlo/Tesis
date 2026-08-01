<?php

namespace App\Http\Controllers;

use App\Services\ReporteService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReporteController extends Controller
{
    public function __construct(
        private ReporteService $reporteService
    ) {
    }

    public function ingresosMensuales(
        Request $request
    ) {
        $datos = $request->validate(
            [
                'anio' => [
                    'nullable',
                    'integer',
                    'min:2000',
                    'max:2100',
                ],

                'mes' => [
                    'nullable',
                    'integer',
                    'between:1,12',
                ],
            ],
            [
                'anio.integer' =>
                    'El año seleccionado no es válido.',

                'anio.min' =>
                    'El año seleccionado no es válido.',

                'anio.max' =>
                    'El año seleccionado no es válido.',

                'mes.integer' =>
                    'El mes seleccionado no es válido.',

                'mes.between' =>
                    'El mes debe estar entre enero y diciembre.',
            ]
        );

        $anio = (int) (
            $datos['anio'] ??
            now()->year
        );

        $mes = isset($datos['mes'])
            ? (int) $datos['mes']
            : null;

        $reporte =
            $this->reporteService
                ->obtenerReporte(
                    $anio,
                    $mes
                );

        $nombreMes = $mes
            ? ucfirst(
                Carbon::create(
                    $anio,
                    $mes,
                    1
                )
                    ->locale('es')
                    ->translatedFormat('F')
            )
            : null;

        return view(
            'modules.reportes.ingresosMensuales',
            array_merge(
                $reporte,
                [
                    'titulo' =>
                        'Reporte financiero',

                    'years' =>
                        $this
                            ->reporteService
                            ->obtenerAniosDisponibles(),

                    'anio' =>
                        $anio,

                    'mes' =>
                        $mes,

                    'nombreMes' =>
                        $nombreMes,

                    'tipo' =>
                        $mes
                            ? 'diario'
                            : 'mensual',
                ]
            )
        );
    }
}