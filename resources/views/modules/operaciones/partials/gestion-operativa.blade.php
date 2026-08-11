@php
    use App\Models\GestionOperativa;
    use App\Models\GestionOperativaViajero;
    use App\Models\TareaOperacionViaje;

    $gestionableActual =
        $tarea->gestionable;

    $gestionActual =
        $gestionableActual instanceof GestionOperativa
            ? $gestionableActual
            : null;

    $tipoGestion =
        $tarea->tipo_gestion;

    $tiposIndividuales = [
        GestionOperativa::TIPO_TREN,
        GestionOperativa::TIPO_ENTRADA,
        GestionOperativa::TIPO_ACTIVIDAD_RESERVADA,
        GestionOperativa::TIPO_SEGURO,
    ];

    $requiereDetalleIndividual = in_array(
        $tipoGestion,
        $tiposIndividuales,
        true
    );

    $etiquetaTipo =
        TareaOperacionViaje::etiquetasTipoGestion()[
            $tipoGestion
        ]
        ?? 'Servicio';

    $accionContextual = match ($tipoGestion) {
        GestionOperativa::TIPO_TREN =>
            'Gestionar reserva de tren',

        GestionOperativa::TIPO_TRASLADO =>
            'Gestionar traslado',

        GestionOperativa::TIPO_ENTRADA =>
            'Gestionar entradas',

        GestionOperativa::TIPO_ALIMENTACION =>
            'Gestionar alimentación',

        GestionOperativa::TIPO_ACTIVIDAD_RESERVADA =>
            'Gestionar actividad',

        GestionOperativa::TIPO_SEGURO =>
            'Gestionar seguro',

        default =>
            'Gestionar servicio',
    };

    $programacionActividad = app(
        \App\Services\ProgramacionTareaContextualService::class
    )->resolver($tarea, $reserva);

    $fechaActividad = $programacionActividad['fecha'];

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

    $fechaHoraInicioSugerida = $gestionActual?->fecha_hora_inicio
        ?->format('Y-m-d\TH:i')
        ?? $programacionActividad['inicio_input'];

    $fechaHoraFinSugerida = $gestionActual?->fecha_hora_fin
        ?->format('Y-m-d\TH:i')
        ?? $programacionActividad['fin_input'];

    $origenSugerido = $gestionActual?->ubicacion_origen
        ?? $programacionActividad['origen'];

    $destinoSugerido = $gestionActual?->destino
        ?? $programacionActividad['destino'];

    if (
        !$gestionActual
        && $tipoGestion === GestionOperativa::TIPO_TRASLADO
        && !$destinoSugerido
        && str_contains(mb_strtolower($tarea->nombre), 'hotel')
    ) {
        $destinoSugerido = $reserva->destino?->hotel
            ?: $reserva->destino?->ciudad_destino;
    }

    $datosAdicionales =
        $gestionActual?->datos_adicionales
        ?? [];

    $detallesViajeros =
        $gestionActual
            ? $gestionActual
                ->detallesViajeros()
                ->get()
            : collect();

    $viajerosReserva =
        $reserva->viajerosReserva;

    $requiereDetalleIndividualDisponible =
        $requiereDetalleIndividual
        && $viajerosReserva->isNotEmpty();

    $cantidadViajeros =
        max(
            1,
            (int) $totalViajerosEsperados
        );

    $usarDatosAnteriores =
        (int) old(
            'contexto_tarea_id'
        ) === (int) $tarea->id;

    $valorFormulario = function (
        string $campo,
        mixed $predeterminado = null
    ) use ($usarDatosAnteriores) {
        return $usarDatosAnteriores
            ? old(
                $campo,
                $predeterminado
            )
            : $predeterminado;
    };

    $modalId =
        'modalGestionOperativa'
        . $tarea->id;

    $formularioId =
        'formularioGestionOperativa'
        . $tarea->id;

    $gestionesCompatibles =
        $operacion
            ->gestionesOperativas()
            ->where(
                'tipo',
                $tipoGestion
            )
            ->when(
                $gestionActual,
                fn ($consulta) =>
                    $consulta->whereKeyNot(
                        $gestionActual->id
                    )
            )
            ->orderByDesc('id')
            ->get();
@endphp

