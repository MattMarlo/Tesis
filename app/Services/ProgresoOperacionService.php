<?php

namespace App\Services;

use App\Models\OperacionViaje;
use App\Models\Reserva;

class ProgresoOperacionService
{
    public function __construct(
        private readonly SincronizarTareasItinerarioService $sincronizarTareas
    ) {
    }

    public function calcular(OperacionViaje $operacion): array
    {
        $operacion->loadMissing([
            'reserva.destino',
            'reserva.cliente',
            'reserva.pagos',
            'reserva.grupo.clientes',
            'reserva.viajerosReserva',
            'vuelos.boletos',
            'alojamientos.asignacionesHabitacion',
            'alojamientos.habitaciones',
            'guias',
        ]);

        $reserva = $operacion->reserva;

        /*
         * Sincroniza las actividades gestionables del itinerario
         * con las tareas correspondientes a esta operación.
         */
        $tareasItinerario = $this->sincronizarTareas
            ->sincronizar($operacion);

        $totalTareasItinerario = $tareasItinerario->count();

        $tareasItinerarioResueltas = $tareasItinerario
            ->filter(
                fn ($tarea) => $tarea->estaResuelta()
            )
            ->count();

        $tareasItinerarioPendientes = max(
            0,
            $totalTareasItinerario - $tareasItinerarioResueltas
        );

        $familiaNueva = $reserva->grupo?->usaCategoriasFamiliares()
            ?? false;

        $personas = $this->personas(
            $reserva,
            $familiaNueva
        );

        $esperados = $familiaNueva
            ? (int) $reserva->cantidad_viajeros
            : $personas->count();

        $identificados = $personas->count();

        $documentados = $personas
            ->filter(
                fn ($persona) =>
                    filled($persona['tipo_documento']) &&
                    filled($persona['documento'])
            )
            ->count();

        $requierenBoleto = $personas->where(
            'categoria',
            '!=',
            Reserva::TARIFA_INFANTE
        );

        $requierenHabitacion = $personas->where(
            'categoria',
            '!=',
            Reserva::TARIFA_INFANTE
        );

        /*
         * Todos los paquetes turísticos administrados por
         * Passion Travel incluyen transporte aéreo.
         */
        $requiereVuelo = true;

        /*
         * El alojamiento y el guía se determinan según los
         * servicios incluidos en el paquete.
         */
        $servicios = collect(
            $reserva->destino?->incluye ?? []
        )
            ->map(
                fn ($servicio) => mb_strtolower(
                    (string) $servicio
                )
            )
            ->implode(' ');

        $requiereAlojamiento =
            str_contains($servicios, 'hotel') ||
            str_contains($servicios, 'alojamiento') ||
            str_contains($servicios, 'hospedaje');

        $requiereGuia =
            str_contains($servicios, 'guía') ||
            str_contains($servicios, 'guia');

        /*
         * Vuelos activos.
         */
        $vuelos = $operacion
            ->vuelos
            ->where(
                'estado',
                '!=',
                'cancelado'
            );

        /*
         * Todos los vuelos deben estar confirmados.
         */
        $vuelosConfirmados =
            $vuelos->isNotEmpty() &&
            $vuelos->every(
                fn ($vuelo) =>
                    $vuelo->estado === 'confirmado'
            );

        /*
         * Viajeros con boleto emitido en todos los vuelos.
         */
        $conBoleto = $vuelos->isNotEmpty()
            ? $requierenBoleto
                ->filter(
                    fn ($persona) =>
                        $vuelos->every(
                            fn ($vuelo) =>
                                $vuelo->boletos->contains(
                                    fn ($boleto) =>
                                        $boleto->estado_emision ===
                                            'emitido' &&
                                        $this->boletoEsDe(
                                            $boleto,
                                            $persona
                                        )
                                )
                        )
                )
                ->count()
            : 0;

        /*
         * Cantidad de boletos emitidos por vuelo.
         */
        $boletosPorVuelo = $vuelos->mapWithKeys(
            function ($vuelo) use ($requierenBoleto) {
                $emitidos = $requierenBoleto
                    ->filter(
                        fn ($persona) =>
                            $vuelo->boletos->contains(
                                fn ($boleto) =>
                                    $boleto->estado_emision ===
                                        'emitido' &&
                                    $this->boletoEsDe(
                                        $boleto,
                                        $persona
                                    )
                            )
                    )
                    ->count();

                return [
                    $vuelo->id => [
                        'actual' => $emitidos,
                        'total' => $requierenBoleto->count(),
                    ],
                ];
            }
        );

        /*
         * Los viajeros que requieren boleto también deben
         * tener asiento.
         */
        $requierenAsiento = $requierenBoleto;

        /*
         * Viajeros con boleto y asiento en todos los vuelos.
         */
        $conAsiento = $vuelos->isNotEmpty()
            ? $requierenAsiento
                ->filter(
                    fn ($persona) =>
                        $vuelos->every(
                            fn ($vuelo) =>
                                $vuelo->boletos->contains(
                                    fn ($boleto) =>
                                        $boleto->estado_emision ===
                                            'emitido' &&
                                        filled($boleto->asiento) &&
                                        $this->boletoEsDe(
                                            $boleto,
                                            $persona
                                        )
                                )
                        )
                )
                ->count()
            : 0;

        /*
         * Alojamientos activos.
         */
        $alojamientos = $operacion
            ->alojamientos
            ->where(
                'estado',
                '!=',
                'cancelado'
            );

        /*
         * Compatibilidad con operaciones antiguas completadas
         * antes de implementar habitaciones detalladas.
         */
        $distribucionHistorica =
            $operacion->estaCompleta() &&
            $alojamientos->isNotEmpty() &&
            $alojamientos->every(
                fn ($alojamiento) =>
                    $alojamiento->habitaciones->isEmpty()
            );

        /*
         * Cantidad de viajeros con habitación.
         */
        $conHabitacion = $distribucionHistorica
            ? $requierenHabitacion->count()
            : (
                $requiereAlojamiento &&
                $alojamientos->isNotEmpty()
                    ? $requierenHabitacion
                        ->filter(
                            fn ($persona) =>
                                $alojamientos->every(
                                    fn ($alojamiento) =>
                                        $alojamiento
                                            ->asignacionesHabitacion
                                            ->contains(
                                                fn ($asignacion) =>
                                                    $this->asignacionEsDe(
                                                        $asignacion,
                                                        $persona
                                                    )
                                            )
                                )
                        )
                        ->count()
                    : 0
            );

        /*
         * El alojamiento debe estar confirmado cuando
         * el paquete lo requiere.
         */
        $alojamientoConfirmado =
            !$requiereAlojamiento ||
            (
                $alojamientos->isNotEmpty() &&
                $alojamientos->every(
                    fn ($alojamiento) =>
                        $alojamiento->estado === 'confirmado'
                )
            );

        /*
         * El guía debe estar confirmado cuando el paquete
         * lo incluye.
         */
        $guiaConfirmado =
            !$requiereGuia ||
            $operacion->guias->contains(
                'estado',
                'confirmado'
            );

        /*
         * Cantidad de viajeros asignados por alojamiento.
         */
        $habitacionesPorAlojamiento = $alojamientos->mapWithKeys(
            fn ($alojamiento) => [
                $alojamiento->id => [
                    'actual' => $requierenHabitacion
                        ->filter(
                            fn ($persona) =>
                                $alojamiento
                                    ->asignacionesHabitacion
                                    ->contains(
                                        fn ($asignacion) =>
                                            $this->asignacionEsDe(
                                                $asignacion,
                                                $persona
                                            )
                                    )
                        )
                        ->count(),

                    'total' => $requierenHabitacion->count(),
                ],
            ]
        );

        /*
         * Información económica.
         */
        $totalPagado = (float) $reserva
            ->pagos
            ->sum('monto_depositado');

        $saldo = max(
            0,
            (float) $reserva->precio_total_viaje - $totalPagado
        );

        /*
         * Componentes utilizados para calcular el porcentaje.
         */
        $componentes = [
            $this->ratio(
                $identificados,
                $esperados
            ),

            $this->ratio(
                $documentados,
                $esperados
            ),

            $saldo <= 0
                ? 1.0
                : 0.0,
        ];

        /*
         * Los vuelos siempre forman parte del progreso.
         */
        $componentes[] = $vuelosConfirmados
            ? 1.0
            : 0.0;

        $componentes[] = $this->ratio(
            $conBoleto,
            $requierenBoleto->count()
        );

        $componentes[] = $this->ratio(
            $conAsiento,
            $requierenAsiento->count()
        );

        /*
         * El alojamiento se incluye únicamente cuando
         * es requerido.
         */
        if ($requiereAlojamiento) {
            $componentes[] = $this->ratio(
                $conHabitacion,
                $requierenHabitacion->count()
            );

            $componentes[] = $alojamientoConfirmado
                ? 1.0
                : 0.0;
        }

        /*
         * El guía se incluye únicamente cuando es requerido.
         */
        if ($requiereGuia) {
            $componentes[] = $guiaConfirmado
                ? 1.0
                : 0.0;
        }

        /*
         * Las tareas del itinerario forman parte del progreso
         * solamente cuando existen actividades gestionables.
         */
        if ($totalTareasItinerario > 0) {
            $componentes[] = $this->ratio(
                $tareasItinerarioResueltas,
                $totalTareasItinerario
            );
        }

        /*
         * Motivos que impiden completar la operación.
         */
        $motivos = [];

        if ($identificados < $esperados) {
            $motivos[] = $familiaNueva
                ? 'Faltan los datos personales de los acompañantes antes de completar la documentación del viaje.'
                : 'Faltan viajeros por identificar.';
        }

        if ($documentados < $esperados) {
            $motivos[] =
                'Faltan documentos de viajeros.';
        }

        if ($conBoleto < $requierenBoleto->count()) {
            $motivos[] =
                'Faltan boletos emitidos.';
        }

        if (!$vuelosConfirmados) {
            $motivos[] =
                'Falta registrar y confirmar todos los vuelos requeridos.';
        }

        if ($conAsiento < $requierenAsiento->count()) {
            $motivos[] =
                'Faltan asientos para viajeros que los requieren.';
        }

        if (
            $requiereAlojamiento &&
            $conHabitacion < $requierenHabitacion->count()
        ) {
            $motivos[] =
                'Faltan viajeros por distribuir en habitaciones.';
        }

        if (!$alojamientoConfirmado) {
            $motivos[] =
                'Falta confirmar el alojamiento.';
        }

        if (!$guiaConfirmado) {
            $motivos[] =
                'Falta confirmar el guía.';
        }

        if ($tareasItinerarioPendientes > 0) {
            $motivos[] =
                'Faltan ' .
                $tareasItinerarioPendientes .
                ' tareas del itinerario por completar.';
        }

        if ($saldo > 0) {
            $motivos[] =
                'La preparación puede continuar, pero el viaje no podrá marcarse como listo hasta completar el pago.';
        }

        $puedeCompletar = $motivos === [];

        /*
         * Resultado final utilizado por la vista
         * de preparación de viajes.
         */
        return [
            'viajeros_identificados' => [
                'actual' => $identificados,
                'total' => $esperados,
            ],

            'documentos_registrados' => [
                'actual' => $documentados,
                'total' => $esperados,
            ],

            'boletos_emitidos' => [
                'actual' => $conBoleto,
                'total' => $requierenBoleto->count(),
                'aplica' => $requiereVuelo,
            ],

            'asientos_asignados' => [
                'actual' => $conAsiento,
                'total' => $requierenAsiento->count(),
                'aplica' => $requiereVuelo,
            ],

            'viajeros_con_habitacion' => [
                'actual' => $conHabitacion,

                'total' => $requiereAlojamiento
                    ? $requierenHabitacion->count()
                    : 0,

                'aplica' => $requiereAlojamiento,
            ],

            'alojamiento_confirmado' =>
                $alojamientoConfirmado,

            'guia_confirmado' =>
                $guiaConfirmado,

            'estado_pago' =>
                $reserva->estado_pago,

            'saldo_pendiente' => round(
                $saldo,
                2
            ),

            'porcentaje_general' => (int) round(
                array_sum($componentes) /
                count($componentes) *
                100
            ),

            'motivos_pendientes' =>
                $motivos,

            'puede_completar' =>
                $puedeCompletar,

            'puede_notificar' =>
                $puedeCompletar &&
                $operacion->estaCompleta(),

            'boletos_por_vuelo' =>
                $boletosPorVuelo,

            'habitaciones_por_alojamiento' =>
                $habitacionesPorAlojamiento,

            'personas' =>
                $personas,

            'tareas_itinerario' => [
                'actual' =>
                    $tareasItinerarioResueltas,

                'total' =>
                    $totalTareasItinerario,

                'pendientes' =>
                    $tareasItinerarioPendientes,

                'aplica' =>
                    $totalTareasItinerario > 0,
            ],

            'tareas_gestion' =>
                $tareasItinerario->values(),

            'familia_nueva' =>
                $familiaNueva,
        ];
    }

