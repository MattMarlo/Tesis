<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ReservaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
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
            return back()->with('error', 'Debe seleccionar un grupo existente o ingresar el nombre para uno nuevo.')->withInput();
        }

        $usuario_id = Auth::id();
        if (!$usuario_id) {
            return back()->with('error', 'Debes estar autenticado para crear una reserva.')->withInput();
        }

        $datos['user_id'] = $usuario_id;
        // Validación: la fecha de viaje no debe ser anterior a la fecha de reserva
        if (isset($datos['fecha_viaje']) && isset($datos['fecha_reserva'])) {
            $fechaViaje = Carbon::parse($datos['fecha_viaje']);
            $fechaReserva = Carbon::parse($datos['fecha_reserva']);
            if ($fechaViaje->lt($fechaReserva)) {
                return back()->with('error', 'La fecha de viaje no debe ser antes de la fecha de reserva')->withInput();
            }
        }
        //Validación de que la fecha de viaje no sea mayor a un año
        $fechaLimite=Carbon::now()->addYear(1);
        if($fechaViaje->gt($fechaReserva)){
            return back()->with('error','La fecha de viaje no debe exceder el año')->withInput();
        }
        // Validate total amount matches the sum of assignments
        $sumaAsignada = 0;
        foreach ($datos['integrantes'] as $integrante) {
            $sumaAsignada += $integrante['monto_asignado'];
        }

        if (abs($sumaAsignada - $datos['precio_total_viaje']) > 0.01) {
            return back()->with('error', 'La suma de los montos asignados debe ser igual al monto total del grupo.')->withInput();
        }

        try {
            $codigo = $this->reservaService->guardarGrupal($datos);
            return to_route('reservas')->with('success', 'Reserva grupal creada. Código: ' . $codigo);
        } catch (\Exception $e) {
            return back()->with('error', 'Error al crear reserva grupal: ' . $e->getMessage())->withInput();
        }
    }
}
