<?php

namespace App\Services;

use App\Models\BoletoVuelo;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class NotificacionBoletoN8nService
{
    public function enviar(
        BoletoVuelo $boleto
    ): void {
        $webhookUrl = config(
            'services.n8n.flight_ticket_notification_url'
        );

        if (!$webhookUrl) {
            return;
        }

        try {
            $boleto->loadMissing([
                'cliente',
                'viajeroReserva.cliente',
                'vuelo.operacion.reserva.cliente',
                'vuelo.operacion.reserva.destino',
            ]);

            $vuelo = $boleto->vuelo;
            $reserva = $vuelo?->operacion?->reserva;
            $persona = $boleto->personaViajera();
            $clienteContacto = $boleto->cliente
                ?? $boleto->viajeroReserva?->cliente
                ?? $reserva?->cliente;

            $respuesta = Http::acceptJson()
                ->timeout(10)
                ->post(
                    $webhookUrl,
                    [
                        'event' =>
                            'boleto.avion.emitido',
                        'data' => [
                            'boleto_id' => $boleto->id,
                            'reserva_id' => $reserva?->id,
                            'codigo_reserva' =>
                                $reserva?->codigo_reserva,
                            'cliente' => $persona
                                ? trim(
                                    ($persona->nombres ?? '').' '.
                                    ($persona->apellidos ?? '')
                                )
                                : null,
                            'email' =>
                                $clienteContacto?->email,
                            'telefono' =>
                                $clienteContacto?->telefono,
                            'destino_paquete' =>
                                $reserva?->destino?->nombre_paquete,
                            'numero_boleto' =>
                                $boleto->numero_boleto,
                            'asiento' => $boleto->asiento,
                            'clase' => $boleto->clase,
                            'estado_emision' =>
                                $boleto->estado_emision,
                            'aerolinea' =>
                                $vuelo?->aerolinea,
                            'numero_vuelo' =>
                                $vuelo?->numero_vuelo,
                            'tipo_tramo' =>
                                $vuelo?->tipo_tramo,
                            'ciudad_origen' =>
                                $vuelo?->ciudad_origen,
                            'aeropuerto_origen' =>
                                $vuelo?->aeropuerto_origen,
                            'ciudad_destino' =>
                                $vuelo?->ciudad_destino,
                            'aeropuerto_destino' =>
                                $vuelo?->aeropuerto_destino,
                            'fecha_hora_salida' =>
                                $vuelo?->fecha_hora_salida
                                    ?->toIso8601String(),
                            'fecha_hora_llegada' =>
                                $vuelo?->fecha_hora_llegada
                                    ?->toIso8601String(),
                            'localizador_reserva' =>
                                $vuelo?->localizador_reserva,
                            'equipaje_incluido' =>
                                $vuelo?->equipaje_incluido,
                            'archivo_boleto_url' =>
                                $boleto->archivo_boleto
                                    ? url(
                                        Storage::disk('public')
                                            ->url(
                                                $boleto->archivo_boleto
                                            )
                                    )
                                    : null,
                        ],
                    ]
                );

            if ($respuesta->failed()) {
                Log::warning(
                    'n8n rechazó la notificación del boleto aéreo.',
                    [
                        'boleto_id' => $boleto->id,
                        'estado_http' =>
                            $respuesta->status(),
                    ]
                );
            }
        } catch (\Throwable $error) {
            Log::error(
                'No se pudo notificar el boleto aéreo a n8n.',
                [
                    'boleto_id' => $boleto->id,
                    'mensaje' => $error->getMessage(),
                ]
            );
        }
    }
}
