<?php

namespace App\Services;

use App\Models\Devolucion;
use App\Models\Pago;
use App\Models\Reserva;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DevolucionService
{
    public function __construct(
        private PagoService $pagoService
    ) {
    }

    public function registrar(array $datos): Devolucion
    {
        return DB::transaction(function () use ($datos) {
            $pago = Pago::query()
                ->lockForUpdate()
                ->findOrFail($datos['pago_id']);

            if ($pago->estaAnulado()) {
                throw new InvalidArgumentException(
                    'No se puede devolver un pago anulado.'
                );
            }

            $reserva = Reserva::query()
                ->lockForUpdate()
                ->findOrFail($pago->reserva_id);

            $monto = round(
                (float) ($datos['monto'] ?? 0),
                2
            );

            /*
             * Calculamos cuánto dinero de este pago
             * ya fue devuelto.
             */
            $devueltoPago = round(
                (float) Devolucion::query()
                    ->procesadas()
                    ->where('pago_id', $pago->id)
                    ->sum('monto'),
                2
            );

            $disponiblePago = round(
                max(
                    0,
                    (float) $pago->monto_depositado -
                    $devueltoPago
                ),
                2
            );

            if ($monto <= 0) {
                throw new InvalidArgumentException(
                    'El monto de la devolución debe ser mayor que cero.'
                );
            }

            if ($monto > $disponiblePago) {
                throw new InvalidArgumentException(
                    'La devolución no puede superar el saldo disponible del pago: ' .
                    number_format($disponiblePago, 2, '.', '') .
                    ' ' . ($reserva->moneda ?: 'USD') . '.'
                );
            }

            $tipo = $datos['tipo'] ?? 'otro';

            /*
             * Una devolución por cancelación solamente puede
             * realizarse después de cancelar y liquidar la reserva.
             */
            if (
                $tipo === 'cancelacion' &&
                !$reserva->estaCancelada()
            ) {
                throw new InvalidArgumentException(
                    'Primero debes cancelar y liquidar la reserva antes de procesar el reembolso.'
                );
            }

            /*
             * Cuando la reserva está cancelada, el total de todas
             * las devoluciones no puede superar el monto autorizado.
             */
            if ($reserva->estaCancelada()) {
                if ($reserva->monto_reembolsable === null) {
                    throw new InvalidArgumentException(
                        'La cancelación todavía no tiene un monto de reembolso autorizado.'
                    );
                }

                $devueltoReserva = round(
                    (float) Devolucion::query()
                        ->procesadas()
                        ->where('reserva_id', $reserva->id)
                        ->sum('monto'),
                    2
                );

                $autorizadoDisponible = round(
                    max(
                        0,
                        (float) $reserva->monto_reembolsable -
                        $devueltoReserva
                    ),
                    2
                );

                if ($monto > $autorizadoDisponible) {
                    throw new InvalidArgumentException(
                        'La devolución supera el monto autorizado disponible de ' .
                        number_format(
                            $autorizadoDisponible,
                            2,
                            '.',
                            ''
                        ) .
                        ' ' . ($reserva->moneda ?: 'USD') . '.'
                    );
                }
            }

            $devolucion = Devolucion::create([
                'pago_id' => $pago->id,
                'reserva_id' => $pago->reserva_id,
                'cliente_id' => $pago->cliente_id,
                'user_id' => $datos['user_id'],
                'monto' => $monto,
                'metodo' => $datos['metodo'],
                'referencia' => $datos['referencia'] ?? null,
                'tipo' => $tipo,
                'motivo' => trim(
                    (string) ($datos['motivo'] ?? '')
                ),
                'estado' => Devolucion::ESTADO_PROCESADA,
                'fecha_devolucion' => now(),
            ]);

            /*
             * Después de devolver dinero actualizamos:
             * 1. El neto pagado.
             * 2. El estado del reembolso.
             */
            $this->pagoService
                ->sincronizarEstadoPagoReserva(
                    (int) $pago->reserva_id
                );

            $this->sincronizarEstadoReembolso(
                $reserva
            );

            return $devolucion;
        });
    }

    public function anular(
        Devolucion $devolucion,
        string $motivo,
        int $usuarioId
    ): void {
        DB::transaction(function () use (
            $devolucion,
            $motivo,
            $usuarioId
        ) {
            $registro = Devolucion::query()
                ->lockForUpdate()
                ->findOrFail($devolucion->id);

            if ($registro->estaAnulada()) {
                throw new InvalidArgumentException(
                    'La devolución ya está anulada.'
                );
            }

            if (mb_strlen(trim($motivo)) < 10) {
                throw new InvalidArgumentException(
                    'El motivo de anulación debe tener al menos 10 caracteres.'
                );
            }

            $registro->update([
                'estado' => Devolucion::ESTADO_ANULADA,
                'motivo_anulacion' => trim($motivo),
                'fecha_anulacion' => now(),
                'anulada_por_user_id' => $usuarioId,
            ]);

            $this->pagoService
                ->sincronizarEstadoPagoReserva(
                    (int) $registro->reserva_id
                );

            $reserva = Reserva::query()
                ->findOrFail($registro->reserva_id);

            $this->sincronizarEstadoReembolso(
                $reserva
            );
        });
    }

    private function sincronizarEstadoReembolso(
        Reserva $reserva
    ): void {
        $reserva->refresh();

        /*
         * Los estados especiales de reembolso se utilizan
         * solamente cuando la reserva fue cancelada.
         */
        if (!$reserva->estaCancelada()) {
            return;
        }

        $pagadoAlCancelar = round(
            (float) ($reserva->monto_pagado_al_cancelar ?? 0),
            2
        );

        $autorizado = round(
            (float) ($reserva->monto_reembolsable ?? 0),
            2
        );

        $devuelto = round(
            (float) Devolucion::query()
                ->procesadas()
                ->where('reserva_id', $reserva->id)
                ->sum('monto'),
            2
        );

        $estado = match (true) {
            $pagadoAlCancelar <= 0 =>
                Reserva::REEMBOLSO_NO_APLICA,

            $autorizado <= 0 =>
                Reserva::REEMBOLSO_SIN_REEMBOLSO,

            $devuelto >= $autorizado =>
                Reserva::REEMBOLSO_COMPLETADO,

            $devuelto > 0 =>
                Reserva::REEMBOLSO_PARCIAL,

            default =>
                Reserva::REEMBOLSO_PENDIENTE,
        };

        $reserva->forceFill([
            'estado_reembolso' => $estado,
        ])->save();
    }
}