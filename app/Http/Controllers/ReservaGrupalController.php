<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ReservaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ReservaGrupalController extends Controller
{
    protected $reservaService;

    public function __construct(ReservaService $reservaService)
    {
        $this->reservaService = $reservaService;
    }

    public function create()
    {
        $clientes = DB::table('clientes')->get();
        $destinos = DB::table('destinos')->get();
        $reservas = DB::table('reservas')->get();
        $grupos   = DB::table('grupos')->get();
        return view('modules.reservas.grupal.create', compact('clientes', 'destinos', 'reservas', 'grupos'));
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'grupo_id'                       => 'nullable|exists:grupos,id',
            'nombre_grupo'                   => 'nullable|string|max:255',
            'destino_id'                     => 'required|exists:destinos,id',
            'fecha_reserva'                  => 'required|date',
            'fecha_viaje'                    => 'required|date',
            'precio_total_viaje'             => 'required|numeric|min:0',
            'integrantes'                    => 'required|array|min:1',
            'integrantes.*.cliente_id'       => 'required|exists:clientes,id',
            'integrantes.*.monto_asignado'   => 'required|numeric|min:0',
            'integrantes.*.es_lider'         => 'nullable|boolean'
        ]);

        if (empty($datos['grupo_id']) && empty($datos['nombre_grupo'])) {
            $msg = 'Debe seleccionar un grupo existente o ingresar el nombre para uno nuevo.';
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
        // Validate total amount matches the sum of assignments
        $sumaAsignada = 0;
        foreach ($datos['integrantes'] as $integrante) {
            $sumaAsignada += $integrante['monto_asignado'];
        }

        if (abs($sumaAsignada - $datos['precio_total_viaje']) > 0.01) {
            $msg = 'La suma de los montos asignados debe ser igual al monto total del grupo.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $msg], 422);
            }
            return back()->with('error', $msg)->withInput();
        }

        try {
            $codigo = $this->reservaService->guardarGrupal($datos);
            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'redirect' => route('reservas'), 'codigo' => $codigo]);
            }
            return to_route('reservas')->with('success', 'Reserva grupal creada. Código: ' . $codigo);
        } catch (\Exception $e) {
            $msg = 'Error al crear reserva grupal: ' . $e->getMessage();
            if ($request->expectsJson()) {
                return response()->json(['message' => $msg], 500);
            }
            return back()->with('error', $msg)->withInput();
        }
    }
}
