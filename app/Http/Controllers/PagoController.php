<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PagoService;
use App\Models\Reserva;
use App\Models\Pago;
use Illuminate\Support\Facades\Auth;

class PagoController extends Controller
{
    public function __construct(protected PagoService $pagoService)
    {
    }

    public function index(Request $request)
    {
        $filtros = [
            'estado' => $request->input('estado', 'todos'),
            'metodo' => $request->input('metodo', 'todos'),
        ];

        $metricas = $this->pagoService->getMetricasGenerales();
        $reservasLista = $this->pagoService->getListaReservas($filtros);
        if ($request->filled('reserva_id')) {
            $rid = (int) $request->input('reserva_id');
            $reservasLista = $reservasLista->filter(fn ($row) => (int) $row['reserva_id'] === $rid)->values();
        }

        $reservaFiltroId = $request->input('reserva_id');
        $abrirCobro = $request->boolean('abrir_cobro');

        return view('modules.pagos.index', [
            'metricas'        => $metricas,
            'reservas'        => $reservasLista,
            'filtros'         => $filtros,
            'reservaFiltroId' => $reservaFiltroId,
            'abrirCobro'      => $abrirCobro,
        ]);
    }

    public function showGrupoDetails($reservaId)
    {
        $desglose = $this->pagoService->getDesgloseGrupal($reservaId);
        
        return response()->json([
            'success' => true,
            'data' => $desglose
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'reserva_id' => 'required|exists:reservas,id',
            'monto_depositado' => 'required|numeric|min:1',
            'metodo_pago' => 'required|string',
            'cliente_id' => 'nullable|exists:clientes,id'
        ]);

        $reserva = Reserva::findOrFail($request->reserva_id);
        
        $montoDepositado = (float) $request->input('monto_depositado', 0); 
        $totalPagado = $reserva->pago()->sum('monto_depositado');
        $pendiente = max(0, (float) $reserva->precio_total_viaje - $totalPagado);
        // cambio para validar que el monto a pagar no sea mayor al pago que se va a realizar
        if ($montoDepositado > $pendiente) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'El  monto a cobrar no puede ser mayor a la deuda pendiente ($$' . number_format($pendiente, 2, '.', '') . ').');
        }

        $datos = $request->only(['reserva_id', 'monto_depositado', 'metodo_pago', 'referencia', 'cliente_id']);
        $datos['user_id'] = Auth::id() ?? 1; // Fallback for dev

        // Si no envía cliente_id (por ejemplo pago de reserva individual general), sacamos de la reserva
        if (empty($datos['cliente_id'])) {
            $datos['cliente_id'] = $reserva->cliente_id;
        }

        $this->pagoService->registrarPago($datos);

        $redirectTo = $request->input('redirect_after', 'pagos');
        $msg = 'Pago registrado correctamente. El estado de la reserva se ha actualizado.';

        if ($redirectTo === 'reservas') {
            return redirect()->route('reservas')->with('success', $msg)->with('toast_sync', true);
        }

        $query = array_filter([
            'reserva_id' => $request->input('reserva_id'),
            'abrir_cobro' => $request->input('abrir_cobro'),
        ], fn ($v) => $v !== null && $v !== '');

        return redirect()->route('pagos', $query)->with('success', $msg)->with('toast_sync', true);
    }

    public function auditoria(Pago $pago)
    {
        $pago->load('user', 'cliente', 'reserva');

        $cobrador = $pago->user
            ? trim(($pago->user->nombres ?? '').' '.($pago->user->apellidos ?? ''))
            : '—';

        return response()->json([
            'success' => true,
            'data'    => [
                'id'              => $pago->id,
                'reserva_id'      => $pago->reserva_id,
                'monto'           => (float) $pago->monto_depositado,
                'metodo_pago'     => ucfirst($pago->metodo_pago),
                'metodo_pago_val' => $pago->metodo_pago,
                'referencia'      => $pago->referencia,
                'fecha_pago'      => $pago->fecha_pago,
                'fecha_pago_fmt'  => \Carbon\Carbon::parse($pago->fecha_pago)->format('d/m/Y H:i:s'),
                'cobrador'        => $cobrador,
                'cliente'         => $pago->cliente
                    ? trim($pago->cliente->nombres.' '.$pago->cliente->apellidos)
                    : '—',
            ],
        ]);
    }

    /**
     * Obtener todos los pagos de una reserva para poder seleccionar cuál anular
     * @param int $reservaId
     * @return \Illuminate\Http\JsonResponse
     */
    public function listaPagosReserva(int $reservaId)
    {
        $pagos = Pago::where('reserva_id', $reservaId)
            ->with(['cliente', 'user'])
            ->orderBy('fecha_pago', 'desc')
            ->get();

        $pagosFormato = $pagos->map(function ($pago) {
            $cobrador = $pago->user
                ? trim(($pago->user->nombres ?? '').' '.($pago->user->apellidos ?? ''))
                : '—';

            return [
                'id'              => $pago->id,
                'monto'           => (float) $pago->monto_depositado,
                'metodo_pago'     => ucfirst($pago->metodo_pago),
                'referencia'      => $pago->referencia ?? '—',
                'fecha_pago_fmt'  => \Carbon\Carbon::parse($pago->fecha_pago)->format('d/m/Y H:i:s'),
                'cobrador'        => $cobrador,
                'cliente'         => $pago->cliente
                    ? trim($pago->cliente->nombres.' '.$pago->cliente->apellidos)
                    : '—',
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $pagosFormato,
            'total'   => $pagos->sum('monto_depositado'),
        ]);
    }

    public function update(Request $request, Pago $pago)
    {
        $request->validate([
            'monto_depositado' => 'required|numeric|min:1',
            'metodo_pago'      => 'required|string',
            'referencia'       => 'nullable|string|max:100',
        ]);

        $this->pagoService->actualizarPago($pago->id, $request->only([
            'monto_depositado',
            'metodo_pago',
            'referencia',
        ]));

        $query = array_filter([
            'reserva_id' => $request->input('reserva_id'),
        ], fn ($v) => $v !== null && $v !== '');

        return redirect()->route('pagos', $query)->with('success', 'Pago actualizado. Estado de reserva sincronizado.')->with('toast_sync', true);
    }

    public function anular(Request $request, Pago $pago)
    {
        $this->pagoService->anularPago($pago->id);

        $query = array_filter([
            'reserva_id' => $request->input('reserva_id'),
        ], fn ($v) => $v !== null && $v !== '');

        return redirect()->route('pagos', $query)->with('success', 'Pago anulado. El estado de la reserva se ha recalculado.')->with('toast_sync', true);
    }

    public function updateIntegrante(Request $request)
    {
        $request->validate([
            'reserva_id'     => 'required|exists:reservas,id',
            'cliente_id'     => 'required|exists:clientes,id',
            'nombres'        => 'required|string|max:250',
            'apellidos'      => 'required|string|max:250',
            'monto_asignado' => 'required|numeric|min:0',
        ]);

        try {
            $this->pagoService->actualizarIntegranteGrupal(
                (int) $request->reserva_id,
                (int) $request->cliente_id,
                $request->only(['nombres', 'apellidos', 'monto_asignado'])
            );

            // Asegurar que el estado de la reserva se sincronice también en caso de cambios de monto
            $this->pagoService->sincronizarEstadoPagoReserva((int) $request->reserva_id);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'message' => 'Integrante actualizado.']);
    }
}
