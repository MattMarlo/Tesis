<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SolicitudPrerreservaWhatsApp;
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

        $solicitud = SolicitudPrerreservaWhatsApp::firstOrCreate(
            [
                'referencia_externa' =>
                    $data['referencia_externa'],
            ],
            [
                'destino_id' => $data['destino_id'],
                'nombre_completo' =>
                    trim($data['nombre_completo']),
                'cedula' => $data['cedula'],
                'correo' => mb_strtolower(
                    trim($data['correo'])
                ),
                'telefono' => $data['telefono'],
                'tipo_reserva' => $data['tipo_reserva'],
                'cantidad_personas' =>
                    $data['cantidad_personas'],
                'estado' =>
                    SolicitudPrerreservaWhatsApp::ESTADO_PENDIENTE,
            ]
        );

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