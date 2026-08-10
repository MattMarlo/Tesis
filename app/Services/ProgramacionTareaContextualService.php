<?php

namespace App\Services;

use App\Models\Reserva;
use App\Models\TareaOperacionViaje;
use Carbon\Carbon;

class ProgramacionTareaContextualService
{
    public function resolver(
        TareaOperacionViaje $tarea,
        Reserva $reserva
    ): array {
        $fechaBase = $reserva->fecha_viaje
            ?? $reserva->destino?->fecha_salida;

        $fechaActividad = $fechaBase
            ? Carbon::parse($fechaBase)->startOfDay()->addDays(
                max(0, (int) $tarea->dia - 1)
            )
            : null;

        $inicio = $this->combinar(
            $fechaActividad,
            $tarea->hora_inicio,
            '00:00'
        );

        $fin = $this->combinar(
            $fechaActividad,
            $tarea->hora_fin
        );

        if ($inicio && $fin && $fin->lessThanOrEqualTo($inicio)) {
            $fin->addDay();
        }

        [$origen, $destino] = $this->separarRuta(
            $tarea->ubicacion
        );

        return [
            'fecha' => $fechaActividad,
            'inicio' => $inicio,
            'fin' => $fin,
            'inicio_input' => $inicio?->format('Y-m-d\TH:i'),
            'fin_input' => $fin?->format('Y-m-d\TH:i'),
            'origen' => $origen,
            'destino' => $destino,
            'ruta' => $destino
                ? $origen.' - '.$destino
                : ($tarea->ubicacion ?: null),
        ];
    }

    private function combinar(
        ?Carbon $fecha,
        mixed $hora,
        ?string $horaPredeterminada = null
    ): ?Carbon {
        if (!$fecha || (!$hora && !$horaPredeterminada)) {
            return null;
        }

        $horaFinal = substr(
            (string) ($hora ?: $horaPredeterminada),
            0,
            5
        );

        return $fecha->copy()->setTimeFromTimeString($horaFinal);
    }

    private function separarRuta(?string $ubicacion): array
    {
        $ubicacion = trim((string) $ubicacion);

        if ($ubicacion === '') {
            return [null, null];
        }

        $partes = preg_split(
            '/\s+(?:-|–|—|→|hacia)\s+/iu',
            $ubicacion,
            2
        );

        if (count($partes) !== 2) {
            return [$ubicacion, null];
        }

        return [trim($partes[0]), trim($partes[1])];
    }
}
