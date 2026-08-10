<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConsentimientoDato;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WhatsAppConsentimientoController extends Controller
{
    public function estado(Request $request): JsonResponse
    {
        $this->autorizarN8n($request);

        $datos = $request->validate([
            'telefono' => [
                'required',
                'string',
                'regex:/^[0-9]{8,20}$/',
            ],
        ]);

        $consentimiento = ConsentimientoDato::query()
            ->where('telefono', $datos['telefono'])
            ->where('canal', 'whatsapp')
            ->latest('fecha_evento')
            ->latest('id')
            ->first();

        return response()->json([
            'success' => true,
            'tiene_registro' => $consentimiento !== null,
            'tiene_consentimiento' => $consentimiento?->estado === 'aceptado',
            'estado' => $consentimiento?->estado,
            'version_politica' => $consentimiento?->version_politica,
            'fecha_evento' => $consentimiento?->fecha_evento?->toISOString(),
        ]);
    }

    public function guardar(Request $request): JsonResponse
    {
        $this->autorizarN8n($request);

        $datos = $request->validate([
            'telefono' => [
                'required',
                'string',
                'regex:/^[0-9]{8,20}$/',
            ],
            'estado' => [
                'required',
                Rule::in([
                    'aceptado',
                    'rechazado',
                    'revocado',
                ]),
            ],
            'mensaje_id' => [
                'required',
                'string',
                'max:255',
            ],
            'evidencia' => [
                'nullable',
                'array',
            ],
        ]);

        $consentimiento = ConsentimientoDato::firstOrCreate(
            [
                'mensaje_id' => $datos['mensaje_id'],
            ],
            [
                'telefono' => $datos['telefono'],
                'canal' => 'whatsapp',
                'estado' => $datos['estado'],
                'version_politica' => '2026-08-08-v1',
                'politica_url' =>
                    'https://passiontravelviajes.de/politica-de-privacidad',
                'fecha_evento' => now(),
                'evidencia' => $datos['evidencia'] ?? null,
            ]
        );

        return response()->json([
            'success' => true,
            'creado' => $consentimiento->wasRecentlyCreated,
            'estado' => $consentimiento->estado,
            'fecha_evento' => $consentimiento->fecha_evento?->toISOString(),
        ], $consentimiento->wasRecentlyCreated ? 201 : 200);
    }

    private function autorizarN8n(Request $request): void
    {
        $claveEsperada = (string) config(
            'services.n8n.whatsapp_api_secret'
        );

        $claveRecibida = (string) $request->header(
            'X-N8N-API-Key'
        );

        abort_if(
            $claveEsperada === ''
            || ! hash_equals($claveEsperada, $claveRecibida),
            401,
            'No autorizado.'
        );
    }
}