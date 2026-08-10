<?php

namespace App\Services;

use App\Models\Reserva;
use App\Models\ReservaRiesgo;

class GestionRiesgoReservaService
{
    public function __construct(
        private PoliticaPagoReservaService $politica,
        private CancelacionReservaService $cancelacion
    ) {}

    public function evaluarTodas(): array
    {
        $resultado = [
            'evaluadas' => 0,
            'en_riesgo' => 0,
            'canceladas' => 0,
            'en_revision' => 0,
            'omitidas_sin_aceptacion' => Reserva::query()
                ->where('estado', '!=', Reserva::ESTADO_CANCELADA)
                ->whereNull('politica_aceptada_at')
                ->count(),
        ];

        Reserva::query()
            ->where('estado', '!=', Reserva::ESTADO_CANCELADA)
            /*
             * Las reservas anteriores a esta política no se cancelan de
             * forma automática sin evidencia de que el cliente la aceptó.
             */
            ->whereNotNull('politica_aceptada_at')
            ->orderBy('id')
            ->chunkById(100, function ($reservas) use (&$resultado) {
                foreach ($reservas as $reserva) {
                    $resultado['evaluadas']++;
                    $reserva = $this->politica->inicializar($reserva);

                    if (!in_array($reserva->estado_cobranza, [
                        Reserva::COBRANZA_EN_RIESGO,
                        Reserva::COBRANZA_REVISION_CANCELACION,
                    ], true)) {
                        continue;
                    }

                    $resultado['en_riesgo']++;
                    $riesgo = ReservaRiesgo::query()
                        ->where('reserva_id', $reserva->id)
                        ->whereIn('estado', [
                            ReservaRiesgo::ESTADO_ACTIVA,
                            ReservaRiesgo::ESTADO_REVISION_CANCELACION,
                        ])
                        ->latest('id')
                        ->first();

                    if (
                        !$riesgo ||
                        now()->lte($riesgo->fecha_limite_regularizacion)
                    ) {
                        continue;
                    }

                    $cancelada = $this->cancelacion->cancelar(
                        $reserva,
                        [
                            'motivo_cancelacion' =>
                                'Incumplimiento del plazo de pago luego del período de gracia.',
                            'tipo_cancelacion' => 'incumplimiento_pago',
                        ],
                        null,
                        true
                    );

                    $resultado[$cancelada
                        ? 'canceladas'
                        : 'en_revision']++;
                }
            });

        return $resultado;
    }
}
