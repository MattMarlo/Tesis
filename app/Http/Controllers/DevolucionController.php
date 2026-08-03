<?php

namespace App\Http\Controllers;

use App\Models\Devolucion;
use App\Models\Pago;
use App\Services\DevolucionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class DevolucionController extends Controller
{
    public function __construct(private DevolucionService $servicio) {}

    public function index(Request $request)
    {
        $buscar = trim((string) $request->input('buscar'));

        $devoluciones = Devolucion::query()
            ->with(['pago', 'reserva', 'cliente', 'usuario', 'anuladaPor'])
            ->when($buscar, function ($query) use ($buscar) {
                $query->whereHas('reserva', fn ($q) => $q->where('codigo_reserva', 'like', "%{$buscar}%"))
                    ->orWhereHas('cliente', fn ($q) => $q->where('nombres', 'like', "%{$buscar}%")
                        ->orWhere('apellidos', 'like', "%{$buscar}%"));
            })
            ->latest('fecha_devolucion')->paginate(15)->withQueryString();

        $pagos = Pago::query()
            ->registrados()
            ->with(['reserva', 'cliente'])
            ->withSum(['devoluciones as total_devuelto' => fn ($q) => $q->procesadas()], 'monto')
            ->latest('fecha_pago')->get()
            ->filter(fn ($pago) => round((float) $pago->monto_depositado - (float) $pago->total_devuelto, 2) > 0);

        $totalProcesado = (float) Devolucion::query()->procesadas()->sum('monto');

        return view('modules.devoluciones.index', compact('devoluciones', 'pagos', 'totalProcesado', 'buscar'));
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'pago_id' => ['required', 'integer', 'exists:pagos,id'],
            'monto' => ['required', 'numeric', 'gt:0'],
            'metodo' => ['required', Rule::in(['efectivo', 'transferencia', 'tarjeta', 'otro'])],
            'referencia' => ['nullable', 'string', 'max:100', 'required_if:metodo,transferencia,tarjeta'],
            'tipo' => ['required', Rule::in([
                'cancelacion', 'correccion', 'pago_duplicado',
                'reduccion_servicios', 'comercial', 'otro',
            ])],
            'motivo' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'pago_id.required' => 'Selecciona el pago que será reembolsado.',
            'pago_id.exists' => 'El pago seleccionado no existe.',
            'monto.required' => 'Ingresa el monto de la devolución.',
            'monto.gt' => 'El monto debe ser mayor que cero.',
            'metodo.required' => 'Selecciona el método de devolución.',
            'referencia.required_if' => 'Ingresa la referencia de la transferencia o tarjeta.',
            'tipo.required' => 'Selecciona el tipo de devolución.',
            'motivo.required' => 'Explica el motivo de la devolución.',
            'motivo.min' => 'El motivo debe tener al menos 10 caracteres.',
        ]);

        try {
            $datos['user_id'] = (int) Auth::id();
            $this->servicio->registrar($datos);

            return redirect()->route('devoluciones.index')->with('success', 'Devolución registrada correctamente.');
        } catch (InvalidArgumentException $error) {
            return back()->withInput()->with('error', $error->getMessage());
        } catch (\Throwable $error) {
            Log::error('Error al registrar devolución', ['mensaje' => $error->getMessage()]);

            return back()->withInput()->with('error', 'No se pudo registrar la devolución.');
        }
    }

    public function anular(Request $request, Devolucion $devolucion)
    {
        $datos = $request->validate([
            'motivo_anulacion' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        try {
            $this->servicio->anular($devolucion, $datos['motivo_anulacion'], (int) Auth::id());

            return back()->with('success', 'Devolución anulada correctamente.');
        } catch (InvalidArgumentException $error) {
            return back()->with('error', $error->getMessage());
        }
    }
}