<div class="gestion-contextual-tarea">
    @if ($gestionActual)
        <div class="resumen-gestion-vinculada">
            <div>
                <span>
                    <i class="bi bi-link-45deg"></i>
                    Gestión vinculada
                </span>

                <strong>
                    {{ $gestionActual->proveedor }}
                </strong>

                @if (
                    $gestionActual
                        ->referencia_confirmacion
                )
                    <small>
                        Referencia:
                        {{ $gestionActual
                            ->referencia_confirmacion }}
                    </small>
                @endif
            </div>

            <span
                class="
                    estado-gestion-contextual
                    estado-gestion-{{ $gestionActual->estado }}
                "
            >
                {{ match ($gestionActual->estado) {
                    GestionOperativa::ESTADO_PENDIENTE =>
                        'Pendiente',

                    GestionOperativa::ESTADO_EN_PROCESO =>
                        'En proceso',

                    GestionOperativa::ESTADO_CONFIRMADO =>
                        'Confirmado',

                    GestionOperativa::ESTADO_CANCELADO =>
                        'Cancelado',

                    default =>
                        'Sin estado',
                } }}
            </span>
        </div>
    @endif

    @if ($editable)
        <button
            type="button"
            class="btn-gestion-contextual"
            data-bs-toggle="modal"
            data-bs-target="#{{ $modalId }}"
        >
            <i class="bi bi-sliders"></i>

            {{ $gestionActual
                ? 'Editar gestión'
                : $accionContextual }}
        </button>
    @endif
</div>

<div
    class="modal fade modal-gestion-contextual"
    id="{{ $modalId }}"
    tabindex="-1"
    aria-labelledby="{{ $modalId }}Titulo"
    aria-hidden="true"
