<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Destino;
use App\Models\PreReserva;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SolicitudPrerreservaWhatsAppController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $this->autorizarN8n($request);

        $data = $request->validate([
            'destino_id' => [
                'required',
                'integer',
                'exists:destinos,id',
            ],
            'referencia_externa' => [
                'required',
                'string',
                'max:150',
            ],
            'nombre_completo' => [
                'required',
                'string',
                'min:5',
                'max:150',
            ],
            'cedula' => [
                'required',
                'digits:10',
            ],
            'correo' => [
                'required',
                'email',
                'max:150',
            ],
            'telefono' => [
                'required',
                'string',
                'max:20',
            ],
            'tipo_reserva' => [
                'required',
                Rule::in(['individual', 'grupal']),
            ],
            'cantidad_personas' => [
                'required',
                'integer',
                'min:1',
                'max:100',
            ],
        ]);

        if (! $this->cedulaEcuatorianaValida(
            $data['cedula']
        )) {
            throw ValidationException::withMessages([
                'cedula' => [
                    'La cédula ecuatoriana no es válida.',
                ],
            ]);
        }

        if (
            $data['tipo_reserva'] === 'individual'
            && $data['cantidad_personas'] !== 1
        ) {
            throw ValidationException::withMessages([
                'cantidad_personas' => [
                    'Una solicitud individual debe tener una persona.',
                ],
            ]);
        }

        if (
            $data['tipo_reserva'] === 'grupal'
            && $data['cantidad_personas'] < 2
        ) {
            throw ValidationException::withMessages([
                'cantidad_personas' => [
                    'Una solicitud grupal requiere al menos dos personas.',
                ],
            ]);
        }
        
        $destino = Destino::findOrFail(
            $data['destino_id']
        );

        $preReserva = PreReserva::firstOrCreate(
            [
                'referencia_externa' =>
                    $data['referencia_externa'],
            ],
            [
                'cliente_nombre' => trim(
                    $data['nombre_completo']
                ),

                'email' => mb_strtolower(
                    trim($data['correo'])
                ),

                'destino' =>
                    $destino->nombre_paquete,

                'destino_id' => $destino->id,

                'telefono' => trim(
                    $data['telefono']
                ),

                'cedula' => $data['cedula'],

                'fecha_viaje' => null,

                'cantidad_personas' =>
                    $data['cantidad_personas'],

                'fecha_reserva' => now(),

                'origen' =>
                    PreReserva::ORIGEN_WHATSAPP,

                'telegram_chat_id' => null,

                'estado' =>
                    PreReserva::ESTADO_PENDIENTE,

                'tipo_reserva' =>
                    $data['tipo_reserva'],

                'user_id' => null,
            ]
        );

        return response()->json([
            'success' => true,

            'duplicada' =>
                ! $preReserva->wasRecentlyCreated,

            // Conservamos este nombre para no cambiar n8n.
            'solicitud_id' => $preReserva->id,

            'pre_reserva_id' => $preReserva->id,

            'estado' => $preReserva->estado,

            'message' =>
                $preReserva->wasRecentlyCreated
                    ? 'Prerreserva registrada correctamente.'
                    : 'La prerreserva ya había sido registrada.',
        ], $preReserva->wasRecentlyCreated ? 201 : 200);

        return response()->json([
            'success' => true,
            'duplicada' => ! $solicitud->wasRecentlyCreated,
            'solicitud_id' => $solicitud->id,
            'estado' => $solicitud->estado,
            'message' => $solicitud->wasRecentlyCreated
                ? 'Solicitud de prerreserva registrada correctamente.'
                : 'La solicitud ya había sido registrada.',
        ], $solicitud->wasRecentlyCreated ? 201 : 200);
    }

    private function autorizarN8n(Request $request): void
    {
        $esperada = (string) config(
            'services.n8n.whatsapp_api_secret'
        );

        $recibida = (string) $request->header(
            'X-N8N-API-Key',
            ''
        );

        if (
            $esperada === ''
            || ! hash_equals($esperada, $recibida)
        ) {
            abort(401, 'No autorizado.');
        }
    }

    private function cedulaEcuatorianaValida(
        string $cedula
    ): bool {
        if (
            ! preg_match('/^\d{10}$/', $cedula)
            || (int) substr($cedula, 0, 2) < 1
            || (int) substr($cedula, 0, 2) > 24
            || (int) $cedula[2] > 5
        ) {
            return false;
        }

        $suma = 0;

        for ($i = 0; $i < 9; $i++) {
            $valor = (int) $cedula[$i]
                * ($i % 2 === 0 ? 2 : 1);

            $suma += $valor > 9
                ? $valor - 9
                : $valor;
        }

        return (
            (10 - ($suma % 10)) % 10
        ) === (int) $cedula[9];
    }
}