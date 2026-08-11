<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SolicitudAsesorWhatsApp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SolicitudAsesorWhatsAppController extends Controller
{
    public function store(
        Request $request
    ): JsonResponse {
        if (! $this->estaAutorizado($request)) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado.',
            ], 401);
        }

        $datos = $request->validate([
            'nombre' => [
                'nullable',
                'string',
                'max:150',
            ],

            'telefono' => [
                'required',
                'string',
                'max:30',
            ],

            'motivo' => [
                'required',
                'string',
                'min:10',
                'max:2000',
            ],

            'mensaje_id' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $mensajeId =
            $datos['mensaje_id'] ?? null;

        if ($mensajeId) {
            $existente =
                SolicitudAsesorWhatsApp::query()
                    ->where(
                        'mensaje_id',
                        $mensajeId
                    )
                    ->first();

            if ($existente) {
                return response()->json([
                    'success' => true,
                    'creado' => false,
                    'solicitud_id' =>
                        $existente->id,
                    'estado' =>
                        $existente->estado,
                ]);
            }
        }

        $solicitud =
            SolicitudAsesorWhatsApp::create([
                'nombre' =>
                    $datos['nombre'] ?? null,

                'telefono' =>
                    $datos['telefono'],

                'motivo' =>
                    $datos['motivo'],

                'estado' =>
                    SolicitudAsesorWhatsApp
                        ::ESTADO_PENDIENTE,

                'mensaje_id' =>
                    $mensajeId,
            ]);

        return response()->json([
            'success' => true,
            'creado' => true,
            'solicitud_id' =>
                $solicitud->id,
            'estado' =>
                $solicitud->estado,
            'message' =>
                'La solicitud fue registrada correctamente.',
        ], 201);
    }

    private function estaAutorizado(
        Request $request
    ): bool {
        $claveEsperada = (string) config(
            'services.n8n.whatsapp_api_secret'
        );

        $claveRecibida = (string)
            $request->header(
                'X-N8N-API-Key'
            );

        return $claveEsperada !== ''
            && $claveRecibida !== ''
            && hash_equals(
                $claveEsperada,
                $claveRecibida
            );
    }
}