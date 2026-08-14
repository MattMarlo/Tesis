<?php

namespace App\Services;

use App\Models\Destino;
use App\Models\PreReserva;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PrerreservaPublicaService
{
    private const ORIGEN_WEB = 'landing_page';

    public function __construct(
        private CupoReservaService $cupos
    ) {}

    /**
     * @return array{prerreserva: PreReserva, duplicada: bool}
     */
    public function registrar(
        Destino $destino,
        array $datos
    ): array {
        return DB::transaction(function () use (
            $destino,
            $datos
        ): array {
            $destino = Destino::query()
                ->lockForUpdate()
                ->findOrFail($destino->id);

            $this->validarPaquete($destino, $datos);

            $existente = PreReserva::query()
                ->where('destino_id', $destino->id)
                ->where('cedula', $datos['cedula'])
                ->whereIn('estado', [
                    PreReserva::ESTADO_PENDIENTE,
                    PreReserva::ESTADO_CONTACTADO,
                    PreReserva::ESTADO_CONVERTIDA,
                ])
                ->latest('id')
                ->first();

            if ($existente) {
                return [
                    'prerreserva' => $existente,
                    'duplicada' => true,
                ];
            }

            $prerreserva = PreReserva::create([
                'cliente_nombre' => $datos['nombre_completo'],
                'email' => $datos['correo'],
                'destino' => $destino->nombre_paquete,
                'destino_id' => $destino->id,
                'telefono' => $datos['telefono'],
                'cedula' => $datos['cedula'],
                'fecha_viaje' => $destino->fecha_salida,
                'cantidad_personas' => (int) $datos['cantidad_personas'],
                'fecha_reserva' => now(),
                'origen' => self::ORIGEN_WEB,
                'telegram_chat_id' => null,
                'referencia_externa' => 'WEB-'.Str::uuid(),
                'estado' => PreReserva::ESTADO_PENDIENTE,
                'tipo_reserva' => $datos['tipo_reserva'],
                'acepta_condiciones' => true,
                'confirmada_por_cliente_at' => now(),
                'user_id' => null,
            ]);

            return [
                'prerreserva' => $prerreserva,
                'duplicada' => false,
            ];
        });
    }

    private function validarPaquete(
        Destino $destino,
        array $datos
    ): void {
        if ($destino->estado_publicacion !== 'publicado') {
            throw ValidationException::withMessages([
                'destino' => 'Este paquete ya no está disponible para prerreservas.',
            ]);
        }

        if (
            $destino->fecha_salida
            && $destino->fecha_salida->lt(today())
        ) {
            throw ValidationException::withMessages([
                'destino' => 'La fecha de salida de este paquete ya pasó.',
            ]);
        }

        $cantidad = (int) $datos['cantidad_personas'];
        $disponibles = $this->cupos
            ->obtenerDisponibles($destino);

        if ($cantidad > $disponibles) {
            throw ValidationException::withMessages([
                'cantidad_personas' => "Solo quedan {$disponibles} cupos disponibles.",
            ]);
        }
    }
}
