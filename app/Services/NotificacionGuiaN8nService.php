<?php

namespace App\Services;

use App\Models\GuiaReserva;
use App\Models\TareaOperacionViaje;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificacionGuiaN8nService
{
    public function enviar(
        GuiaReserva $guia,
        ?TareaOperacionViaje $tarea = null
    ): void {
        $webhookUrl = config(
            'services.n8n.guide_assignment_notification_url'
        );

        if (! $webhookUrl) {
            return;
        }

        try {
            $guia->loadMissing([
                'operacion.reserva.cliente',
                'operacion.reserva.destino',
            ]);

            $reserva = $guia->operacion?->reserva;
            $cliente = $reserva?->cliente;

            $respuesta = Http::acceptJson()
                ->timeout(10)
                ->post(
                    $webhookUrl,
                    [
                        'event' => 'guia.viaje.asignado',
                        'data' => [
                            'guia_id' => $guia->id,
                            'reserva_id' => $reserva?->id,
                            'codigo_reserva' => $reserva?->codigo_reserva,
                            'cliente' => $cliente
                                ? $cliente->nombre_completo
                                : null,
                            'email' => $cliente?->email,
                            'telefono' => $cliente?->telefono,
                            'destino' => $reserva?->destino?->ciudad_destino,
                            'nombre_paquete' => $reserva?->destino?->nombre_paquete,
                            'nombre_guia' => $guia->nombre_completo,
                            'empresa_guia' => $guia->empresa,
                            'telefono_guia' => $guia->telefono,
                            'correo_guia' => $guia->correo,
                            'idiomas_guia' => $guia->idiomas,
                            'ciudad_servicio' => $guia->ciudad_servicio,
                            'fecha_inicio' => $guia->fecha_inicio?->toDateString(),
                            'fecha_fin' => $guia->fecha_fin?->toDateString(),
                            'punto_encuentro' => $guia->punto_encuentro,
                            'fecha_hora_encuentro' => $guia->fecha_hora_encuentro
                                ?->toIso8601String(),
                            'servicios_incluidos' => $guia->servicios_incluidos,
                            'estado_guia' => $guia->estado,
                            'tarea_id' => $tarea?->id,
                            'actividad' => $tarea?->nombre,
                            'dia_actividad' => $tarea?->dia,
                            'hora_inicio_actividad' => $tarea?->hora_inicio,
                            'hora_fin_actividad' => $tarea?->hora_fin,
                            'ubicacion_actividad' => $tarea?->ubicacion,
                        ],
                    ]
                );

            if ($respuesta->failed()) {
                Log::warning(
                    'n8n rechazó la notificación del guía asignado.',
                    [
                        'guia_id' => $guia->id,
                        'tarea_id' => $tarea?->id,
                        'estado_http' => $respuesta->status(),
                    ]
                );
            }
        } catch (\Throwable $error) {
            Log::error(
                'No se pudo notificar el guía asignado a n8n.',
                [
                    'guia_id' => $guia->id,
                    'tarea_id' => $tarea?->id,
                    'mensaje' => $error->getMessage(),
                ]
            );
        }
    }
}
