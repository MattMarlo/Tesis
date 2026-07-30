<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Destino;
use App\Models\Pago;
use App\Models\PreReserva;
use App\Models\Reserva;

class DashboardController extends Controller
{
    public function index()
    {
        $hoy = now()->toDateString();

        $reservasHoy = Reserva::whereDate(
            'fecha_reserva',
            $hoy
        )->count();

        $clientesActivos = Cliente::where(
            'estado',
            'activo'
        )->count();

        $ingresosMes = Pago::whereYear(
            'fecha_pago',
            now()->year
        )
            ->whereMonth('fecha_pago', now()->month)
            ->sum('monto_depositado');

        $prereservasPendientes = PreReserva::where(
            'estado',
            'pendiente_contacto'
        )->count();

        $reservasRecientes = Reserva::with([
            'cliente',
            'destino',
        ])
            ->orderByDesc('fecha_reserva')
            ->orderByDesc('id')
            ->take(5)
            ->get();

        $viajesProximos = Reserva::whereDate(
            'fecha_viaje',
            '>=',
            $hoy
        )
            ->where('estado', '!=', 'cancelada')
            ->count();

        $pagosPendientes = Reserva::where(
            'estado_pago',
            '!=',
            'pagado'
        )
            ->where('estado', '!=', 'cancelada')
            ->count();

        $paquetesPublicados = Destino::where(
            'estado_publicacion',
            'publicado'
        )->count();

        return view('dashboard.index', compact(
            'reservasHoy',
            'clientesActivos',
            'ingresosMes',
            'prereservasPendientes',
            'reservasRecientes',
            'viajesProximos',
            'pagosPendientes',
            'paquetesPublicados'
        ));
    }
}