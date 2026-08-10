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
                $resumenTareas['actual']
                / $resumenTareas['total']
                * 100
            )
            : 100;

    $tiposGestionGenerica = [
        \App\Models\TareaOperacionViaje::TIPO_TREN,
        \App\Models\TareaOperacionViaje::TIPO_TRASLADO,
        \App\Models\TareaOperacionViaje::TIPO_ENTRADA,
        \App\Models\TareaOperacionViaje::TIPO_ALIMENTACION,
        \App\Models\TareaOperacionViaje::
            TIPO_ACTIVIDAD_RESERVADA,
        \App\Models\TareaOperacionViaje::TIPO_SEGURO,
        \App\Models\TareaOperacionViaje::TIPO_OTRO,
    ];

    $tiposGestionEspecializada = [
        \App\Models\TareaOperacionViaje::TIPO_VUELO,
        \App\Models\TareaOperacionViaje::TIPO_ALOJAMIENTO,
        \App\Models\TareaOperacionViaje::TIPO_GUIA,
    ];
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
                        \App\Models\TareaOperacionViaje::
                            ESTADO_PENDIENTE =>
                                'Pendiente',

                        \App\Models\TareaOperacionViaje::
                            ESTADO_EN_PROCESO =>
                                'En proceso',

                        \App\Models\TareaOperacionViaje::
                            ESTADO_COMPLETADA =>
                                'Completada',

                        \App\Models\TareaOperacionViaje::
                            ESTADO_OMITIDA =>
                                'Omitida',

                        default =>
                            'Sin estado',
                    };

                    $nombreTipoGestion =
                        \App\Models\TareaOperacionViaje::
                            etiquetasTipoGestion()[
                                $tarea->tipo_gestion
                            ]
                        ?? 'Otra gestión';

                    $horaInicio =
                        $tarea->hora_inicio
                            ? substr(
                                (string) $tarea->hora_inicio,
                                0,
                                5
                            )
                            : null;

                    $horaFin =
                        $tarea->hora_fin
                            ? substr(
                                (string) $tarea->hora_fin,
                                0,
                                5
                            )
                            : null;

                    $fechaTarea =
                        $reserva->fecha_viaje
                            ?->copy()
                            ->addDays(
                                max(
                                    0,
                                    (int) $tarea->dia - 1
                                )
                            );

                    $esGestionGenerica = in_array(
                        $tarea->tipo_gestion,
                        $tiposGestionGenerica,
                        true
                    );

                    $esGestionEspecializada = in_array(
                        $tarea->tipo_gestion,
                        $tiposGestionEspecializada,
                        true
                    );

                    $configuracionEspecializada = match (
                        $tarea->tipo_gestion
                    ) {
                        \App\Models\TareaOperacionViaje::
                            TIPO_VUELO => [
                                'boton' =>
                                    'btnNuevoVuelo',

                                'icono' =>
                                    'bi-airplane',

                                'texto' =>
                                    'Gestionar vuelo y boletos',
                            ],

                        \App\Models\TareaOperacionViaje::
                            TIPO_ALOJAMIENTO => [
                                'boton' =>
                                    'btnNuevoAlojamiento',

                                'icono' =>
                                    'bi-building',

                                'texto' =>
                                    'Gestionar hotel y habitaciones',
                            ],

                        \App\Models\TareaOperacionViaje::
                            TIPO_GUIA => [
                                'boton' =>
                                    'btnNuevoGuia',

                                'icono' =>
                                    'bi-person-badge',

                                'texto' =>
                                    'Gestionar guía',
                            ],

                        default => null,
                    };

                    $vueloVinculado =
                        $tarea->tipo_gestion ===
                            \App\Models\TareaOperacionViaje::TIPO_VUELO
                        && $tarea->gestionable instanceof
                            \App\Models\VueloReserva
                            ? $tarea->gestionable
                            : null;

                    $progresoVuelo = $vueloVinculado
                        ? (
                            $progreso['boletos_por_vuelo'][
                                $vueloVinculado->id
                            ]
                            ?? [
                                'actual' =>
                                    $vueloVinculado
                                        ->boletos
                                        ->where(
                                            'estado_emision',
                                            \App\Models\BoletoVuelo::
                                                ESTADO_EMITIDO
                                        )
                                        ->count(),

                                'total' => max(
                                    1,
                                    (int) (
                                        $totalViajerosEsperados
                                        ?? $reserva
                                            ->cantidad_viajeros
                                        ?? 1
                                    )
                                ),
                            ]
                        )
                        : null;

                    $boletosEmitidos =
                        (int) (
                            $progresoVuelo['actual']
                            ?? 0
                        );

                    $boletosEsperados =
                        (int) (
                            $progresoVuelo['total']
                            ?? 0
                        );

                    $asientosAsignados = $vueloVinculado
                        ? $vueloVinculado
                            ->boletos
                            ->filter(
                                fn ($boleto) =>
                                    $boleto->estado_emision ===
                                        \App\Models\BoletoVuelo::
                                            ESTADO_EMITIDO
                                    && filled(
                                        $boleto->asiento
                                    )
                            )
                            ->count()
                        : 0;

                    $nombreEstadoVuelo = $vueloVinculado
                        ? match ($vueloVinculado->estado) {
                            \App\Models\VueloReserva::
                                ESTADO_CONFIRMADO =>
                                    'Confirmado',

                            \App\Models\VueloReserva::
                                ESTADO_CANCELADO =>
                                    'Cancelado',

                            default =>
                                'Pendiente',
                        }
                        : null;
                @endphp

                <article
                    id="tarea-itinerario-{{ $tarea->id }}"
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

                            @if ($fechaTarea)
                                <span class="fecha-tarea-itinerario">
                                    {{ $fechaTarea
                                        ->locale('es')
                                        ->translatedFormat(
                                            'd \d\e F \d\e Y'
                                        ) }}
                                </span>
                            @endif
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

                                        @if (
                                            $horaInicio
                                            && $horaFin
                                        )
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

                                    {{ $tarea
                                        ->completada_at
                                        ->format(
                                            'd/m/Y H:i'
                                        ) }}
                                </div>
                            @endif

                            @if ($esGestionGenerica)
                                @include(
                                    'modules.operaciones.partials.gestion-operativa',
                                    [
                                        'tarea' => $tarea,
                                    ]
                                )
                            @elseif (
                                $esGestionEspecializada
                                && $configuracionEspecializada
                            )
                                @if ($vueloVinculado)
                                    <div class="resumen-vuelo-contextual">
                                        <div class="resumen-vuelo-contextual-cabecera">
                                            <div class="resumen-vuelo-contextual-identidad">
                                                <span class="resumen-vuelo-icono">
                                                    <i class="bi bi-airplane"></i>
                                                </span>

                                                <div>
                                                    <small>
                                                        Vuelo vinculado
                                                    </small>

                                                    <strong>
                                                        {{ $vueloVinculado->ciudad_origen }}
                                                        <i class="bi bi-arrow-right"></i>
                                                        {{ $vueloVinculado->ciudad_destino }}
                                                    </strong>

                                                    <span>
                                                        {{ $vueloVinculado->aerolinea }}

                                                        @if ($vueloVinculado->numero_vuelo)
                                                            ·
                                                            {{ $vueloVinculado->numero_vuelo }}
                                                        @endif
                                                    </span>
                                                </div>
                                            </div>

                                            <span
                                                class="estado-vuelo-contextual estado-vuelo-{{ $vueloVinculado->estado }}"
                                            >
                                                {{ $nombreEstadoVuelo }}
                                            </span>
                                        </div>

                                        <div class="datos-vuelo-contextual">
                                            <div>
                                                <span>Salida</span>

                                                <strong>
                                                    {{ $vueloVinculado
                                                        ->fecha_hora_salida
                                                        ->format('d/m/Y H:i') }}
                                                </strong>
                                            </div>

                                            <div>
                                                <span>Llegada</span>

                                                <strong>
                                                    {{ $vueloVinculado
                                                        ->fecha_hora_llegada
                                                        ->format('d/m/Y H:i') }}
                                                </strong>
                                            </div>

                                            <div>
                                                <span>Localizador</span>

                                                <strong>
                                                    {{ $vueloVinculado
                                                        ->localizador_reserva
                                                        ?: 'Pendiente' }}
                                                </strong>
                                            </div>
                                        </div>

                                        <div class="progreso-vuelo-contextual">
                                            <div>
                                                <span>Boletos emitidos</span>

                                                <strong>
                                                    {{ $boletosEmitidos }}
                                                    de
                                                    {{ $boletosEsperados }}
                                                </strong>
                                            </div>

                                            <div>
                                                <span>Asientos asignados</span>

                                                <strong>
                                                    {{ $asientosAsignados }}
                                                    de
                                                    {{ $boletosEsperados }}
                                                </strong>
                                            </div>
                                        </div>

                                        <div class="acciones-vuelo-contextual">
                                            @if ($editable)
                                                <button
                                                    type="button"
                                                    class="btn-gestion-contextual btnEditarVuelo"
                                                    data-id="{{ $vueloVinculado->id }}"
                                                >
                                                    <i class="bi bi-pencil-square"></i>
                                                    Editar vuelo
                                                </button>
                                            @endif

                                            <a
                                                href="{{ route(
                                                    'operaciones.vuelos.boletos.index',
                                                    [
                                                        'operacion' =>
                                                            $operacion->id,

                                                        'vuelo' =>
                                                            $vueloVinculado->id,

                                                        'tarea_id' =>
                                                            $tarea->id,
                                                    ]
                                                ) }}"
                                                class="btn-ver-gestion-contextual"
                                            >
                                                <i class="bi bi-ticket-perforated"></i>
                                                Gestionar boletos
                                            </a>
                                        </div>
                                    </div>
                                @elseif ($editable)
                                    <div class="gestion-contextual-tarea">
                                        <button
                                            type="button"
                                            class="
                                                btn-gestion-contextual
                                                btn-gestion-especializada
                                            "
                                            data-tarea-id="{{ $tarea->id }}"
                                            data-tipo-gestion="{{ $tarea->tipo_gestion }}"
                                            data-nombre="{{ $tarea->nombre }}"
                                            data-descripcion="{{ $tarea->descripcion }}"
                                            data-dia="{{ $tarea->dia }}"
                                            data-fecha="{{ $fechaTarea?->format('Y-m-d') }}"
                                            data-hora-inicio="{{ $horaInicio }}"
                                            data-hora-fin="{{ $horaFin }}"
                                            data-ubicacion="{{ $tarea->ubicacion }}"
                                            @if (
                                                $tarea->tipo_gestion !==
                                                    \App\Models\TareaOperacionViaje::TIPO_VUELO
                                            )
                                                onclick="
                                                    document
                                                        .getElementById(
                                                            '{{ $configuracionEspecializada['boton'] }}'
                                                        )
                                                        ?.click()
                                                "
                                            @endif
                                        >
                                            <i
                                                class="
                                                    bi
                                                    {{ $configuracionEspecializada['icono'] }}
                                                "
                                            ></i>

                                            {{ $configuracionEspecializada['texto'] }}
                                        </button>
                                    </div>
                                @endif
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
                                                \App\Models\TareaOperacionViaje::
                                                    ESTADO_PENDIENTE
                                        )
                                    >
                                        Pendiente
                                    </option>

                                    <option
                                        value="en_proceso"
                                        @selected(
                                            $tarea->estado ===
                                                \App\Models\TareaOperacionViaje::
                                                    ESTADO_EN_PROCESO
                                        )
                                    >
                                        En proceso
                                    </option>

                                    <option
                                        value="completada"
                                        @selected(
                                            $tarea->estado ===
                                                \App\Models\TareaOperacionViaje::
                                                    ESTADO_COMPLETADA
                                        )
                                    >
                                        Completada
                                    </option>

                                    <option
                                        value="omitida"
                                        @selected(
                                            $tarea->estado ===
                                                \App\Models\TareaOperacionViaje::
                                                    ESTADO_OMITIDA
                                        )
                                    >
                                        Omitida
                                    </option>
                                </select>

                                @if (
                                    $esGestionGenerica
                                    || $esGestionEspecializada
                                )
                                    <small>
                                        El estado también se actualizará
                                        automáticamente según la gestión
                                        vinculada.
                                    </small>
                                @endif
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