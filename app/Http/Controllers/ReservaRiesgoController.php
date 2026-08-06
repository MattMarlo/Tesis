<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use App\Models\ReservaRiesgo;
use Illuminate\Http\Request;

class ReservaRiesgoController extends Controller
{
    public function index(Request $request)
    {
        $estado = $request->input('estado', 'pendientes');

        $consulta = ReservaRiesgo::query()
            ->with([
                'reserva.cliente',
                'reserva.destino',
                'reserva.grupo',
                'reserva.pagos',
                'reserva.devoluciones',
            ])
            ->latest('fecha_ingreso');

        if ($estado === 'pendientes') {
            $consulta->whereIn('estado', [
                ReservaRiesgo::ESTADO_ACTIVA,
                ReservaRiesgo::ESTADO_REVISION_CANCELACION,
            ]);
        } elseif (in_array($estado, [
            ReservaRiesgo::ESTADO_ACTIVA,
            ReservaRiesgo::ESTADO_REVISION_CANCELACION,
            ReservaRiesgo::ESTADO_REGULARIZADA,
            ReservaRiesgo::ESTADO_CANCELADA,
        ], true)) {
            $consulta->where('estado', $estado);
        }

        $riesgos = $consulta
            ->paginate(20)
            ->withQueryString();

        $resumen = [
            'activas' => ReservaRiesgo::where(
                'estado',
                ReservaRiesgo::ESTADO_ACTIVA
            )->count(),
            'revision' => ReservaRiesgo::where(
                'estado',
                ReservaRiesgo::ESTADO_REVISION_CANCELACION
            )->count(),
            'saldo' => (float) Reserva::query()
                ->whereIn('estado_cobranza', [
                    Reserva::COBRANZA_EN_RIESGO,
                    Reserva::COBRANZA_REVISION_CANCELACION,
                ])
                ->get()
                ->sum(fn (Reserva $reserva) => $reserva->saldo_pendiente),
        ];

        return view('modules.reservas.riesgo', compact(
            'riesgos',
            'resumen',
            'estado'
        ));
    }
}
