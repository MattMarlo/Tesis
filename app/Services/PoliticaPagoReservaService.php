<?php

namespace App\Services;

use App\Models\Reserva;
use App\Models\ReservaRiesgo;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PoliticaPagoReservaService
{
    public function registrarAceptacion(
        Reserva $reserva,
        array $datos
    ): Reserva {
        $reserva = $reserva->fresh();
        $diasSaldo = max(
            0,
            (int) config('reservas.dias_antes_saldo_final', 30)
        );
        $diasGracia = max(
            0,
            (int) config('reservas.dias_gracia_riesgo', 3)
        );

        $texto = sprintf(
            'El anticipo de %s %s confirma el cupo una vez conciliado. ' .
            'El saldo total vence el %s. Vencido el plazo, la reserva ' .
            'entra en riesgo y dispone de hasta %d días de gracia, sin ' .
            'superar el plazo del proveedor. En una cancelación, los ' .
            'valores abonados se liquidan contra penalidades y costos de ' .
            'proveedores debidamente documentados; el excedente se devuelve ' .
            'al pagador original. Para viajes reservados dentro de los %d ' .
            'días previos se exige el pago completo.',
            number_format((float) $reserva->monto_anticipo, 2, '.', ''),
            $reserva->moneda ?: 'USD',
            $reserva->fecha_vencimiento_saldo?->format('d/m/Y') ?: 'sin fecha',
            $diasGracia,
            $diasSaldo
        );

        $reserva->forceFill([
            'version_politica_pago' =>
                config('reservas.version_politica', '2026-08-05-v1'),
            'politica_pago_aceptada' => $texto,
            'politica_aceptada_at' => now(),
            'canal_aceptacion_politica' =>
                $datos['canal_aceptacion_politica'],
            'referencia_aceptacion_politica' => trim(
                $datos['referencia_aceptacion_politica']
            ),
        ])->save();

        return $reserva;
    }

    public function inicializar(
        Reserva $reserva,
        bool $forzar = false
    ): Reserva {
        if ($reserva->monto_anticipo !== null && !$forzar) {
            return $this->sincronizar($reserva);
        }

        $fechaReserva = Carbon::parse(
            $reserva->fecha_reserva ?? $reserva->created_at ?? now()
        )->startOfDay();
        $fechaViaje = Carbon::parse($reserva->fecha_viaje)->startOfDay();
        $diasSaldo = max(
            0,
            (int) config('reservas.dias_antes_saldo_final', 30)
        );
        $vencimientoSaldo = $fechaViaje->copy()->subDays($diasSaldo);
        $porcentaje = max(
            1,
            min(
                100,
                (float) config('reservas.porcentaje_anticipo', 30)
            )
        );

        /* Una venta dentro de D-30 solo se confirma con pago completo. */
        $montoAnticipo = $fechaReserva->gte($vencimientoSaldo)
            ? (float) $reserva->precio_total_viaje
            : round(
                (float) $reserva->precio_total_viaje *
                ($porcentaje / 100),
                2
            );

        $limiteAnticipo = $fechaReserva->copy()
            ->addDays(max(
                0,
                (int) config('reservas.dias_para_pagar_anticipo', 3)
            ))
            ->endOfDay();

        if ($vencimientoSaldo->lte($limiteAnticipo)) {
            $limiteAnticipo = $fechaReserva->gte($vencimientoSaldo)
                ? $fechaReserva->copy()->endOfDay()
                : $vencimientoSaldo->copy()->endOfDay();
        }

        $reserva->forceFill([
            'porcentaje_anticipo' => $porcentaje,
            'monto_anticipo' => $montoAnticipo,
            'fecha_limite_anticipo' => $limiteAnticipo,
            'fecha_vencimiento_saldo' => $vencimientoSaldo,
        ])->save();

        return $this->sincronizar($reserva->fresh());
    }

    public function sincronizar(Reserva|int $reserva): Reserva
    {
        $reserva = $reserva instanceof Reserva
            ? $reserva->fresh(['pagos', 'devoluciones'])
            : Reserva::query()
                ->with(['pagos', 'devoluciones'])
                ->findOrFail($reserva);

        if ($reserva->monto_anticipo === null) {
            return $this->inicializar($reserva);
        }

        if ($reserva->estaCancelada()) {
            $pagado = round($reserva->total_pagado, 2);
            $total = round((float) $reserva->precio_total_viaje, 2);
            $estadoPago = match (true) {
                $pagado <= 0 => Reserva::PAGO_PENDIENTE,
                $total > 0 && $pagado >= $total => Reserva::PAGO_COMPLETO,
                default => Reserva::PAGO_PARCIAL,
            };

            $reserva->forceFill([
                'estado_cobranza' => Reserva::COBRANZA_CANCELADA,
                'estado_pago' => $estadoPago,
            ])->save();

            return $reserva;
        }

        $pagado = round($reserva->total_pagado, 2);
        $total = round((float) $reserva->precio_total_viaje, 2);
        $anticipo = round((float) $reserva->monto_anticipo, 2);
        $ahora = now();

        if ($pagado <= 0) {
            $estadoPago = Reserva::PAGO_PENDIENTE;
        } elseif ($total > 0 && $pagado >= $total) {
            $estadoPago = Reserva::PAGO_COMPLETO;
        } else {
            $estadoPago = Reserva::PAGO_PARCIAL;
        }

        $anticipoCompleto = $pagado >= $anticipo && $anticipo > 0;
        $saldoCompleto = $total > 0 && $pagado >= $total;
        $tipoRiesgo = null;

        if ($saldoCompleto) {
            $estadoCobranza = Reserva::COBRANZA_PAGADA;
        } elseif (!$anticipoCompleto) {
            $vencio = $reserva->fecha_limite_anticipo &&
                $ahora->gt($reserva->fecha_limite_anticipo);
            $estadoCobranza = $vencio
                ? Reserva::COBRANZA_EN_RIESGO
                : Reserva::COBRANZA_PENDIENTE_ANTICIPO;
            $tipoRiesgo = $vencio
                ? ReservaRiesgo::TIPO_ANTICIPO
                : null;
        } else {
            $vencio = $reserva->fecha_vencimiento_saldo &&
                $ahora->startOfDay()->gt(
                    $reserva->fecha_vencimiento_saldo->copy()->startOfDay()
                );
            $estadoCobranza = $vencio
                ? Reserva::COBRANZA_EN_RIESGO
                : Reserva::COBRANZA_AL_DIA;
            $tipoRiesgo = $vencio
                ? ReservaRiesgo::TIPO_SALDO
                : null;
        }

        DB::transaction(function () use (
            $reserva,
            $estadoPago,
            $estadoCobranza,
            $anticipoCompleto,
            $saldoCompleto,
            $tipoRiesgo,
            $pagado,
            $total,
            $anticipo
        ) {
            $cambios = [
                'estado_pago' => $estadoPago,
                'estado_cobranza' => $estadoCobranza,
                'estado' => $anticipoCompleto
                    ? Reserva::ESTADO_CONFIRMADA
                    : Reserva::ESTADO_PENDIENTE,
            ];

            if ($anticipoCompleto && !$reserva->anticipo_completado_at) {
                $cambios['anticipo_completado_at'] = now();
            }

            if ($saldoCompleto && !$reserva->saldo_completado_at) {
                $cambios['saldo_completado_at'] = now();
            }

            $reserva->forceFill($cambios)->save();

            if ($tipoRiesgo) {
                $this->abrirRiesgo(
                    $reserva,
                    $tipoRiesgo,
                    $tipoRiesgo === ReservaRiesgo::TIPO_ANTICIPO
                        ? max(0, $anticipo - $pagado)
                        : max(0, $total - $pagado)
                );
            } else {
                $this->regularizarRiesgos($reserva);
            }
        });

        return $reserva->fresh(['riesgoActivo']);
    }

    private function abrirRiesgo(
        Reserva $reserva,
        string $tipo,
        float $saldo
    ): void {
        $riesgo = ReservaRiesgo::query()
            ->where('reserva_id', $reserva->id)
            ->whereIn('estado', [
                ReservaRiesgo::ESTADO_ACTIVA,
                ReservaRiesgo::ESTADO_REVISION_CANCELACION,
            ])
            ->latest('id')
            ->first();

        if ($riesgo) {
            if ($riesgo->tipo !== $tipo) {
                $riesgo->update([
                    'tipo' => $tipo,
                    'saldo_al_ingresar' => $saldo,
                ]);
            }

            return;
        }

        $fechaBase = $tipo === ReservaRiesgo::TIPO_ANTICIPO
            ? $reserva->fecha_limite_anticipo->copy()
            : $reserva->fecha_vencimiento_saldo
                ->copy()
                ->endOfDay();

        ReservaRiesgo::create([
            'reserva_id' => $reserva->id,
            'tipo' => $tipo,
            'estado' => ReservaRiesgo::ESTADO_ACTIVA,
            'saldo_al_ingresar' => $saldo,
            'fecha_ingreso' => $fechaBase,
            'fecha_limite_regularizacion' => $fechaBase
                ->copy()
                ->addDays(max(
                    0,
                    (int) config('reservas.dias_gracia_riesgo', 3)
                )),
        ]);
    }

    private function regularizarRiesgos(Reserva $reserva): void
    {
        ReservaRiesgo::query()
            ->where('reserva_id', $reserva->id)
            ->whereIn('estado', [
                ReservaRiesgo::ESTADO_ACTIVA,
                ReservaRiesgo::ESTADO_REVISION_CANCELACION,
            ])
            ->update([
                'estado' => ReservaRiesgo::ESTADO_REGULARIZADA,
                'fecha_resolucion' => now(),
                'observaciones' => 'Regularizada mediante pago.',
                'updated_at' => now(),
            ]);
    }
}
