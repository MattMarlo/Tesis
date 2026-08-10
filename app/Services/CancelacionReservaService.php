<?php

namespace App\Services;

use App\Models\Reserva;
use App\Models\ReservaRiesgo;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CancelacionReservaService
{
    public function cancelar(
        Reserva $reserva,
        array $datos,
        ?int $usuarioId,
        bool $automatica = false
    ): bool {
        return DB::transaction(function () use (
            $reserva,
            $datos,
            $usuarioId,
            $automatica
        ) {
            /*
             * Bloqueamos la reserva durante la cancelación
             * para evitar procesos duplicados.
             */
            $reserva = Reserva::query()
                ->with([
                    'pagos',
                    'devoluciones',
                ])
                ->lockForUpdate()
                ->findOrFail($reserva->id);

            if ($reserva->estaCancelada()) {
                throw new InvalidArgumentException(
                    'La reserva ya se encuentra cancelada.'
                );
            }

            /*
             * Total pagado neto:
             * pagos registrados menos devoluciones procesadas.
             */
            $pagado = round(
                $reserva->total_pagado,
                2
            );

            $tipo =
                $datos['tipo_cancelacion'] ??
                'otro';

            /*
             * Una cancelación automática no puede inventar
             * gastos ni penalidades.
             *
             * Por eso inicialmente autoriza la devolución
             * completa de lo pagado.
             */
            $gastos = $automatica
                ? 0.0
                : round(
                    (float) (
                        $datos[
                            'gastos_no_reembolsables'
                        ] ?? 0
                    ),
                    2
                );

            $detalle = trim(
                (string) (
                    $datos[
                        'detalle_gastos_no_reembolsables'
                    ] ?? ''
                )
            );

            $evidencia = trim(
                (string) (
                    $datos[
                        'evidencia_cancelacion'
                    ] ?? ''
                )
            );

            /*
             * Cuando la cancelación es responsabilidad
             * de la agencia o proveedor, no se descuentan
             * penalidades al cliente.
             */
            if (
                in_array(
                    $tipo,
                    [
                        'agencia',
                        'proveedor',
                    ],
                    true
                )
            ) {
                $gastos = 0;
                $detalle = '';
            }

            /*
             * Los gastos nunca pueden ser negativos ni
             * superar el total pagado.
             */
            if (
                $gastos < 0 ||
                $gastos > $pagado
            ) {
                throw new InvalidArgumentException(
                    'Los gastos no reembolsables no pueden superar el valor neto pagado.'
                );
            }

            /*
             * Si se descuentan gastos, es obligatorio
             * explicar cuáles son.
             */
            if (
                $gastos > 0 &&
                mb_strlen($detalle) < 10
            ) {
                throw new InvalidArgumentException(
                    'Detalla las penalidades o servicios no reembolsables y conserva sus respaldos.'
                );
            }

            /*
             * Para cancelaciones por fuerza mayor se debe
             * registrar la evidencia revisada.
             */
            if (
                $tipo === 'fuerza_mayor' &&
                mb_strlen($evidencia) < 10
            ) {
                throw new InvalidArgumentException(
                    'En fuerza mayor registra la evidencia revisada (certificado, denuncia, cierre oficial u otro respaldo).'
                );
            }

            /*
             * Cálculo del reembolso autorizado.
             */
            $reembolsable = round(
                max(
                    0,
                    $pagado - $gastos
                ),
                2
            );

            $estadoReembolso = match (true) {
                $pagado <= 0 =>
                    Reserva::REEMBOLSO_NO_APLICA,

                $reembolsable <= 0 =>
                    Reserva::REEMBOLSO_SIN_REEMBOLSO,

                default =>
                    Reserva::REEMBOLSO_PENDIENTE,
            };

            /*
             * La reserva se cancela incluso cuando:
             *
             * - Tiene pagos parciales.
             * - Tiene una operación en preparación.
             * - Existen servicios preparados.
             *
             * Los pagos y reembolsos se conservan para
             * mantener la trazabilidad contable.
             */
            $reserva->forceFill([
                'estado' =>
                    Reserva::ESTADO_CANCELADA,

                'estado_cobranza' =>
                    Reserva::COBRANZA_CANCELADA,

                'motivo_cancelacion' =>
                    trim(
                        (string) (
                            $datos[
                                'motivo_cancelacion'
                            ] ??
                            'Cancelación de la reserva.'
                        )
                    ),

                'tipo_cancelacion' =>
                    $tipo,

                'cancelacion_automatica' =>
                    $automatica,

                'fecha_cancelacion' =>
                    now(),

                'cancelado_por_user_id' =>
                    $usuarioId,

                'estado_reembolso' =>
                    $estadoReembolso,

                'monto_pagado_al_cancelar' =>
                    $pagado,

                'gastos_no_reembolsables' =>
                    $gastos,

                'monto_reembolsable' =>
                    $reembolsable,

                'detalle_gastos_no_reembolsables' =>
                    $detalle ?: null,

                'evidencia_cancelacion' =>
                    $evidencia ?: null,
            ])->save();

            /*
             * Cerramos todos los registros activos
             * del apartado de reservas en riesgo.
             */
            ReservaRiesgo::query()
                ->where(
                    'reserva_id',
                    $reserva->id
                )
                ->whereIn(
                    'estado',
                    [
                        ReservaRiesgo::ESTADO_ACTIVA,
                        ReservaRiesgo::
                            ESTADO_REVISION_CANCELACION,
                    ]
                )
                ->update([
                    'estado' =>
                        ReservaRiesgo::ESTADO_CANCELADA,

                    'fecha_resolucion' =>
                        now(),

                    'resuelto_por_user_id' =>
                        $usuarioId,

                    'observaciones' =>
                        $automatica
                            ? 'Cancelada automáticamente al vencer los siete días de gracia. Los valores abonados quedan pendientes de devolución y no se aplican descuentos sin documentación.'
                            : 'Cancelación revisada y procesada por un usuario.',

                    'updated_at' =>
                        now(),
                ]);

            return true;
        });
    }

    public function liquidarCancelacion(
        Reserva $reserva,
        array $datos,
        int $usuarioId
    ): void {
        if ($reserva->estaCancelada()) {
            throw new InvalidArgumentException(
                'La reserva ya fue cancelada; ajusta la liquidación desde devoluciones.'
            );
        }

        $this->cancelar(
            $reserva,
            $datos,
            $usuarioId
        );
    }
}