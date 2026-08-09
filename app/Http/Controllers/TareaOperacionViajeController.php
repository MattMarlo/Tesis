<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarTareaOperacionViajeRequest;
use App\Models\OperacionViaje;
use App\Models\TareaOperacionViaje;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class TareaOperacionViajeController extends Controller
{
    public function update(
        ActualizarTareaOperacionViajeRequest $request,
        OperacionViaje $operacion,
        TareaOperacionViaje $tarea
    ): RedirectResponse {
        /*
         * Impide modificar una tarea utilizando el ID de
         * una operación diferente en la URL.
         */
        abort_unless(
            (int) $tarea->operacion_viaje_id ===
                (int) $operacion->id,
            404
        );

        /*
         * Las tareas retiradas del itinerario se conservan
         * como historial y no deben seguir modificándose.
         */
        if (!$tarea->vigente) {
            return back()
                ->withErrors([
                    'tarea' =>
                        'Esta tarea ya no pertenece al itinerario vigente.',
                ]);
        }

        $datos = $request->validated();

        DB::transaction(
            function () use (
                $tarea,
                $datos,
                $request
            ): void {
                $estadoAnterior =
                    $tarea->estado;

                $eraResuelta =
                    $tarea->estaResuelta();

                $nuevoEstado =
                    $datos['estado'];

                $ahoraEstaResuelta = in_array(
                    $nuevoEstado,
                    [
                        TareaOperacionViaje::ESTADO_COMPLETADA,
                        TareaOperacionViaje::ESTADO_OMITIDA,
                    ],
                    true
                );

                $tarea->estado =
                    $nuevoEstado;

                $tarea->observaciones =
                    $datos['observaciones'] ?? null;

                if ($ahoraEstaResuelta) {
                    /*
                     * La fecha cambia cuando la tarea pasa por
                     * primera vez a resuelta o cambia entre
                     * completada y omitida.
                     *
                     * Si solamente se editan las observaciones,
                     * se conserva la fecha original.
                     */
                    if (
                        !$eraResuelta ||
                        $estadoAnterior !== $nuevoEstado ||
                        !$tarea->completada_at
                    ) {
                        $tarea->completada_at =
                            now();

                        $tarea->completada_por_user_id =
                            $request->user()->id;
                    }
                } else {
                    /*
                     * Si se reabre la tarea, se eliminan los datos
                     * que indicaban su resolución anterior.
                     */
                    $tarea->completada_at =
                        null;

                    $tarea->completada_por_user_id =
                        null;
                }

                $tarea->save();
            }
        );

        $mensaje = match ($tarea->estado) {
            TareaOperacionViaje::ESTADO_PENDIENTE =>
                'La tarea volvió al estado pendiente.',

            TareaOperacionViaje::ESTADO_EN_PROCESO =>
                'La tarea fue marcada como en proceso.',

            TareaOperacionViaje::ESTADO_COMPLETADA =>
                'La tarea fue completada correctamente.',

            TareaOperacionViaje::ESTADO_OMITIDA =>
                'La tarea fue omitida y se registró su justificación.',

            default =>
                'La tarea fue actualizada correctamente.',
        };

        return redirect()
            ->route(
                'operaciones.show',
                [
                    'id' =>
                        $operacion->reserva_id,
                ]
            )
            ->with(
                'success',
                $mensaje
            );
    }
}