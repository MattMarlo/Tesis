@php
    $resumenTareas =
        $progreso['tareas_itinerario']
        ?? [
            'actual' => 0,
            'total' => 0,
            'pendientes' => 0,
            'aplica' => false,
        ];

    $tareasGestion = collect(
        $progreso['tareas_gestion']
        ?? []
    );

    $porcentajeTareas =
        $resumenTareas['total'] > 0
            ? (int) round(
                $resumenTareas['actual'] /
                $resumenTareas['total'] *
                100
            )
            : 100;
@endphp

@if ($resumenTareas['aplica'])
    <section
        id="tareasItinerario"
        class="bloque-expediente bloque-tareas-itinerario"
    >
        <div class="bloque-expediente-titulo">
            <div>
                <span>Coordinaciones operativas</span>

                <h2>Tareas del itinerario</h2>

                <p class="descripcion-tareas-itinerario">
                    Actividades del paquete que requieren una
                    gestión previa por parte de la agencia.
                </p>
            </div>

            <div class="resumen-tareas-itinerario">
                <strong>
                    {{ $resumenTareas['actual'] }}
                    de
                    {{ $resumenTareas['total'] }}
                </strong>

                <span>resueltas</span>
            </div>
        </div>

        <div class="barra-tareas-itinerario">
            <span
                style="width: {{ $porcentajeTareas }}%"
            ></span>
        </div>

        <div class="lista-tareas-itinerario">
            @foreach ($tareasGestion as $tarea)
                @php
                    $nombreEstadoTarea = match (
                        $tarea->estado
                    ) {
                        'pendiente' =>
                            'Pendiente',

                        'en_proceso' =>
                            'En proceso',

                        'completada' =>
                            'Completada',

                        'omitida' =>
                            'Omitida',

                        default =>
                            'Sin estado',
                    };

                    $nombreTipoGestion = match (
                        $tarea->tipo_gestion
                    ) {
                        'reserva' =>
                            'Reserva',

                        'entrada' =>
                            'Entrada',

                        'guia' =>
                            'Guía',

                        'alimentacion' =>
                            'Alimentación',

                        'alojamiento' =>
                            'Alojamiento',

                        'actividad' =>
                            'Actividad',

                        default =>
                            'Otra gestión',
                    };

                    $horaInicio = $tarea->hora_inicio
                        ? substr(
                            (string) $tarea->hora_inicio,
                            0,
                            5
                        )
                        : null;

                    $horaFin = $tarea->hora_fin
                        ? substr(
                            (string) $tarea->hora_fin,
                            0,
                            5
                        )
                        : null;
                @endphp

                <article
                    class="
                        tarjeta-tarea-itinerario
                        tarea-estado-{{ $tarea->estado }}
                    "
                >
                    <header class="encabezado-tarea-itinerario">
                        <div class="identificacion-tarea">
                            <span class="dia-tarea">
                                Día {{ $tarea->dia }}
                            </span>

                            <span class="tipo-tarea">
                                {{ $nombreTipoGestion }}
                            </span>
                        </div>

                        <span
                            class="
                                estado-tarea-itinerario
                                estado-tarea-{{ $tarea->estado }}
                            "
                        >
                            {{ $nombreEstadoTarea }}
                        </span>
                    </header>

                    <div class="contenido-tarea-itinerario">
                        <div class="informacion-tarea-itinerario">
                            <h3>
                                {{ $tarea->nombre }}
                            </h3>

                            @if ($tarea->descripcion)
                                <p>
                                    {{ $tarea->descripcion }}
                                </p>
                            @endif

                            <div class="detalles-tarea-itinerario">
                                @if ($horaInicio || $horaFin)
                                    <span>
                                        <i class="bi bi-clock"></i>

                                        @if ($horaInicio && $horaFin)
                                            {{ $horaInicio }}
                                            –
                                            {{ $horaFin }}
                                        @elseif ($horaInicio)
                                            Desde las
                                            {{ $horaInicio }}
                                        @else
                                            Hasta las
                                            {{ $horaFin }}
                                        @endif
                                    </span>
                                @endif

                                @if ($tarea->ubicacion)
                                    <span>
                                        <i class="bi bi-geo-alt"></i>
                                        {{ $tarea->ubicacion }}
                                    </span>
                                @endif
                            </div>

                            @if ($tarea->completada_at)
                                <div class="resolucion-tarea">
                                    <i class="bi bi-check-circle"></i>

                                    Registrada el
                                    {{ $tarea->completada_at->format(
                                        'd/m/Y H:i'
                                    ) }}
                                </div>
                            @endif
                        </div>

                        <form
                            method="POST"
                            action="{{ route(
                                'operaciones.tareas.update',
                                [
                                    'operacion' =>
                                        $operacion->id,

                                    'tarea' =>
                                        $tarea->id,
                                ]
                            ) }}"
                            class="formulario-tarea-itinerario"
                        >
                            @csrf
                            @method('PATCH')

                            <div class="campo-tarea-itinerario">
                                <label
                                    for="estado_tarea_{{ $tarea->id }}"
                                >
                                    Estado
                                </label>

                                <select
                                    id="estado_tarea_{{ $tarea->id }}"
                                    name="estado"
                                    @disabled(!$editable)
                                >
                                    <option
                                        value="pendiente"
                                        @selected(
                                            $tarea->estado ===
                                            'pendiente'
                                        )
                                    >
                                        Pendiente
                                    </option>

                                    <option
                                        value="en_proceso"
                                        @selected(
                                            $tarea->estado ===
                                            'en_proceso'
                                        )
                                    >
                                        En proceso
                                    </option>

                                    <option
                                        value="completada"
                                        @selected(
                                            $tarea->estado ===
                                            'completada'
                                        )
                                    >
                                        Completada
                                    </option>

                                    <option
                                        value="omitida"
                                        @selected(
                                            $tarea->estado ===
                                            'omitida'
                                        )
                                    >
                                        Omitida
                                    </option>
                                </select>
                            </div>

                            <div class="campo-tarea-itinerario">
                                <label
                                    for="observaciones_tarea_{{ $tarea->id }}"
                                >
                                    Observaciones
                                </label>

                                <textarea
                                    id="observaciones_tarea_{{ $tarea->id }}"
                                    name="observaciones"
                                    rows="3"
                                    maxlength="2000"
                                    placeholder="Proveedor, confirmación, referencia o motivo..."
                                    @disabled(!$editable)
                                >{{ $tarea->observaciones }}</textarea>

                                <small>
                                    Si seleccionas “Omitida”, la
                                    justificación es obligatoria.
                                </small>
                            </div>

                            @if ($editable)
                                <button
                                    type="submit"
                                    class="btn-guardar-tarea"
                                >
                                    <i class="bi bi-check2"></i>
                                    Guardar tarea
                                </button>
                            @endif
                        </form>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@endif