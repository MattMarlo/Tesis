<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ReservaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ReservaIndividualController extends Controller
{
    protected $reservaService;
    // Inyectamos el servicio en el constructor
    public function __construct(ReservaService $reservaService)
    {
        $this->reservaService = $reservaService;
    }
    public function create()
    {
        $clientes = DB::table('clientes')->get();
        $destinos = DB::table('destinos')->get();
        $reservas = DB::table('reservas')->get();
        return view('modules.reservas.individual.create', compact('clientes', 'destinos','reservas'));
    }
    
    public function store(Request $request)
    {
        $datos = $request->validate([
            'cliente_id'         => 'required|exists:clientes,id',
            'destino_id'         => 'required|exists:destinos,id',
            //'estado'             => 'nullable|in:confirmada,pendiente,cancelada',
            'fecha_reserva'      => 'required|date',
            'fecha_viaje'        => 'required|date',
            'precio_total_viaje' => 'required|numeric|min:0',
            'monto_depositado'   => 'nullable|numeric|min:0',
            'metodo_pago'        => 'nullable|string',
            'fecha_pago'         => 'nullable|date',
        ]);

        // Validación: la fecha de viaje no debe ser anterior a la fecha de reserva
        $fechaViaje = isset($datos['fecha_viaje']) ? Carbon::parse($datos['fecha_viaje']) : null;
        $fechaReserva = isset($datos['fecha_reserva']) ? Carbon::parse($datos['fecha_reserva']) : Carbon::now();

        if ($fechaViaje && $fechaReserva && $fechaViaje->lt($fechaReserva)) {
            $msg = 'La fecha de viaje no debe ser antes de la fecha de reserva';
            if ($request->expectsJson()) {
                return response()->json(['message' => $msg], 422);
            }
            return back()->with('error', $msg)->withInput();
        }

        // Validación de que la fecha de viaje no sea mayor a un año desde ahora
        $fechaLimite = Carbon::now()->addYear(1);
        if ($fechaViaje && $fechaViaje->gt($fechaLimite)) {
            $msg = 'La fecha de viaje no debe exceder el año';
            if ($request->expectsJson()) {
                return response()->json(['message' => $msg], 422);
            }
            return back()->with('error', $msg)->withInput();
        }

        // Validar que el precio de viaje sea mayor o igual al monto a cobrar
        if (isset($datos['monto_depositado']) && $datos['monto_depositado'] > $datos['precio_total_viaje']) {
            $msg = 'El monto depositado no debe ser mayor al precio total del viaje';
            if ($request->expectsJson()) {
                return response()->json(['message' => $msg], 422);
            }
            return back()->with('error', $msg)->withInput();
        }
        $usuario_id = Auth::id();
        if (!$usuario_id) {
            $msg = 'Debes estar autenticado para crear una reserva.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $msg], 401);
            }
            return back()->with('error', $msg)->withInput();
        }

        $datos['user_id'] = $usuario_id;
        //$datos['estado'] = $datos['estado'] ?? 'pendiente';

        try {
            $codigo = $this->reservaService->guardarIndividual($datos);
            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'redirect' => route('reservas'), 'codigo' => $codigo]);
            }
            return to_route('reservas')->with('success', 'Reserva creada. Código: ' . $codigo);
        } catch (\Exception $e) {
            $msg = 'Error al crear reserva: ' . $e->getMessage();
            if ($request->expectsJson()) {
                return response()->json(['message' => $msg], 500);
            }
            return back()->with('error', $msg)->withInput();
        }
    }
}
