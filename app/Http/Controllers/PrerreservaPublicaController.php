<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarPrerreservaPublicaRequest;
use App\Models\Destino;
use App\Services\PrerreservaPublicaService;
use Illuminate\Http\JsonResponse;

class PrerreservaPublicaController extends Controller
{
    public function store(
        GuardarPrerreservaPublicaRequest $request,
        Destino $destino,
        PrerreservaPublicaService $servicio
    ): JsonResponse {
        $resultado = $servicio->registrar(
            $destino,
            $request->validated()
        );

        $duplicada = $resultado['duplicada'];
        $prerreserva = $resultado['prerreserva'];

        return response()->json([
            'success' => true,
            'duplicada' => $duplicada,
            'pre_reserva_id' => $prerreserva->id,
            'estado' => $prerreserva->estado,
            'message' => $duplicada
                ? 'Ya tienes una prerreserva activa para este paquete. Nuestro equipo se pondrá en contacto contigo.'
                : 'Tu prerreserva fue registrada correctamente. Nuestro equipo se pondrá en contacto contigo.',
        ], $duplicada ? 200 : 201);
    }
}