    private function personas(
        Reserva $reserva,
        bool $familiaNueva
    ) {
        /*
         * Reservas familiares nuevas.
         */
        if ($familiaNueva) {
            return $reserva
                ->viajerosReserva
                ->map(
                    fn ($viajero) => [
                        'tipo' =>
                            'viajero',

                        'id' =>
                            $viajero->id,

                        'nombre' =>
                            $viajero->nombre_completo,

                        'tipo_documento' =>
                            $viajero->tipo_documento,

                        'documento' =>
                            $viajero->documento,

                        'documento_enmascarado' =>
                            $viajero->documento_enmascarado,

                        'categoria' =>
                            $viajero->categoria_tarifa,

                        'edad' =>
                            $viajero->edad_al_viajar,

                        'es_titular' =>
                            $viajero->es_titular,

                        'requiere_boleto' =>
                            $viajero->categoria_tarifa !==
                            Reserva::TARIFA_INFANTE,

                        'requiere_habitacion' =>
                            $viajero->categoria_tarifa !==
                            Reserva::TARIFA_INFANTE,
                    ]
                )
                ->values();
        }

        /*
         * Reservas individuales y grupales tradicionales.
         */
        $clientes = $reserva->esIndividual()
            ? collect([
                $reserva->cliente,
            ])->filter()
            : (
                $reserva->grupo?->clientes
                ?? collect()
            );

        return $clientes
            ->map(
                function ($cliente) use ($reserva) {
                    $categoria = $reserva->esIndividual()
                        ? $reserva->categoria_tarifa
                        : $cliente->pivot?->categoria_tarifa;

                    return [
                        'tipo' =>
                            'cliente',

                        'id' =>
                            $cliente->id,

                        'nombre' =>
                            $cliente->nombre_completo,

                        'tipo_documento' =>
                            $cliente->tipo_documento,

                        'documento' =>
                            $cliente->documento,

                        'documento_enmascarado' =>
                            $this->enmascarar(
                                $cliente->documento
                            ),

                        'categoria' =>
                            $categoria,

                        'edad' =>
                            $reserva->esIndividual()
                                ? $reserva->edad_viajero
                                : $cliente->pivot?->edad_al_viajar,

                        'es_titular' =>
                            (int) $reserva->cliente_id ===
                            (int) $cliente->id,

                        'requiere_boleto' =>
                            $categoria !==
                            Reserva::TARIFA_INFANTE,

                        'requiere_habitacion' =>
                            $categoria !==
                            Reserva::TARIFA_INFANTE,
                    ];
                }
            )
            ->values();
    }

    private function boletoEsDe(
        $boleto,
        array $persona
    ): bool {
        return $persona['tipo'] === 'viajero'
            ? (
                (int) $boleto->viajero_reserva_id ===
                (int) $persona['id']
            )
            : (
                (int) $boleto->cliente_id ===
                (int) $persona['id']
            );
    }

    private function asignacionEsDe(
        $asignacion,
        array $persona
    ): bool {
        return $persona['tipo'] === 'viajero'
            ? (
                (int) $asignacion->viajero_reserva_id ===
                (int) $persona['id']
            )
            : (
                (int) $asignacion->cliente_id ===
                (int) $persona['id']
            );
    }

    private function ratio(
        int $actual,
        int $total
    ): float {
        return $total <= 0
            ? 1.0
            : min(
                1,
                $actual / $total
            );
    }

    private function enmascarar(
        ?string $documento
    ): string {
        if (!$documento) {
            return 'Pendiente';
        }

        $largo = mb_strlen($documento);

        return str_repeat(
            '*',
            max(
                0,
                $largo - 4
            )
        ) . mb_substr(
            $documento,
            -4
        );
    }
}