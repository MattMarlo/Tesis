<?php

namespace App\Services;

use App\Models\Reserva;
use App\Models\Pago;
use App\Models\Grupo;
use InvalidArgumentException;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PagoService
{
    public function __construct(
        private readonly TarifaReservaService $tarifaService
    ) {
    }

    public function getMetricasGenerales(): array
    {
        $reservas = Reserva::query()
            ->where(
                'estado',
                '!=',
                Reserva::ESTADO_CANCELADA
            )
            ->withSum(
                'pagos as total_pagado',
                'monto_depositado'
            )
            ->get();

        $totalEsperado = (float) $reservas
            ->sum('precio_total_viaje');

        $totalPagos = (float) $reservas
            ->sum(
                fn ($reserva) =>
                    (float) (
                        $reserva->total_pagado ?? 0
                    )
            );

        $totalTransacciones = Pago::query()
            ->registrados()
            ->whereHas(
                'reserva',
                function ($consulta) {
                    $consulta->where(
                        'estado',
                        '!=',
                        Reserva::ESTADO_CANCELADA
                    );
                }
            )
            ->count();

        $pendiente = max(
            0,
            $totalEsperado - $totalPagos
        );

        $reservasConDeuda = $reservas
            ->filter(function ($reserva) {
                return (float) $reserva
                    ->precio_total_viaje >
                    (float) (
                        $reserva->total_pagado ?? 0
                    );
            })
            ->count();

        $sinIniciar = $reservas
            ->filter(function ($reserva) {
                return (float) (
                    $reserva->total_pagado ?? 0
                ) <= 0;
            });

        $sinIniciarMonto = (float) $sinIniciar
            ->sum('precio_total_viaje');

        $reservaCritica = $sinIniciar
            ->sortBy('fecha_viaje')
            ->first()
            ?->id;

        $tasaCobro = $totalEsperado > 0
            ? round(
                ($totalPagos / $totalEsperado) * 100
            )
            : 0;

        return [
            'total_pagos' =>
                $totalPagos,
            'total_trx' =>
                $totalTransacciones,
            'cobrado' =>
                $totalPagos,
            'tasa_cobro' =>
                $tasaCobro,
            'pendiente' =>
                $pendiente,
            'reservas_deuda' =>
                $reservasConDeuda,
            'sin_iniciar_monto' =>
                $sinIniciarMonto,
            'reserva_critica' =>
                $reservaCritica,
        ];
    }

    public function getListaReservas(
        array $filtros = []
    ) {
        $reservas = Reserva::query()
            ->with([
                'cliente',
                'destino',
                'grupo.responsablePago',
                'grupo.clientes',
                'pagos',
            ])
            ->latest()
            ->get();

        $lista = $reservas->map(
            function ($reserva) {
                $pagado = (float) $reserva
                    ->pagos
                    ->sum('monto_depositado');

                $precioTotal = (float)
                    $reserva->precio_total_viaje;

                $pendiente = max(
                    0,
                    $precioTotal - $pagado
                );

                if ($reserva->estaCancelada()) {
                    $estadoCalculado =
                        'Cancelada';
                } elseif (
                    $precioTotal > 0 &&
                    $pagado >= $precioTotal
                ) {
                    $estadoCalculado =
                        'Completado';
                } elseif ($pagado > 0) {
                    $estadoCalculado =
                        'Parcial';
                } else {
                    $estadoCalculado =
                        'Sin pago';
                }

                $porcentaje = $precioTotal > 0
                    ? min(
                        100,
                        round(
                            ($pagado / $precioTotal) *
                            100
                        )
                    )
                    : 0;

                $ultimoPago = $reserva
                    ->pagos
                    ->sortByDesc('fecha_pago')
                    ->first();

                $grupo = $reserva->grupo;

                if (
                    $reserva->esGrupal() &&
                    $grupo
                ) {
                    $nombre = $grupo->nombre_grupo;
                } else {
                    $nombre = $reserva->cliente
                        ? $reserva->cliente
                            ->nombre_completo
                        : 'Cliente no disponible';
                }

                $modalidadPago = 'Individual';

                if ($grupo?->esFamiliar()) {
                    $modalidadPago =
                        'Pago familiar';
                } elseif (
                    $grupo?->esIndependiente()
                ) {
                    $modalidadPago =
                        'Pago por integrante';
                }

                return [
                    'reserva_id' =>
                        $reserva->id,
                    'codigo_reserva' =>
                        $reserva->codigo_reserva,
                    'tipo' =>
                        $reserva->tipo,
                    'cliente_grupo' =>
                        $nombre,
                    'paquete' =>
                        $reserva->destino
                            ?->nombre_paquete,
                    'moneda' =>
                        $reserva->moneda ?: 'USD',
                    'pagado' =>
                        $pagado,
                    'pendiente' =>
                        $pendiente,
                    'precio_total' =>
                        $precioTotal,
                    'metodo' =>
                        $ultimoPago
                            ? ucfirst(
                                $ultimoPago
                                    ->metodo_pago
                            )
                            : 'Sin registro',
                    'metodo_valor' =>
                        $ultimoPago
                            ? $ultimoPago
                                ->metodo_pago
                            : null,
                    'fecha_ultimo_pago' =>
                        $ultimoPago
                            ? $ultimoPago
                                ->fecha_pago
                                ?->format('d/m/Y')
                            : null,
                    'estado' =>
                        $estadoCalculado,
                    'estado_reserva' =>
                        $reserva->estado,
                    'porcentaje' =>
                        $porcentaje,
                    'id_ultimo_pago' =>
                        $ultimoPago?->id,
                    'modalidad_pago' =>
                        $modalidadPago,
                    'responsable_pago' =>
                        $grupo
                            ?->responsablePago
                            ?->nombre_completo,
                    'cliente_pago_id' =>
                        $reserva->esIndividual()
                            ? $reserva->cliente_id
                            : (
                                $grupo?->esFamiliar()
                                    ? $grupo
                                        ->responsable_pago_id
                                    : null
                            ),

                    'nombre_pagador' =>
                        $reserva->esIndividual()
                            ? $reserva->cliente
                                ?->nombre_completo
                            : (
                                $grupo?->esFamiliar()
                                    ? $grupo
                                        ->responsablePago
                                        ?->nombre_completo
                                    : null
                            ),
                    'cantidad_viajeros' =>
                        $reserva
                            ->cantidad_viajeros,
                    'puede_cobrar' =>
                        !$reserva->estaCancelada() &&
                        $pendiente > 0,
                ];
            }
        );

        if (
            !empty($filtros['estado']) &&
            $filtros['estado'] !== 'todos'
        ) {
            $estado = mb_strtolower(
                $filtros['estado']
            );

            $lista = $lista
                ->filter(
                    fn ($fila) =>
                        mb_strtolower(
                            $fila['estado']
                        ) === $estado
                )
                ->values();
        }

        if (
            !empty($filtros['metodo']) &&
            $filtros['metodo'] !== 'todos'
        ) {
            $metodo = mb_strtolower(
                $filtros['metodo']
            );

            $lista = $lista
                ->filter(
                    fn ($fila) =>
                        $fila['metodo_valor'] ===
                        $metodo
                )
                ->values();
        }

        return $lista;
    }

    public function getDesgloseGrupal(
        int $reservaId
    ): array {
        $reserva = Reserva::query()
            ->with([
                'grupo.responsablePago',
                'grupo.clientes',
                'pagos',
                'destino',
            ])
            ->findOrFail($reservaId);

        if (
            !$reserva->esGrupal() ||
            !$reserva->grupo
        ) {
            throw new InvalidArgumentException(
                'La reserva seleccionada no es grupal.'
            );
        }

        $grupo = $reserva->grupo;
        $esFamiliar = $grupo->esFamiliar();
        $usaCategoriasFamiliares =
            $grupo->usaCategoriasFamiliares();

        $totalPagado = (float) $reserva
            ->pagos
            ->sum('monto_depositado');

        $saldoTotal = max(
            0,
            (float) $reserva->precio_total_viaje -
            $totalPagado
        );

        $integrantes = $grupo
            ->clientes
            ->map(function ($cliente) use (
                $reserva,
                $esFamiliar,
                $usaCategoriasFamiliares
            ) {
                $montoAsignado = (float) (
                    $cliente->pivot
                        ->monto_asignado ?? 0
                );

                if ($esFamiliar) {
                    $pagado = null;
                    $pendiente = null;
                    $estado = 'Pago colectivo';
                } else {
                    $pagado = (float) $reserva
                        ->pagos
                        ->where(
                            'cliente_id',
                            $cliente->id
                        )
                        ->sum('monto_depositado');

                    $pendiente = max(
                        0,
                        $montoAsignado -
                        $pagado
                    );

                    if (
                        $montoAsignado > 0 &&
                        $pagado >= $montoAsignado
                    ) {
                        $estado = 'Pagado';
                    } elseif ($pagado > 0) {
                        $estado = 'Parcial';
                    } else {
                        $estado = 'Sin pago';
                    }
                }

                return [
                    'cliente_id' =>
                        $cliente->id,
                    'nombre_completo' =>
                        $cliente->nombre_completo,
                    'documento' =>
                        $cliente->documento,
                    'es_lider' =>
                        (bool) $cliente->pivot
                            ->es_lider,
                    'es_responsable_pago' =>
                        (int) $cliente->id ===
                        (int) (
                            $reserva->grupo
                                ->responsable_pago_id ?? 0
                        ),
                    'edad' => $usaCategoriasFamiliares
                        ? null
                        : $cliente->pivot->edad_al_viajar,
                    'categoria' => $usaCategoriasFamiliares
                        ? 'Titular y responsable del pago'
                        : $cliente->pivot->categoria_tarifa,
                    'porcentaje' => $usaCategoriasFamiliares
                        ? null
                        : $cliente->pivot->porcentaje_tarifa,
                    'asignado' => $usaCategoriasFamiliares
                        ? null
                        : $montoAsignado,
                    'es_titular_familiar' =>
                        $usaCategoriasFamiliares,
                    'pagado' =>
                        $pagado,
                    'pendiente' =>
                        $pendiente,
                    'estado' =>
                        $estado,
                    'puede_cobrar' =>
                        !$esFamiliar &&
                        !$reserva->estaCancelada() &&
                        $pendiente > 0,
                ];
            })
            ->values()
            ->all();

        return [
            'reserva_id' =>
                $reserva->id,
            'codigo_reserva' =>
                $reserva->codigo_reserva,
            'nombre_grupo' =>
                $grupo->nombre_grupo,
            'tipo_grupo' =>
                $grupo->tipo_grupo,
            'modalidad' =>
                $esFamiliar
                    ? 'familiar'
                    : 'independiente',
            'moneda' =>
                $reserva->moneda ?: 'USD',
            'responsable_pago' =>
                $grupo->responsablePago
                    ? [
                        'id' =>
                            $grupo
                                ->responsablePago->id,
                        'nombre' =>
                            $grupo
                                ->responsablePago
                                ->nombre_completo,
                    ]
                    : null,
            'precio_total' =>
                (float) $reserva
                    ->precio_total_viaje,
            'total_pagado' =>
                $totalPagado,
            'saldo_total' =>
                $saldoTotal,
            'puede_cobrar_total' =>
                $esFamiliar &&
                !$reserva->estaCancelada() &&
                $saldoTotal > 0,
            'composicion_familiar' =>
                $grupo->usaCategoriasFamiliares()
                    ? [
                        ...$grupo->composicionFamiliar(),
                        'cantidad_viajeros' =>
                            $grupo
                                ->cantidad_viajeros_por_categorias,
                    ]
                    : null,
            'desglose_familiar' =>
                $usaCategoriasFamiliares
                    ? $this->tarifaService
                        ->calcularPorCantidadesFamiliares(
                            $reserva->destino,
                            $grupo->composicionFamiliar()
                        )
                    : null,
            'integrantes' =>
                $integrantes,
        ];
    }

    public function registrarPago(array $datos)
    {
        return DB::transaction(function () use ($datos) {
            $reserva = Reserva::query()
                ->with([
                    'grupo.clientes',
                    'grupo.responsablePago',
                ])
                ->lockForUpdate()
                ->findOrFail($datos['reserva_id']);

            if ($reserva->estaCancelada()) {
                throw new InvalidArgumentException(
                    'No se pueden registrar pagos en una reserva cancelada.'
                );
            }

            $monto = round(
                (float) ($datos['monto_depositado'] ?? 0),
                2
            );

            if ($monto <= 0) {
                throw new InvalidArgumentException(
                    'El monto del pago debe ser mayor que cero.'
                );
            }

            $metodo = strtolower(
                trim($datos['metodo_pago'] ?? '')
            );

            $metodosPermitidos = [
                Pago::METODO_EFECTIVO,
                Pago::METODO_TRANSFERENCIA,
                Pago::METODO_TARJETA,
                Pago::METODO_OTRO,
            ];

            if (!in_array(
                $metodo,
                $metodosPermitidos,
                true
            )) {
                throw new InvalidArgumentException(
                    'El método de pago seleccionado no es válido.'
                );
            }

            $clienteId = (int) (
                $datos['cliente_id'] ?? 0
            );

            if ($reserva->esIndividual()) {
                $clienteId = (int) $reserva->cliente_id;

                $totalPagado = (float) Pago::query()
                    ->registrados()
                    ->where(
                        'reserva_id',
                        $reserva->id
                    )
                    ->sum('monto_depositado');

                $saldoPermitido = max(
                    0,
                    (float) $reserva->precio_total_viaje -
                    $totalPagado
                );
            } else {
                $grupo = $reserva->grupo;

                if (!$grupo) {
                    throw new InvalidArgumentException(
                        'La reserva no tiene un grupo asociado.'
                    );
                }

                if ($grupo->esFamiliar()) {
                    $clienteId = (int) (
                        $grupo->responsable_pago_id ?? 0
                    );

                    if (!$clienteId) {
                        throw new InvalidArgumentException(
                            'El grupo familiar no tiene un responsable del pago.'
                        );
                    }

                    $totalPagado = (float) Pago::query()
                        ->registrados()
                        ->where(
                            'reserva_id',
                            $reserva->id
                        )
                        ->sum('monto_depositado');

                    $saldoPermitido = max(
                        0,
                        (float) $reserva->precio_total_viaje -
                        $totalPagado
                    );
                } else {
                    if (!$clienteId) {
                        throw new InvalidArgumentException(
                            'Selecciona el integrante que realiza el pago.'
                        );
                    }

                    $integrante = $grupo
                        ->clientes
                        ->firstWhere(
                            'id',
                            $clienteId
                        );

                    if (!$integrante) {
                        throw new InvalidArgumentException(
                            'El cliente seleccionado no pertenece al grupo.'
                        );
                    }

                    $montoAsignado = (float) (
                        $integrante->pivot
                            ->monto_asignado ?? 0
                    );

                    $pagadoIntegrante = (float) Pago::query()
                        ->registrados()
                        ->where(
                            'reserva_id',
                            $reserva->id
                        )
                        ->where(
                            'cliente_id',
                            $clienteId
                        )
                        ->sum('monto_depositado');

                    $saldoPermitido = max(
                        0,
                        $montoAsignado -
                        $pagadoIntegrante
                    );
                }
            }

            $saldoPermitido = round(
                $saldoPermitido,
                2
            );

            if ($saldoPermitido <= 0) {
                throw new InvalidArgumentException(
                    'La deuda seleccionada ya está pagada.'
                );
            }

            if ($monto > $saldoPermitido) {
                throw new InvalidArgumentException(
                    'El pago no puede superar el saldo pendiente de ' .
                    number_format(
                        $saldoPermitido,
                        2,
                        '.',
                        ''
                    ) .
                    ' ' .
                    ($reserva->moneda ?: 'USD') .
                    '.'
                );
            }

            $pago = Pago::create([
                'reserva_id' => $reserva->id,
                'cliente_id' => $clienteId,
                'user_id' => $datos['user_id'],
                'monto_depositado' => $monto,
                'fecha_pago' => now(),
                'metodo_pago' => $metodo,
                'referencia' => !empty(
                    $datos['referencia']
                )
                    ? trim($datos['referencia'])
                    : null,
                'estado' => Pago::ESTADO_REGISTRADO,
            ]);

            $this->sincronizarEstadoPagoReserva(
                $reserva->id
            );

            return $pago->id;
        });
    }

    /**
     * Recalcula estado_pago (y confirma la reserva si corresponde) según suma de pagos.
     */
    public function sincronizarEstadoPagoReserva(
        int $reservaId
    ): void {
        $reserva = Reserva::find(
            $reservaId
        );

        if (!$reserva) {
            return;
        }

        $totalPagado = (float) Pago::query()
            ->registrados()
            ->where(
                'reserva_id',
                $reserva->id
            )
            ->sum('monto_depositado');

        $precioTotal = (float)
            $reserva->precio_total_viaje;

        if ($totalPagado <= 0) {
            $estadoPago =
                Reserva::PAGO_PENDIENTE;
        } elseif (
            $precioTotal > 0 &&
            $totalPagado >= $precioTotal
        ) {
            $estadoPago =
               Reserva::PAGO_COMPLETO;
        } else {
            $estadoPago =
                Reserva::PAGO_PARCIAL;
        }

        $reserva->estado_pago =
            $estadoPago;

        if (!$reserva->estaCancelada()) {
            $reserva->estado =
                $estadoPago === Reserva::PAGO_COMPLETO
                    ? Reserva::ESTADO_CONFIRMADA
                    : Reserva::ESTADO_PENDIENTE;
        }

        $reserva->save();
    }

    public function actualizarPago(
        int $pagoId,
        array $datos
    ): void {
        DB::transaction(function () use (
            $pagoId,
            $datos
        ) {
            $pago = Pago::query()
                ->lockForUpdate()
                ->findOrFail($pagoId);

            if ($pago->estaAnulado()) {
                throw new InvalidArgumentException(
                    'Los pagos anulados no se pueden editar.'
                );
            }

            $reserva = Reserva::query()
                ->with([
                    'grupo.clientes',
                    'grupo.responsablePago',
                ])
                ->lockForUpdate()
                ->findOrFail($pago->reserva_id);

            if ($reserva->estaCancelada()) {
                throw new InvalidArgumentException(
                    'No se pueden editar pagos de una reserva cancelada.'
                );
            }

            $monto = round(
                (float) (
                    $datos['monto_depositado'] ?? 0
                ),
                2
            );

            if ($monto <= 0) {
                throw new InvalidArgumentException(
                    'El monto debe ser mayor que cero.'
                );
            }

            $metodo = strtolower(
                trim(
                    $datos['metodo_pago'] ??
                    $pago->metodo_pago
                )
            );

            $metodosPermitidos = [
                Pago::METODO_EFECTIVO,
                Pago::METODO_TRANSFERENCIA,
                Pago::METODO_TARJETA,
                Pago::METODO_OTRO,
            ];

            if (!in_array(
                $metodo,
                $metodosPermitidos,
                true
            )) {
                throw new InvalidArgumentException(
                    'El método de pago seleccionado no es válido.'
                );
            }

            if (
                $reserva->esGrupal() &&
                $reserva->grupo?->esIndependiente()
            ) {
                $integrante = $reserva
                    ->grupo
                    ->clientes
                    ->firstWhere(
                        'id',
                        $pago->cliente_id
                    );

                if (!$integrante) {
                    throw new InvalidArgumentException(
                        'El cliente del pago no pertenece al grupo.'
                    );
                }

                $montoAsignado = (float) (
                    $integrante->pivot
                        ->monto_asignado ?? 0
                );

                $otrosPagos = (float) Pago::query()
                    ->registrados()
                    ->where(
                        'reserva_id',
                        $reserva->id
                    )
                    ->where(
                        'cliente_id',
                        $pago->cliente_id
                    )
                    ->where(
                        'id',
                        '!=',
                        $pago->id
                    )
                    ->sum('monto_depositado');

                $maximoPermitido = max(
                    0,
                    $montoAsignado -
                    $otrosPagos
                );
            } else {
                $otrosPagos = (float) Pago::query()
                    ->registrados()
                    ->where(
                        'reserva_id',
                        $reserva->id
                    )
                    ->where(
                        'id',
                        '!=',
                        $pago->id
                    )
                    ->sum('monto_depositado');

                $maximoPermitido = max(
                    0,
                    (float) $reserva
                        ->precio_total_viaje -
                    $otrosPagos
                );
            }

            if ($monto > $maximoPermitido) {
                throw new InvalidArgumentException(
                    'El pago no puede superar el valor disponible de ' .
                    number_format(
                        $maximoPermitido,
                        2,
                        '.',
                        ''
                    ) .
                    ' ' .
                    ($reserva->moneda ?: 'USD') .
                    '.'
                );
            }

            $referencia = !empty(
                $datos['referencia']
            )
                ? trim($datos['referencia'])
                : null;

            if (
                in_array(
                    $metodo,
                    [
                        Pago::METODO_TRANSFERENCIA,
                        Pago::METODO_TARJETA,
                    ],
                    true
                ) &&
                !$referencia
            ) {
                throw new InvalidArgumentException(
                    'Ingresa el comprobante o referencia del pago.'
                );
            }

            $pago->update([
                'monto_depositado' =>
                    $monto,
                'metodo_pago' =>
                    $metodo,
                'referencia' =>
                    $referencia,
            ]);

            $this->sincronizarEstadoPagoReserva(
                (int) $reserva->id
            );
        });
    }

    public function anularPago(
        int $pagoId,
        string $motivo,
        int $usuarioId
    ): void {
        DB::transaction(function () use (
            $pagoId,
            $motivo,
            $usuarioId
        ) {
            $pago = Pago::query()
                ->lockForUpdate()
                ->findOrFail($pagoId);

            if ($pago->estaAnulado()) {
                throw new InvalidArgumentException(
                    'El pago ya se encuentra anulado.'
                );
            }

            $pago->update([
                'estado' =>
                    Pago::ESTADO_ANULADO,
                'motivo_anulacion' =>
                    trim($motivo),
                'fecha_anulacion' =>
                    now(),
                'anulado_por_user_id' =>
                    $usuarioId,
            ]);

            $this->sincronizarEstadoPagoReserva(
                (int) $pago->reserva_id
            );
        });
    }

}
