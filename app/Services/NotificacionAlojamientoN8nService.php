<?php

namespace App\Services;

use App\Models\AsignacionHabitacion;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificacionAlojamientoN8nService
{
    public function enviar(
        AsignacionHabitacion $asignacion
    ): void {
        $webhookUrl = config(
            'services.n8n.room_assignment_notification_url'
        );

        if (!$webhookUrl) {
            return;
        }

        try {
            $asignacion->loadMissing([
                'cliente',
                'viajeroReserva.cliente',
                'habitacion.alojamiento.operacion.reserva.cliente',
                'habitacion.alojamiento.operacion.reserva.destino',
            ]);

            $habitacion = $asignacion->habitacion;
            $alojamiento = $habitacion?->alojamiento;
            $reserva = $alojamiento
                ?->operacion
                ?->reserva;
            $persona = $asignacion->viajeroReserva
                ?? $asignacion->cliente;
            $clienteContacto = $asignacion->cliente
                ?? $asignacion->viajeroReserva?->cliente
                ?? $reserva?->cliente;

            $respuesta = Http::acceptJson()
                ->timeout(10)
                ->post(
                    $webhookUrl,
                    [
                        'event' =>
                            'habitacion.alojamiento.asignada',
                        'data' => [
                            'asignacion_id' =>
                                $asignacion->id,
                            'codigo_reserva' =>
                                $reserva?->codigo_reserva,
                            'destino' =>
                                $reserva?->destino
                                    ?->ciudad_destino,
                            'viajero' => $persona
                                ? trim(
                                    ($persona->nombres ?? '').' '.
                                    ($persona->apellidos ?? '')
                                )
                                : null,
                            'email' =>
                                $clienteContacto?->email,
                            'telefono' =>
                                $clienteContacto?->telefono,
                            'nombre_hotel' =>
                                $alojamiento?->nombre_hotel,
                            'ciudad' =>
                                $alojamiento?->ciudad,
                            'numero_habitacion' =>
                                $habitacion?->referencia,
                            'tipo_habitacion' =>
                                $habitacion?->tipo,
                            'capacidad_habitacion' =>
                                $habitacion?->capacidad,
                            'observaciones' =>
                                $habitacion?->observaciones,
                        ],
                    ]
                );

            if ($respuesta->failed()) {
                Log::warning(
                    'n8n rechazó la notificación de la habitación asignada.',
                    [
                        'asignacion_id' =>
                            $asignacion->id,
                        'estado_http' =>
                            $respuesta->status(),
                    ]
                );
            }
        } catch (\Throwable $error) {
            Log::error(
                'No se pudo notificar la habitación asignada a n8n.',
                [
                    'asignacion_id' =>
                        $asignacion->id,
                    'mensaje' =>
                        $error->getMessage(),
                ]
            );
        }
    }
}
