<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PagoService;
use App\Models\Reserva;
use App\Models\Pago;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

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
        $datos = $request->validate([
            'reserva_id' => [
                'required',
                'integer',
                'exists:reservas,id',
            ],
            'cliente_id' => [
                'nullable',
                'integer',
                'exists:clientes,id',
            ],
            'monto_depositado' => [
                'required',
                'numeric',
                'gt:0',
            ],
            'metodo_pago' => [
                'required',
                Rule::in([
                    Pago::METODO_EFECTIVO,
                    Pago::METODO_TRANSFERENCIA,
                    Pago::METODO_TARJETA,
                    Pago::METODO_OTRO,
                ]),
            ],
            'referencia' => [
                'nullable',
                'string',
                'max:100',
                'required_if:metodo_pago,transferencia,tarjeta',
            ],
            'redirect_after' => [
                'nullable',
                Rule::in([
                    'pagos',
                    'reservas',
                ]),
            ],
        ], [
            'reserva_id.required' =>
                'Selecciona la reserva que recibirá el pago.',
            'reserva_id.exists' =>
                'La reserva seleccionada no existe.',

            'cliente_id.exists' =>
                'El integrante seleccionado no existe.',

            'monto_depositado.required' =>
                'Ingresa el monto recibido.',
            'monto_depositado.numeric' =>
                'El monto debe ser un valor numérico.',
            'monto_depositado.gt' =>
                'El monto debe ser mayor que cero.',

            'metodo_pago.required' =>
                'Selecciona el método de pago.',
            'metodo_pago.in' =>
                'El método de pago seleccionado no es válido.',

            'referencia.required_if' =>
                'Ingresa el número de comprobante o referencia.',
            'referencia.max' =>
                'La referencia no puede superar 100 caracteres.',
        ]);

        $usuarioId = Auth::id();

        if (!$usuarioId) {
            return redirect()
                ->route('login')
                ->with(
                    'error',
                    'Debes iniciar sesión para registrar un pago.'
                );
        }

        $datos['user_id'] = (int) $usuarioId;

        try {
            $this->pagoService->registrarPago(
                $datos
            );

            $mensaje =
                'Pago registrado correctamente.';

            if (
                ($datos['redirect_after'] ?? null) ===
                'reservas'
            ) {
                return redirect()
                    ->route('reservas')
                    ->with('success', $mensaje);
            }

            $consulta = array_filter([
                'reserva_id' =>
                    $datos['reserva_id'],
            ]);

            return redirect()
                ->route('pagos', $consulta)
                ->with('success', $mensaje);
        } catch (InvalidArgumentException $error) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    $error->getMessage()
                );
        } catch (\Throwable $error) {
            Log::error(
                'Error al registrar pago',
                [
                    'reserva_id' =>
                        $datos['reserva_id'] ?? null,
                    'usuario_id' => $usuarioId,
                    'mensaje' =>
                        $error->getMessage(),
                ]
            );

            return back()
                ->withInput()
                ->with(
                    'error',
                    'No se pudo registrar el pago. Inténtalo nuevamente.'
                );
        }
    }

    public function auditoria(Pago $pago)
    {
        $pago->load([
            'user',
            'cliente',
            'reserva',
            'anuladoPor',
        ]);

        $cobrador = 'Sin información';

        if ($pago->user) {
            $cobrador = trim(
                ($pago->user->nombres ?? '') .
                ' ' .
                ($pago->user->apellidos ?? '')
            );
        }

        $nombreCliente = 'Sin información';

        if ($pago->cliente) {
            $nombreCliente = trim(
                ($pago->cliente->nombres ?? '') .
                ' ' .
                ($pago->cliente->apellidos ?? '')
            );
        }

        $usuarioAnulacion = null;

        if ($pago->anuladoPor) {
            $usuarioAnulacion = trim(
                ($pago->anuladoPor->nombres ?? '') .
                ' ' .
                ($pago->anuladoPor->apellidos ?? '')
            );
        }

        $reservaCancelada = $pago->reserva
            ? $pago->reserva->estaCancelada()
            : false;

        return response()->json([
            'success' => true,
            'data' => [
                'id' =>
                    $pago->id,
                'reserva_id' =>
                    $pago->reserva_id,
                'codigo_reserva' =>
                    $pago->reserva
                        ? $pago->reserva
                            ->codigo_reserva
                        : null,
                'moneda' =>
                    $pago->reserva &&
                    $pago->reserva->moneda
                        ? $pago->reserva->moneda
                        : 'USD',
                'monto' =>
                    (float) $pago
                        ->monto_depositado,
                'metodo_pago' =>
                    ucfirst(
                        $pago->metodo_pago
                    ),
                'metodo_pago_val' =>
                    $pago->metodo_pago,
                'referencia' =>
                    $pago->referencia,
                'fecha_pago' =>
                    $pago->fecha_pago
                        ? $pago->fecha_pago
                            ->format('Y-m-d H:i:s')
                        : null,
                'fecha_pago_fmt' =>
                    $pago->fecha_pago
                        ? $pago->fecha_pago
                            ->format('d/m/Y H:i')
                        : null,
                'cobrador' =>
                    $cobrador,
                'cliente' =>
                    $nombreCliente,
                'estado' =>
                    $pago->estado,
                'esta_anulado' =>
                    $pago->estaAnulado(),
                'motivo_anulacion' =>
                    $pago->motivo_anulacion,
                'fecha_anulacion' =>
                    $pago->fecha_anulacion
                        ? $pago->fecha_anulacion
                            ->format('d/m/Y H:i')
                        : null,
                'anulado_por' =>
                    $usuarioAnulacion,
                'puede_editar' =>
                    !$pago->estaAnulado() &&
                    !$reservaCancelada,
                'puede_anular' =>
                    !$pago->estaAnulado(),
            ],
        ]);
    }

    /**
     * Obtener todos los pagos de una reserva para poder seleccionar cuál anular
     * @param int $reservaId
     * @return \Illuminate\Http\JsonResponse
     */

    public function listaPagosReserva(
        int $reservaId
    ) {
        $pagos = Pago::query()
            ->where(
                'reserva_id',
                $reservaId
            )
            ->with([
                'cliente',
                'user',
                'anuladoPor',
                'reserva',
            ])
            ->orderByDesc('fecha_pago')
            ->get();

        $pagosFormato = $pagos->map(
            function ($pago) {
                $cobrador = $pago->user
                    ? trim(
                        ($pago->user->nombres ?? '') .
                        ' ' .
                        ($pago->user->apellidos ?? '')
                    )
                    : 'Sin información';

                return [
                    'id' =>
                        $pago->id,
                    'monto' =>
                        (float) $pago
                            ->monto_depositado,
                    'moneda' =>
                        $pago->reserva?->moneda
                            ?: 'USD',
                    'metodo_pago' =>
                        ucfirst(
                            $pago->metodo_pago
                        ),
                    'metodo_pago_val' =>
                        $pago->metodo_pago,
                    'referencia' =>
                        $pago->referencia
                            ?: 'Sin referencia',
                    'fecha_pago_fmt' =>
                        $pago->fecha_pago
                            ?->format('d/m/Y H:i'),
                    'cobrador' =>
                        $cobrador,
                    'cliente' => $pago->cliente
                        ? trim(
                            ($pago->cliente->nombres ?? '') .
                            ' ' .
                            ($pago->cliente->apellidos ?? '')
                        )
                        : 'Sin información',
                    'estado' =>
                        $pago->estado,
                    'esta_anulado' =>
                        $pago->estaAnulado(),
                    'motivo_anulacion' =>
                        $pago->motivo_anulacion,
                    'fecha_anulacion' =>
                        $pago->fecha_anulacion
                            ?->format('d/m/Y H:i'),
                    'puede_editar' =>
                        !$pago->estaAnulado() &&
                        !$pago->reserva
                            ?->estaCancelada(),
                    'puede_anular' =>
                        !$pago->estaAnulado(),
                ];
            }
        );

        $totalRegistrado = (float) $pagos
            ->where(
                'estado',
                Pago::ESTADO_REGISTRADO
            )
            ->sum('monto_depositado');

        return response()->json([
            'success' =>
                true,
            'data' =>
                $pagosFormato,
            'total_registrado' =>
                $totalRegistrado,
        ]);
    }

    public function update(
        Request $request,
        Pago $pago
    ) {
        $datos = $request->validate([
            'monto_depositado' => [
                'required',
                'numeric',
                'gt:0',
            ],
            'metodo_pago' => [
                'required',
                Rule::in([
                    Pago::METODO_EFECTIVO,
                    Pago::METODO_TRANSFERENCIA,
                    Pago::METODO_TARJETA,
                    Pago::METODO_OTRO,
                ]),
            ],
            'referencia' => [
                'nullable',
                'string',
                'max:100',
                'required_if:metodo_pago,transferencia,tarjeta',
            ],
            'reserva_id' => [
                'nullable',
                'integer',
            ],
        ], [
            'monto_depositado.required' =>
                'Ingresa el monto del pago.',
            'monto_depositado.numeric' =>
                'El monto debe ser un valor numérico.',
            'monto_depositado.gt' =>
                'El monto debe ser mayor que cero.',

            'metodo_pago.required' =>
                'Selecciona el método de pago.',
            'metodo_pago.in' =>
                'El método de pago no es válido.',

            'referencia.required_if' =>
                'Ingresa el comprobante o referencia.',
            'referencia.max' =>
                'La referencia no puede superar 100 caracteres.',
        ]);

        try {
            $this->pagoService->actualizarPago(
                $pago->id,
                $datos
            );

            $consulta = array_filter([
                'reserva_id' =>
                    $datos['reserva_id'] ?? null,
            ]);

            return redirect()
                ->route('pagos', $consulta)
                ->with(
                    'success',
                    'Pago actualizado correctamente.'
                );
        } catch (InvalidArgumentException $error) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    $error->getMessage()
                );
        } catch (\Throwable $error) {
            Log::error(
                'Error al actualizar pago',
                [
                    'pago_id' => $pago->id,
                    'usuario_id' =>
                        Auth::id(),
                    'mensaje' =>
                        $error->getMessage(),
                ]
            );

            return back()
                ->withInput()
                ->with(
                    'error',
                    'No se pudo actualizar el pago.'
                );
        }
    }

    public function anular(
        Request $request,
        Pago $pago
    ) {
        $datos = $request->validate([
            'motivo_anulacion' => [
                'required',
                'string',
                'min:10',
                'max:500',
            ],
            'reserva_id' => [
                'nullable',
                'integer',
            ],
        ], [
            'motivo_anulacion.required' =>
                'Escribe el motivo de la anulación.',
            'motivo_anulacion.min' =>
                'El motivo debe tener al menos 10 caracteres.',
            'motivo_anulacion.max' =>
                'El motivo no puede superar 500 caracteres.',
        ]);

        $usuarioId = Auth::id();

        if (!$usuarioId) {
            return redirect()
                ->route('login')
                ->with(
                    'error',
                    'Debes iniciar sesión para anular un pago.'
                );
        }

        try {
            $this->pagoService->anularPago(
                $pago->id,
                $datos['motivo_anulacion'],
                (int) $usuarioId
            );

            $consulta = array_filter([
                'reserva_id' =>
                    $datos['reserva_id'] ?? null,
            ]);

            return redirect()
                ->route('pagos', $consulta)
                ->with(
                    'success',
                    'Pago anulado correctamente. El historial se mantiene disponible.'
                );
        } catch (InvalidArgumentException $error) {
            return back()->with(
                'error',
                $error->getMessage()
            );
        } catch (\Throwable $error) {
            Log::error(
                'Error al anular pago',
                [
                    'pago_id' => $pago->id,
                    'usuario_id' => $usuarioId,
                    'mensaje' =>
                        $error->getMessage(),
                ]
            );

            return back()->with(
                'error',
                'No se pudo anular el pago.'
            );
        }
    }

}