>
    <div
        class="
            modal-dialog
            modal-xl
            modal-dialog-scrollable
        "
    >
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <span class="modal-etiqueta-contextual">
                        Día {{ $tarea->dia }}
                        ·
                        {{ $etiquetaTipo }}
                    </span>

                    <h2
                        class="modal-title fs-5"
                        id="{{ $modalId }}Titulo"
                    >
                        {{ $tarea->nombre }}
                    </h2>

                    @if ($fechaActividad)
                        <small>
                            {{ $fechaActividad
                                ->locale('es')
                                ->translatedFormat(
                                    'd \d\e F \d\e Y'
                                ) }}
                        </small>
                    @endif
                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Cerrar"
                ></button>
            </div>

            <div class="modal-body">
                <div class="contexto-actividad-gestion">
                    <span class="contexto-actividad-icono">
                        <i class="bi bi-calendar2-week"></i>
                    </span>
                    <div>
                        <small>Datos tomados de la actividad del paquete</small>
                        <strong>{{ $tarea->nombre }}</strong>
                        <p>
                            @if ($fechaActividad)
                                {{ $fechaActividad->format('d/m/Y') }}
                            @endif
                            @if ($horaInicio)
                                · {{ $horaInicio }}
                            @endif
                            @if ($horaFin)
                                – {{ $horaFin }}
                            @endif
                            @if ($tarea->ubicacion)
                                · {{ $tarea->ubicacion }}
                            @endif
                        </p>
                    </div>
                </div>
                @if (
                    !$gestionActual
                    && $gestionesCompatibles->isNotEmpty()
                )
                    <section class="gestiones-existentes">
                        <div class="titulo-seccion-gestion">
                            <div>
                                <span>Gestiones existentes</span>

                                <h3>
                                    Vincular una gestión registrada
                                </h3>
                            </div>
                        </div>

                        <p>
                            Puedes reutilizar una gestión del mismo
                            tipo en varias tareas de esta operación.
                        </p>

                        <div class="lista-gestiones-existentes">
                            @foreach (
                                $gestionesCompatibles
                                as $gestionCompatible
                            )
                                <article>
                                    <div>
                                        <strong>
                                            {{ $gestionCompatible->nombre }}
                                        </strong>

                                        <span>
                                            {{ $gestionCompatible->proveedor }}
                                        </span>

                                        @if (
                                            $gestionCompatible
                                                ->referencia_confirmacion
                                        )
                                            <small>
                                                {{
                                                    $gestionCompatible
                                                        ->referencia_confirmacion
                                                }}
                                            </small>
                                        @endif
                                    </div>

                                    <form
                                        method="POST"
                                        action="{{ route(
                                            'operaciones.tareas.gestiones.vincular',
                                            [
                                                'operacion' =>
                                                    $operacion->id,

                                                'tarea' =>
                                                    $tarea->id,

                                                'gestion' =>
                                                    $gestionCompatible->id,
                                            ]
                                        ) }}"
                                    >
                                        @csrf

                                        <button
                                            type="submit"
                                            class="btn-vincular-gestion"
                                        >
                                            <i class="bi bi-link"></i>
                                            Vincular
                                        </button>
                                    </form>
                                </article>
                            @endforeach
                        </div>
                    </section>

                    <hr>
                @endif

                <form
                    id="{{ $formularioId }}"
                    method="POST"
                    action="{{ $gestionActual
                        ? route(
                            'operaciones.gestiones.update',
                            $gestionActual->id
                        )
                        : route(
                            'operaciones.tareas.gestiones.store',
                            [
                                'operacion' =>
                                    $operacion->id,

                                'tarea' =>
                                    $tarea->id,
                            ]
                        ) }}"
                    enctype="multipart/form-data"
                    class="formulario-gestion-contextual"
                    @if (in_array($tipoGestion, [GestionOperativa::TIPO_ALIMENTACION, GestionOperativa::TIPO_TREN], true))
                        data-fecha-paquete-inicio="{{ $reserva->destino?->fecha_salida?->format('Y-m-d') }}"
                        data-fecha-paquete-fin="{{ $reserva->destino?->fecha_regreso?->format('Y-m-d') }}"
                    @endif
                    @if ($tipoGestion === GestionOperativa::TIPO_ALIMENTACION)
                        data-validacion-alimentacion="true"
                    @elseif ($tipoGestion === GestionOperativa::TIPO_TREN)
                        data-validacion-tren="true"
                        data-reabrir-validacion="{{ $usarDatosAnteriores ? 'true' : 'false' }}"
                    @endif
                >
                    @csrf

                    @if ($gestionActual)
                        @method('PUT')
                    @endif

                    <input
                        type="hidden"
                        name="tipo"
                        value="{{ $tipoGestion }}"
                    >

                    <input
                        type="hidden"
                        name="contexto_tarea_id"
                        value="{{ $tarea->id }}"
                    >

                    <section class="seccion-formulario-gestion">
                        <div class="titulo-seccion-gestion">
                            <div>
                                <span>Servicio</span>
                                <h3>Información principal</h3>
                            </div>
                        </div>

                        <div class="campos-gestion-contextual">
                            <div class="campo-gestion campo-gestion-amplio">
                                <label
                                    for="gestionNombre{{ $tarea->id }}"
                                >
                                    Nombre del servicio *
                                </label>

                                <input
                                    id="gestionNombre{{ $tarea->id }}"
                                    type="text"
                                    name="nombre"
                                    maxlength="180"
                                    required
                                    value="{{ $valorFormulario(
                                        'nombre',
                                        $gestionActual?->nombre
                                            ?? $tarea->nombre
                                    ) }}"
                                >
                            </div>

                            <div class="campo-gestion">
                                <label
                                    for="gestionProveedor{{ $tarea->id }}"
                                >
                                    Proveedor *
                                </label>

                                <input
                                    id="gestionProveedor{{ $tarea->id }}"
                                    type="text"
                                    name="proveedor"
                                    maxlength="150"
                                    required
                                    value="{{ $valorFormulario(
                                        'proveedor',
                                        $gestionActual?->proveedor
                                    ) }}"
                                >
                            </div>

                            <div class="campo-gestion">
                                <label
                                    for="gestionContacto{{ $tarea->id }}"
                                >
                                    Persona de contacto
                                </label>

                                <input
                                    id="gestionContacto{{ $tarea->id }}"
                                    type="text"
                                    name="contacto"
                                    maxlength="150"
                                    value="{{ $valorFormulario(
                                        'contacto',
                                        $gestionActual?->contacto
                                    ) }}"
                                >
                            </div>

                            <div class="campo-gestion">
                                <label
                                    for="gestionTelefono{{ $tarea->id }}"
                                >
                                    Teléfono
                                </label>

                                <input
                                    id="gestionTelefono{{ $tarea->id }}"
                                    type="text"
                                    name="telefono"
                                    maxlength="30"
                                    value="{{ $valorFormulario(
                                        'telefono',
                                        $gestionActual?->telefono
                                    ) }}"
                                >
                            </div>

                            <div class="campo-gestion">
                                <label
                                    for="gestionCorreo{{ $tarea->id }}"
                                >
                                    Correo electrónico
                                </label>

                                <input
                                    id="gestionCorreo{{ $tarea->id }}"
                                    type="email"
                                    name="correo"
                                    maxlength="150"
                                    value="{{ $valorFormulario(
                                        'correo',
                                        $gestionActual?->correo
                                    ) }}"
                                >
                            </div>
                        </div>
                    </section>

                    <section class="seccion-formulario-gestion">
                        <div class="titulo-seccion-gestion">
                            <div>
                                <span>Programación</span>
                                <h3>Fecha, horario y ubicación</h3>
                            </div>
                        </div>

                        <div class="campos-gestion-contextual">
                            <div class="campo-gestion">
                                <label
                                    for="gestionInicio{{ $tarea->id }}"
                                >
                                    Fecha y hora de inicio *
                                </label>

                                <input
                                    id="gestionInicio{{ $tarea->id }}"
                                    type="datetime-local"
                                    name="fecha_hora_inicio"
                                    required
                                    value="{{ $valorFormulario(
                                        'fecha_hora_inicio',
                                        $fechaHoraInicioSugerida
                                    ) }}"
                                >
                            </div>

                            <div class="campo-gestion">
                                <label
                                    for="gestionFin{{ $tarea->id }}"
                                >
                                    Fecha y hora de finalización
                                </label>

                                <input
                                    id="gestionFin{{ $tarea->id }}"
                                    type="datetime-local"
                                    name="fecha_hora_fin"
                                    value="{{ $valorFormulario(
                                        'fecha_hora_fin',
                                        $fechaHoraFinSugerida
                                    ) }}"
                                >
                            </div>

                            <div class="campo-gestion">
                                <label
                                    for="gestionOrigen{{ $tarea->id }}"
                                >
                                    Lugar de origen o recogida
                                    @if (
                                        in_array(
                                            $tipoGestion,
                                            [
                                                GestionOperativa::TIPO_TREN,
                                                GestionOperativa::TIPO_TRASLADO,
                                            ],
                                            true
                                        )
                                    )
                                        *
                                    @endif
                                </label>

                                <input
                                    id="gestionOrigen{{ $tarea->id }}"
                                    type="text"
                                    name="ubicacion_origen"
                                    maxlength="180"
                                    value="{{ $valorFormulario(
                                        'ubicacion_origen',
                                        $origenSugerido
                                    ) }}"
                                >
                            </div>

                            <div class="campo-gestion">
                                <label
                                    for="gestionDestino{{ $tarea->id }}"
                                >
                                    Destino
                                    @if (
                                        in_array(
                                            $tipoGestion,
                                            [
                                                GestionOperativa::TIPO_TREN,
                                                GestionOperativa::TIPO_TRASLADO,
                                            ],
                                            true
                                        )
                                    )
                                        *
                                    @endif
                                </label>

                                <input
                                    id="gestionDestino{{ $tarea->id }}"
                                    type="text"
                                    name="destino"
                                    maxlength="180"
                                    value="{{ $valorFormulario(
                                        'destino',
                                        $destinoSugerido
                                    ) }}"
                                >
                            </div>
                        </div>
                    </section>

                    @if (
                        $tipoGestion ===
                            GestionOperativa::TIPO_TREN
                    )
                        <section class="seccion-formulario-gestion">
                            <div class="titulo-seccion-gestion">
                                <div>
                                    <span>Tren</span>
                                    <h3>Información ferroviaria</h3>
                                </div>
                            </div>

                            <div class="campos-gestion-contextual">
                                <div class="campo-gestion">
                                    <label>
                                        Empresa ferroviaria *
                                    </label>

                                    <input
                                        type="text"
                                        name="datos_adicionales[empresa_ferroviaria]"
                                        minlength="2"
                                        maxlength="150"
                                        required
                                        value="{{ $valorFormulario(
                                            'datos_adicionales.empresa_ferroviaria',
                                            $datosAdicionales[
                                                'empresa_ferroviaria'
                                            ] ?? null
                                        ) }}"
                                    >
                                </div>

                                <div class="campo-gestion">
                                    <label>Ruta *</label>

                                    <input
                                        type="text"
                                        name="datos_adicionales[ruta]"
                                        minlength="3"
                                        maxlength="255"
                                        required
                                        value="{{ $valorFormulario(
                                            'datos_adicionales.ruta',
                                            $datosAdicionales['ruta']
                                                ?? $programacionActividad['ruta']
                                        ) }}"
                                    >
                                </div>

                                <div class="campo-gestion">
                                    <label>Clase</label>

                                    <input
                                        type="text"
                                        name="datos_adicionales[clase]"
                                        minlength="2"
                                        maxlength="100"
                                        value="{{ $valorFormulario(
                                            'datos_adicionales.clase',
                                            $datosAdicionales[
                                                'clase'
                                            ] ?? null
                                        ) }}"
                                    >
                                </div>
                            </div>
                        </section>
                    @elseif (
                        $tipoGestion ===
                            GestionOperativa::TIPO_TRASLADO
                    )
                        <section class="seccion-formulario-gestion">
                            <div class="titulo-seccion-gestion">
                                <div>
                                    <span>Transporte</span>
                                    <h3>Vehículo y conductor</h3>
                                </div>
                            </div>

                            <div class="campos-gestion-contextual">
                                <div class="campo-gestion">
                                    <label>Tipo de vehículo</label>

                                    <input
                                        type="text"
                                        name="datos_adicionales[tipo_vehiculo]"
                                        maxlength="100"
                                        value="{{ $valorFormulario(
                                            'datos_adicionales.tipo_vehiculo',
                                            $datosAdicionales[
                                                'tipo_vehiculo'
                                            ] ?? null
                                        ) }}"
                                    >
                                </div>

                                <div class="campo-gestion">
                                    <label>Conductor</label>

                                    <input
                                        type="text"
                                        name="datos_adicionales[conductor]"
                                        maxlength="150"
                                        value="{{ $valorFormulario(
                                            'datos_adicionales.conductor',
                                            $datosAdicionales[
                                                'conductor'
                                            ] ?? null
                                        ) }}"
                                    >
                                </div>

                                <div class="campo-gestion">
                                    <label>
                                        Teléfono del conductor
                                    </label>

                                    <input
                                        type="text"
                                        name="datos_adicionales[telefono_conductor]"
                                        maxlength="30"
                                        value="{{ $valorFormulario(
                                            'datos_adicionales.telefono_conductor',
                                            $datosAdicionales[
                                                'telefono_conductor'
                                            ] ?? null
                                        ) }}"
                                    >
                                </div>
                            </div>
                        </section>
                    @elseif (
                        $tipoGestion ===
                            GestionOperativa::TIPO_ENTRADA
                    )
                        <section class="seccion-formulario-gestion">
                            <div class="titulo-seccion-gestion">
                                <div>
                                    <span>Entrada</span>
                                    <h3>Atracción y acceso</h3>
                                </div>
                            </div>

                            <div class="campos-gestion-contextual">
                                <div class="campo-gestion">
                                    <label>Atracción o recinto</label>
                                    <input
                                        type="text"
                                        name="datos_adicionales[atraccion]"
                                        maxlength="180"
                                        value="{{ $valorFormulario(
                                            'datos_adicionales.atraccion',
                                            $datosAdicionales['atraccion']
                                                ?? $tarea->ubicacion
                                        ) }}"
                                    >
                                </div>
                                <div class="campo-gestion">
                                    <label>Franja o puerta de acceso</label>
                                    <input
                                        type="text"
                                        name="datos_adicionales[franja_acceso]"
                                        maxlength="150"
                                        value="{{ $valorFormulario(
                                            'datos_adicionales.franja_acceso',
                                            $datosAdicionales['franja_acceso'] ?? null
                                        ) }}"
                                    >
                                </div>
                                <div class="campo-gestion campo-gestion-amplio">
                                    <label>Detalle de la visita</label>
                                    <textarea name="datos_adicionales[descripcion_servicio]" rows="3" maxlength="2000">{{ $valorFormulario(
                                        'datos_adicionales.descripcion_servicio',
                                        $datosAdicionales['descripcion_servicio'] ?? $tarea->descripcion
                                    ) }}</textarea>
                                </div>
                            </div>
                        </section>
                    @elseif (
                        $tipoGestion ===
                            GestionOperativa::TIPO_ALIMENTACION
                    )
                        <section class="seccion-formulario-gestion">
                            <div class="titulo-seccion-gestion">
                                <div>
                                    <span>Alimentación</span>
                                    <h3>Menú y restricciones</h3>
                                </div>
                            </div>

                            <div class="campos-gestion-contextual">
                                <div class="campo-gestion">
                                    <label>Restaurante o establecimiento</label>
                                <input
                                    id="gestionRestaurante{{ $tarea->id }}"
                                    type="text"
                                    name="datos_adicionales[restaurante]"
                                    minlength="3"
                                        maxlength="180"
                                        value="{{ $valorFormulario(
                                            'datos_adicionales.restaurante',
                                            $datosAdicionales['restaurante']
                                                ?? $tarea->ubicacion
                                        ) }}"
                                    >
                                </div>
                                <div class="campo-gestion">
                                    <label>Tipo de menú</label>

                                    <input
                                        id="gestionTipoMenu{{ $tarea->id }}"
                                        type="text"
                                        name="datos_adicionales[tipo_menu]"
                                        minlength="3"
                                        maxlength="150"
                                        value="{{ $valorFormulario(
                                            'datos_adicionales.tipo_menu',
                                            $datosAdicionales[
                                                'tipo_menu'
                                            ] ?? null
                                        ) }}"
                                    >
                                </div>

                                <div class="campo-gestion campo-gestion-amplio">
                                    <label>
                                        Restricciones alimentarias
                                    </label>

                                    <textarea
                                        id="gestionRestricciones{{ $tarea->id }}"
                                        name="datos_adicionales[restricciones_alimentarias]"
                                        rows="3"
                                        minlength="3"
                                        maxlength="2000"
                                    >{{ $valorFormulario(
                                        'datos_adicionales.restricciones_alimentarias',
                                        $datosAdicionales[
                                            'restricciones_alimentarias'
                                        ] ?? null
                                    ) }}</textarea>
                                </div>
                            </div>
                        </section>
                    @elseif (
                        $tipoGestion ===
                            GestionOperativa::TIPO_ACTIVIDAD_RESERVADA
                    )
                        <section class="seccion-formulario-gestion">
                            <div class="titulo-seccion-gestion">
                                <div>
                                    <span>Actividad</span>
                                    <h3>Servicio y punto de encuentro</h3>
                                </div>
                            </div>
                            <div class="campos-gestion-contextual">
                                <div class="campo-gestion">
                                    <label>Punto de encuentro</label>
                                    <input
                                        type="text"
                                        name="datos_adicionales[punto_encuentro]"
                                        maxlength="180"
                                        value="{{ $valorFormulario(
                                            'datos_adicionales.punto_encuentro',
                                            $datosAdicionales['punto_encuentro']
                                                ?? $tarea->ubicacion
                                        ) }}"
                                    >
                                </div>
                                <div class="campo-gestion campo-gestion-amplio">
                                    <label>Descripción de la actividad</label>
                                    <textarea name="datos_adicionales[descripcion_servicio]" rows="3" maxlength="2000">{{ $valorFormulario(
                                        'datos_adicionales.descripcion_servicio',
                                        $datosAdicionales['descripcion_servicio'] ?? $tarea->descripcion
                                    ) }}</textarea>
                                </div>
                            </div>
                        </section>
                    @elseif (
                        $tipoGestion ===
                            GestionOperativa::TIPO_SEGURO
                    )
                        <section class="seccion-formulario-gestion">
                            <div class="titulo-seccion-gestion">
                                <div>
                                    <span>Seguro</span>
                                    <h3>Póliza y cobertura</h3>
                                </div>
                            </div>

                            <div class="campos-gestion-contextual">
                                <div class="campo-gestion">
                                    <label>Aseguradora</label>
                                    <input
                                        type="text"
                                        name="datos_adicionales[aseguradora]"
                                        maxlength="150"
                                        value="{{ $valorFormulario(
                                            'datos_adicionales.aseguradora',
                                            $datosAdicionales['aseguradora'] ?? null
                                        ) }}"
                                    >
                                </div>
                                <div class="campo-gestion">
                                    <label>Número de póliza</label>

                                    <input
                                        type="text"
                                        name="datos_adicionales[numero_poliza]"
                                        maxlength="150"
                                        value="{{ $valorFormulario(
                                            'datos_adicionales.numero_poliza',
                                            $datosAdicionales[
                                                'numero_poliza'
                                            ] ?? null
                                        ) }}"
                                    >
                                </div>

                                <div class="campo-gestion campo-gestion-amplio">
                                    <label>Cobertura</label>

                                    <textarea
                                        name="datos_adicionales[cobertura_seguro]"
                                        rows="3"
                                        maxlength="2000"
                                    >{{ $valorFormulario(
                                        'datos_adicionales.cobertura_seguro',
                                        $datosAdicionales[
                                            'cobertura_seguro'
                                        ] ?? null
                                    ) }}</textarea>
                                </div>
                            </div>
                        </section>
                    @else
                        <section class="seccion-formulario-gestion">
                            <div class="titulo-seccion-gestion">
                                <div>
                                    <span>Detalle</span>
                                    <h3>Información del servicio</h3>
                                </div>
                            </div>

                            <div class="campo-gestion">
                                <label>Descripción del servicio</label>

                                <textarea
                                    name="datos_adicionales[descripcion_servicio]"
                                    rows="3"
                                    maxlength="2000"
                                >{{ $valorFormulario(
                                    'datos_adicionales.descripcion_servicio',
                                    $datosAdicionales[
                                        'descripcion_servicio'
                                    ] ?? $tarea->descripcion
                                ) }}</textarea>
                            </div>
                        </section>
                    @endif

                    <section class="seccion-formulario-gestion">
                        <div class="titulo-seccion-gestion">
                            <div>
                                <span>Confirmación</span>
                                <h3>Estado y valores internos</h3>
                            </div>
                        </div>

                        <div class="campos-gestion-contextual">
                            <div class="campo-gestion">
                                <label>
                                    Cantidad de viajeros *
                                </label>

                                <input
                                    type="number"
                                    name="cantidad_viajeros"
                                    min="1"
                                    required
                                    value="{{ $valorFormulario(
                                        'cantidad_viajeros',
                                        $gestionActual?->cantidad_viajeros
                                            ?? $cantidadViajeros
                                    ) }}"
                                >
                            </div>

                            @if (
                                $tipoGestion ===
                                    GestionOperativa::TIPO_TRASLADO
                            )
                                <div class="campo-gestion">
                                    <label>
                                        Capacidad del vehículo *
                                    </label>

                                    <input
                                        type="number"
                                        name="capacidad"
                                        min="1"
                                        required
                                        value="{{ $valorFormulario(
                                            'capacidad',
                                            $gestionActual?->capacidad
                                                ?? $cantidadViajeros
                                        ) }}"
                                    >
                                </div>
                            @endif

                            <div class="campo-gestion">
                                <label>Estado *</label>

                                <select
                                    name="estado"
                                    required
                                >
                                    @foreach (
                                        [
                                            GestionOperativa::ESTADO_PENDIENTE =>
                                                'Pendiente',

                                            GestionOperativa::ESTADO_EN_PROCESO =>
                                                'En proceso',

                                            GestionOperativa::ESTADO_CONFIRMADO =>
                                                'Confirmado',

                                            GestionOperativa::ESTADO_CANCELADO =>
                                                'Cancelado',
                                        ]
                                        as $valorEstado => $nombreEstado
                                    )
                                        <option
                                            value="{{ $valorEstado }}"
                                            @selected(
                                                $valorFormulario(
                                                    'estado',
                                                    $gestionActual?->estado
                                                        ?? GestionOperativa::ESTADO_PENDIENTE
                                                ) === $valorEstado
                                            )
                                        >
                                            {{ $nombreEstado }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="campo-gestion">
                                <label>
                                    Referencia de confirmación
                                </label>

                                <input
                                    type="text"
                                    name="referencia_confirmacion"
                                    maxlength="150"
                                    value="{{ $valorFormulario(
                                        'referencia_confirmacion',
                                        $gestionActual
                                            ?->referencia_confirmacion
                                    ) }}"
                                >
                            </div>

                            <div class="campo-gestion">
                                <label>Costo total</label>

                                <input
                                    type="number"
                                    name="costo_total"
                                    min="0"
                                    step="0.01"
                                    value="{{ $valorFormulario(
                                        'costo_total',
                                        $gestionActual?->costo_total
                                    ) }}"
                                >
                            </div>

                            <div class="campo-gestion">
                                <label>Moneda *</label>

                                <select
                                    name="moneda"
                                    required
                                >
                                    @foreach (
                                        [
                                            'USD',
                                            'EUR',
                                            'PEN',
                                        ]
                                        as $moneda
                                    )
                                        <option
                                            value="{{ $moneda }}"
                                            @selected(
                                                $valorFormulario(
                                                    'moneda',
                                                    $gestionActual?->moneda
                                                        ?? $reserva->moneda
                                                        ?? 'USD'
                                                ) === $moneda
                                            )
                                        >
                                            {{ $moneda }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="campo-gestion campo-gestion-amplio">
                                <label>Comprobante</label>

                                <input
                                    type="file"
                                    name="archivo_comprobante"
                                    accept=".pdf,.jpg,.jpeg,.png,.webp"
                                >

                                <small>
                                    PDF, JPG, PNG o WEBP.
                                    Máximo 5 MB.
                                </small>

                                @if (
                                    $gestionActual
                                    && $gestionActual
                                        ->archivo_comprobante
                                )
                                    <a
                                        href="{{ asset(
                                            'storage/'
                                            . $gestionActual
                                                ->archivo_comprobante
                                        ) }}"
                                        target="_blank"
                                        rel="noopener"
                                    >
                                        <i class="bi bi-paperclip"></i>
                                        Ver comprobante actual
                                    </a>
                                @endif
                            </div>

                            <div class="campo-gestion campo-gestion-amplio">
                                <label>Observaciones</label>

                                <textarea
                                    name="observaciones"
                                    rows="4"
                                    maxlength="5000"
                                >{{ $valorFormulario(
                                    'observaciones',
                                    $gestionActual?->observaciones
                                        ?? $tarea->descripcion
                                ) }}</textarea>
                            </div>
                        </div>
                    </section>

                    @if ($requiereDetalleIndividualDisponible)
                        <section class="seccion-formulario-gestion">
                            <div class="titulo-seccion-gestion">
                                <div>
                                    <span>Viajeros</span>

                                    <h3>
                                        Documentos individuales
                                    </h3>
                                </div>
                            </div>

                            @if ($viajerosReserva->isEmpty())
                                <div class="alert alert-warning">
                                    Primero registra los integrantes
                                    del viaje antes de confirmar esta
                                    gestión.
                                </div>
                            @else
                                <div class="lista-viajeros-gestion">
                                    @foreach (
                                        $viajerosReserva
                                        as $indice => $viajero
                                    )
                                        @php
                                            $detalleActual =
                                                $detallesViajeros
                                                    ->firstWhere(
                                                        'viajero_reserva_id',
                                                        $viajero->id
                                                    );

                                            $prefijoViajero =
                                                "viajeros.$indice";

                                            $valorViajero =
                                                function (
                                                    string $campo,
                                                    mixed $predeterminado = null
                                                ) use (
                                                    $usarDatosAnteriores,
                                                    $prefijoViajero
                                                ) {
                                                    return $usarDatosAnteriores
                                                        ? old(
                                                            "$prefijoViajero.$campo",
                                                            $predeterminado
                                                        )
                                                        : $predeterminado;
                                                };
                                        @endphp

                                        <article class="viajero-gestion-contextual">
                                            <header>
                                                <div>
                                                    <strong>
                                                        {{ $viajero
                                                            ->nombre_completo }}
                                                    </strong>

                                                    <span>
                                                        {{
                                                            $viajero
                                                                ->documento_enmascarado
                                                        }}
                                                    </span>
                                                </div>
                                            </header>

                                            <input
                                                type="hidden"
                                                name="viajeros[{{ $indice }}][viajero_reserva_id]"
                                                value="{{ $viajero->id }}"
                                            >

                                            <div class="campos-viajero-gestion">
                                                <div class="campo-gestion">
                                                    <label>
                                                        Estado individual *
                                                    </label>

                                                    <select
                                                        name="viajeros[{{ $indice }}][estado]"
                                                        required
                                                    >
                                                        @foreach (
                                                            [
                                                                GestionOperativaViajero::ESTADO_PENDIENTE =>
                                                                    'Pendiente',

                                                                GestionOperativaViajero::ESTADO_CONFIRMADO =>
                                                                    'Confirmado',

                                                                GestionOperativaViajero::ESTADO_CANCELADO =>
                                                                    'Cancelado',
                                                            ]
                                                            as $valorEstado =>
                                                                $nombreEstado
                                                        )
                                                            <option
                                                                value="{{ $valorEstado }}"
                                                                @selected(
                                                                    $valorViajero(
                                                                        'estado',
                                                                        $detalleActual?->estado
                                                                            ?? GestionOperativaViajero::ESTADO_PENDIENTE
                                                                    ) === $valorEstado
                                                                )
                                                            >
                                                                {{ $nombreEstado }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="campo-gestion">
                                                    <label>
                                                        Número de documento
                                                        o boleto
                                                    </label>

                                                    <input
                                                        type="text"
                                                        name="viajeros[{{ $indice }}][numero_documento]"
                                                        maxlength="150"
                                                        value="{{ $valorViajero(
                                                            'numero_documento',
                                                            $detalleActual
                                                                ?->numero_documento
                                                        ) }}"
                                                    >
                                                </div>

                                                <div class="campo-gestion">
                                                    <label>
                                                        Referencia individual
                                                    </label>

                                                    <input
                                                        type="text"
                                                        name="viajeros[{{ $indice }}][referencia_individual]"
                                                        maxlength="150"
                                                        value="{{ $valorViajero(
                                                            'referencia_individual',
                                                            $detalleActual
                                                                ?->referencia_individual
                                                        ) }}"
                                                    >
                                                </div>

                                                @if (
                                                    $tipoGestion ===
                                                        GestionOperativa::TIPO_TREN
                                                )
                                                    <div class="campo-gestion">
                                                        <label>Asiento</label>

                                                        <input
                                                            type="text"
                                                            name="viajeros[{{ $indice }}][asiento]"
                                                            maxlength="30"
                                                            value="{{ $valorViajero(
                                                                'asiento',
                                                                $detalleActual?->asiento
                                                            ) }}"
                                                        >
                                                    </div>
                                                @endif

                                                <div class="campo-gestion campo-gestion-amplio">
                                                    <label>
                                                        Restricciones
                                                    </label>

                                                    <textarea
                                                        name="viajeros[{{ $indice }}][restricciones]"
                                                        rows="2"
                                                        maxlength="2000"
                                                    >{{ $valorViajero(
                                                        'restricciones',
                                                        $detalleActual
                                                            ?->restricciones
                                                    ) }}</textarea>
                                                </div>

                                                <div class="campo-gestion campo-gestion-amplio">
                                                    <label>
                                                        Observaciones
                                                    </label>

                                                    <textarea
                                                        name="viajeros[{{ $indice }}][observaciones]"
                                                        rows="2"
                                                        maxlength="2000"
                                                    >{{ $valorViajero(
                                                        'observaciones',
                                                        $detalleActual
                                                            ?->observaciones
                                                    ) }}</textarea>
                                                </div>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            @endif
                        </section>
                    @endif
                </form>
            </div>

            <div class="modal-footer modal-footer-gestion-contextual">
                @if ($gestionActual)
                    <div class="acciones-secundarias-gestion">
                        <form
                            method="POST"
                            action="{{ route(
                                'operaciones.tareas.gestiones.desvincular',
                                [
                                    'operacion' =>
                                        $operacion->id,

                                    'tarea' =>
                                        $tarea->id,
                                ]
                            ) }}"
                        >
                            @csrf
                            @method('DELETE')

                            <button
                                type="submit"
                                class="btn btn-outline-secondary"
                            >
                                <i class="bi bi-link-45deg"></i>
                                Desvincular
                            </button>
                        </form>

                        <form
                            method="POST"
                            action="{{ route(
                                'operaciones.gestiones.destroy',
                                $gestionActual->id
                            ) }}"
                            data-tipo="gestión operativa"
                        >
                            @csrf
                            @method('DELETE')

                            <button
                                type="submit"
                                class="
                                    btn
                                    btn-outline-danger
                                    btnEliminarExpediente
                                "
                            >
                                <i class="bi bi-trash"></i>
                                Eliminar gestión
                            </button>
                        </form>
                    </div>
                @endif

                <div class="acciones-principales-gestion">
                    <button
                        type="button"
                        class="btn btn-light"
                        data-bs-dismiss="modal"
                    >
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        form="{{ $formularioId }}"
                        class="btn btn-primary"
                        @disabled(
                            $requiereDetalleIndividualDisponible
                            && $viajerosReserva->isEmpty()
                        )
                    >
                        <i class="bi bi-check2"></i>

                        {{ $gestionActual
                            ? 'Guardar cambios'
                            : 'Registrar gestión' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
