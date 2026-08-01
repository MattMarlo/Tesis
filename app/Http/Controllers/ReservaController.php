<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReservaController extends Controller
{
    public function index(Request $request)
    {
        $query = Reserva::query()
            ->with([
                'cliente',
                'destino',
                'grupo.responsablePago',
                'grupo.clientes',
            ])
            ->withSum(
                'pagos as total_pagado',
                'monto_depositado'
            );

        if ($request->filled('buscar')) {
            $buscar = trim($request->buscar);

            $query->where(function ($consulta) use ($buscar) {
                $consulta
                    ->where(
                        'codigo_reserva',
                        'like',
                        "%{$buscar}%"
                    )
                    ->orWhereHas(
                        'cliente',
                        function ($cliente) use ($buscar) {
                            $cliente
                                ->where(
                                    'nombres',
                                    'like',
                                    "%{$buscar}%"
                                )
                                ->orWhere(
                                    'apellidos',
                                    'like',
                                    "%{$buscar}%"
                                )
                                ->orWhere(
                                    'documento',
                                    'like',
                                    "%{$buscar}%"
                                );
                        }
                    )
                    ->orWhereHas(
                        'destino',
                        function ($destino) use ($buscar) {
                            $destino
                                ->where(
                                    'nombre_paquete',
                                    'like',
                                    "%{$buscar}%"
                                )
                                ->orWhere(
                                    'ciudad_destino',
                                    'like',
                                    "%{$buscar}%"
                                )
                                ->orWhere(
                                    'pais',
                                    'like',
                                    "%{$buscar}%"
                                );
                        }
                    )
                    ->orWhereHas(
                        'grupo',
                        function ($grupo) use ($buscar) {
                            $grupo->where(
                                'nombre_grupo',
                                'like',
                                "%{$buscar}%"
                            );
                        }
                    );
            });
        }

        if (
            $request->filled('tipo') &&
            in_array(
                $request->tipo,
                ['individual', 'grupal'],
                true
            )
        ) {
            $query->where('tipo', $request->tipo);
        }

        if (
            $request->filled('estado') &&
            in_array(
                $request->estado,
                ['pendiente', 'confirmada', 'cancelada'],
                true
            )
        ) {
            $query->where(
                'estado',
                $request->estado
            );
        }

        $reservas = $query
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $resumen = [
            'total' => Reserva::count(),
            'pendientes' => Reserva::where(
                'estado',
                Reserva::ESTADO_PENDIENTE
            )->count(),
            'confirmadas' => Reserva::where(
                'estado',
                Reserva::ESTADO_CONFIRMADA
            )->count(),
            'canceladas' => Reserva::where(
                'estado',
                Reserva::ESTADO_CANCELADA
            )->count(),
        ];

        return view(
            'modules.reservas.index',
            [
                'titulo' => 'Reservas',
                'reservas' => $reservas,
                'resumen' => $resumen,
            ]
        );
    }

    public function detalleJson(string $id)
    {
        $reserva = Reserva::with([
            'cliente',
            'destino',
            'user',
            'canceladoPor',
            'pagos.cliente',
            'grupo.responsablePago',
            'grupo.clientes',
        ])->findOrFail($id);

        $totalPagado = (float) $reserva
            ->pagos
            ->sum('monto_depositado');

        $saldoPendiente = max(
            0,
            (float) $reserva->precio_total_viaje -
            $totalPagado
        );

        $viajeros = [];

        if ($reserva->esGrupal() && $reserva->grupo) {
            foreach ($reserva->grupo->clientes as $cliente) {
                $montoAsignado =
                    (float) $cliente->pivot->monto_asignado;

                $pagadoIntegrante = $reserva
                    ->pagos
                    ->where(
                        'cliente_id',
                        $cliente->id
                    )
                    ->sum('monto_depositado');

                $esFamiliar =
                    $reserva->grupo->tipo_grupo ===
                    'familiar';

                $viajeros[] = [
                    'id' => $cliente->id,
                    'nombre' =>
                        $cliente->nombre_completo,
                    'documento' =>
                        $cliente->documento,
                    'edad' =>
                        $cliente->pivot->edad_al_viajar,
                    'categoria' =>
                        $this->nombreCategoria(
                            $cliente->pivot
                                ->categoria_tarifa
                        ),
                    'porcentaje' =>
                        (float) $cliente->pivot
                            ->porcentaje_tarifa,
                    'precio' => $montoAsignado,
                    'es_lider' =>
                        (bool) $cliente->pivot
                            ->es_lider,
                    'pagado' => $esFamiliar
                        ? null
                        : (float) $pagadoIntegrante,
                    'saldo' => $esFamiliar
                        ? null
                        : max(
                            0,
                            $montoAsignado -
                            $pagadoIntegrante
                        ),
                ];
            }
        } elseif ($reserva->cliente) {
            $viajeros[] = [
                'id' => $reserva->cliente->id,
                'nombre' =>
                    $reserva->cliente->nombre_completo,
                'documento' =>
                    $reserva->cliente->documento,
                'edad' => $reserva->edad_viajero,
                'categoria' =>
                    $this->nombreCategoria(
                        $reserva->categoria_tarifa
                    ),
                'porcentaje' =>
                    $reserva->porcentaje_tarifa !== null
                        ? (float) $reserva
                            ->porcentaje_tarifa
                        : null,
                'precio' =>
                    (float) $reserva
                        ->precio_total_viaje,
                'es_lider' => true,
                'pagado' => $totalPagado,
                'saldo' => $saldoPendiente,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $reserva->id,
                'codigo' =>
                    $reserva->codigo_reserva,
                'tipo' => $reserva->tipo,
                'estado' => $reserva->estado,
                'estado_pago' =>
                    $reserva->estado_pago,
                'moneda' =>
                    $reserva->moneda ?: 'USD',
                'fecha_registro' =>
                    $reserva->fecha_reserva
                        ?->format('d/m/Y'),
                'fecha_viaje' =>
                    $reserva->fecha_viaje
                        ?->format('d/m/Y'),
                'precio_base_persona' =>
                    (float) (
                        $reserva
                            ->precio_base_persona ?? 0
                    ),
                'precio_total' =>
                    (float) $reserva
                        ->precio_total_viaje,
                'total_pagado' =>
                    $totalPagado,
                'saldo_pendiente' =>
                    $saldoPendiente,
                'cantidad_viajeros' =>
                    $reserva->cantidad_viajeros
                    ?: count($viajeros),
                'paquete' => [
                    'nombre' =>
                        $reserva->destino
                            ?->nombre_paquete,
                    'origen' =>
                        $reserva->destino
                            ?->ciudad_salida,
                    'destino' =>
                        $reserva->destino
                            ?->ciudad_destino,
                    'pais' =>
                        $reserva->destino?->pais,
                    'fecha_regreso' =>
                        $reserva->destino
                            ?->fecha_regreso
                            ?->format('d/m/Y'),
                ],
                'titular' => [
                    'id' =>
                        $reserva->cliente?->id,
                    'nombre' =>
                        $reserva->cliente
                            ?->nombre_completo,
                    'documento' =>
                        $reserva->cliente
                            ?->documento,
                    'email' =>
                        $reserva->cliente?->email,
                    'telefono' =>
                        $reserva->cliente
                            ?->telefono,
                ],
                'grupo' => $reserva->grupo
                    ? [
                        'nombre' =>
                            $reserva->grupo
                                ->nombre_grupo,
                        'tipo' =>
                            $reserva->grupo
                                ->tipo_grupo,
                        'responsable_pago' =>
                            $reserva->grupo
                                ->responsablePago
                                ?->nombre_completo,
                    ]
                    : null,
                'viajeros' => $viajeros,
                'registrado_por' =>
                    $reserva->user
                        ?->nombres,
                'cancelacion' =>
                    $reserva->estaCancelada()
                        ? [
                            'motivo' =>
                                $reserva
                                    ->motivo_cancelacion,
                            'fecha' =>
                                $reserva
                                    ->fecha_cancelacion
                                    ?->format(
                                        'd/m/Y H:i'
                                    ),
                            'usuario' =>
                                $reserva
                                    ->canceladoPor
                                    ?->nombres,
                        ]
                        : null,
            ],
        ]);
    }

    public function cancelar(
        Request $request,
        string $id
    ) {
        $datos = $request->validate([
            'motivo_cancelacion' => [
                'required',
                'string',
                'min:10',
                'max:500',
            ],
        ], [
            'motivo_cancelacion.required' =>
                'Escribe el motivo de la cancelación.',
            'motivo_cancelacion.min' =>
                'El motivo debe tener al menos 10 caracteres.',
            'motivo_cancelacion.max' =>
                'El motivo no puede superar 500 caracteres.',
        ]);

        $reserva = Reserva::findOrFail($id);

        if ($reserva->estaCancelada()) {
            return response()->json([
                'success' => false,
                'message' =>
                    'La reserva ya se encuentra cancelada.',
            ], 422);
        }

        try {
            DB::transaction(function () use (
                $reserva,
                $datos,
                $request
            ) {
                $reserva->update([
                    'estado' =>
                        Reserva::ESTADO_CANCELADA,
                    'motivo_cancelacion' =>
                        trim(
                            $datos[
                                'motivo_cancelacion'
                            ]
                        ),
                    'fecha_cancelacion' => now(),
                    'cancelado_por_user_id' =>
                        $request->user()->id,
                ]);
            });

            return response()->json([
                'success' => true,
                'message' =>
                    'Reserva cancelada correctamente.',
            ]);
        } catch (\Throwable $error) {
            Log::error(
                'Error al cancelar reserva',
                [
                    'reserva_id' =>
                        $reserva->id,
                    'mensaje' =>
                        $error->getMessage(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' =>
                    'No se pudo cancelar la reserva.',
            ], 500);
        }
    }

    private function nombreCategoria(
        ?string $categoria
    ): string {
        return match ($categoria) {
            Reserva::TARIFA_INFANTE =>
                'Infante',
            Reserva::TARIFA_NINO =>
                'Niño',
            Reserva::TARIFA_ADULTO =>
                'Adulto',
            Reserva::TARIFA_ADULTO_MAYOR =>
                'Adulto mayor',
            default =>
                'Sin información',
        };
    }
}