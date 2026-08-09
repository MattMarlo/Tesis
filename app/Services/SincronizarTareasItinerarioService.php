<?php

namespace App\Services;

use App\Models\OperacionViaje;
use App\Models\TareaOperacionViaje;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SincronizarTareasItinerarioService
{
    /**
     * Crea o actualiza las tareas de una operación
     * desde las actividades gestionables del paquete.
     */
    public function sincronizar(
        OperacionViaje $operacion
    ): Collection {
        $operacion->loadMissing([
            'reserva.destino',
        ]);

        $itinerario =
            $operacion
                ->reserva
                ?->destino
                ?->itinerario
            ?? [];

        $actividades =
            $this->extraerActividadesGestionables(
                $itinerario
            );

        DB::transaction(
            function () use (
                $operacion,
                $actividades
            ) {
                $uuidVigentes = [];

                foreach (
                    $actividades
                    as $actividad
                ) {
                    $uuidVigentes[] =
                        $actividad[
                            'actividad_uuid'
                        ];

                    /*
                     * updateOrCreate evita duplicar tareas.
                     *
                     * Solo se actualizan los datos provenientes
                     * del paquete. El estado y las observaciones
                     * del agente se conservan.
                     */
                    $operacion
                        ->tareas()
                        ->updateOrCreate(
                            [
                                'actividad_uuid' =>
                                    $actividad[
                                        'actividad_uuid'
                                    ],
                            ],
                            [
                                'dia' =>
                                    $actividad['dia'],

                                'nombre' =>
                                    $actividad['nombre'],

                                'descripcion' =>
                                    $actividad[
                                        'descripcion'
                                    ],

                                'hora_inicio' =>
                                    $actividad[
                                        'hora_inicio'
                                    ],

                                'hora_fin' =>
                                    $actividad[
                                        'hora_fin'
                                    ],

                                'ubicacion' =>
                                    $actividad[
                                        'ubicacion'
                                    ],

                                'tipo_gestion' =>
                                    $actividad[
                                        'tipo_gestion'
                                    ],

                                'vigente' =>
                                    true,
                            ]
                        );
                }

                /*
                 * Las tareas correspondientes a actividades
                 * retiradas del paquete se conservan como
                 * historial, pero dejan de afectar el progreso.
                 */
                if (empty($uuidVigentes)) {
                    $operacion
                        ->tareas()
                        ->update([
                            'vigente' => false,
                        ]);
                } else {
                    $operacion
                        ->tareas()
                        ->whereNotIn(
                            'actividad_uuid',
                            $uuidVigentes
                        )
                        ->update([
                            'vigente' => false,
                        ]);
                }
            }
        );

        /*
         * Obliga a Eloquent a consultar nuevamente
         * las tareas después de sincronizarlas.
         */
        $operacion->unsetRelation(
            'tareas'
        );

        $operacion->unsetRelation(
            'tareasVigentes'
        );

        return $operacion
            ->tareasVigentes()
            ->get();
    }

    /**
     * Extrae solamente actividades marcadas con
     * requiere_gestion=true.
     */
    private function extraerActividadesGestionables(
        array $itinerario
    ): Collection {
        return collect($itinerario)
            ->flatMap(
                function ($dia) {
                    $numeroDia =
                        (int) (
                            $dia['dia']
                            ?? 0
                        );

                    return collect(
                        $dia['actividades']
                        ?? []
                    )
                        ->filter(
                            function (
                                $actividad
                            ) {
                                $requiereGestion =
                                    filter_var(
                                        $actividad[
                                            'requiere_gestion'
                                        ]
                                        ?? false,
                                        FILTER_VALIDATE_BOOLEAN
                                    );

                                return
                                    $requiereGestion &&
                                    filled(
                                        $actividad[
                                            'uuid'
                                        ]
                                        ?? null
                                    ) &&
                                    filled(
                                        $actividad[
                                            'nombre'
                                        ]
                                        ?? null
                                    );
                            }
                        )
                        ->map(
                            function (
                                $actividad
                            ) use (
                                $numeroDia
                            ) {
                                $uuid = (string) (
                                    $actividad['uuid']
                                    ?? ''
                                );

                                /*
                                 * Los UUID inválidos se ignoran para
                                 * no crear tareas que después puedan
                                 * duplicarse durante la sincronización.
                                 */
                                if (
                                    !Str::isUuid(
                                        $uuid
                                    )
                                ) {
                                    return null;
                                }

                                $tipoGestion =
                                    $this
                                        ->normalizarTipoGestion(
                                            $actividad[
                                                'tipo_gestion'
                                            ]
                                            ?? null
                                        );

                                return [
                                    'actividad_uuid' =>
                                        $uuid,

                                    'dia' =>
                                        $numeroDia,

                                    'nombre' =>
                                        trim(
                                            (string)
                                            $actividad[
                                                'nombre'
                                            ]
                                        ),

                                    'descripcion' =>
                                        filled(
                                            $actividad[
                                                'descripcion'
                                            ]
                                            ?? null
                                        )
                                            ? trim(
                                                (string)
                                                $actividad[
                                                    'descripcion'
                                                ]
                                            )
                                            : null,

                                    'hora_inicio' =>
                                        filled(
                                            $actividad[
                                                'hora_inicio'
                                            ]
                                            ?? null
                                        )
                                            ? $actividad[
                                                'hora_inicio'
                                            ]
                                            : null,

                                    'hora_fin' =>
                                        filled(
                                            $actividad[
                                                'hora_fin'
                                            ]
                                            ?? null
                                        )
                                            ? $actividad[
                                                'hora_fin'
                                            ]
                                            : null,

                                    'ubicacion' =>
                                        filled(
                                            $actividad[
                                                'ubicacion'
                                            ]
                                            ?? null
                                        )
                                            ? trim(
                                                (string)
                                                $actividad[
                                                    'ubicacion'
                                                ]
                                            )
                                            : null,

                                    'tipo_gestion' =>
                                        $tipoGestion,
                                ];
                            }
                        )
                        ->filter();
                }
            )
            ->values();
    }

    /**
     * Evita guardar tipos que no sean reconocidos
     * por el módulo operativo.
     */
    private function normalizarTipoGestion(
        ?string $tipoGestion
    ): string {
        $tiposPermitidos = [
            TareaOperacionViaje::TIPO_RESERVA,
            TareaOperacionViaje::TIPO_ENTRADA,
            TareaOperacionViaje::TIPO_GUIA,
            TareaOperacionViaje::TIPO_ALIMENTACION,
            TareaOperacionViaje::TIPO_ALOJAMIENTO,
            TareaOperacionViaje::TIPO_ACTIVIDAD,
            TareaOperacionViaje::TIPO_OTRO,
        ];

        return in_array(
            $tipoGestion,
            $tiposPermitidos,
            true
        )
            ? $tipoGestion
            : TareaOperacionViaje::TIPO_OTRO;
    }
}