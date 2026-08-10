@extends('layouts.main')

@section('titulo', $titulo)

@section('content')
<link
    rel="stylesheet"
    href="{{ asset('css/operacion-viaje.css') }}?v={{ filemtime(public_path('css/operacion-viaje.css')) }}"
>

@php
    $editable =
        !$operacion->fueNotificada() &&
        !$reserva->estaCancelada();

    $nombreReserva = $reserva->esGrupal()
        ? (
            $reserva->grupo?->nombre_grupo
            ?? 'Grupo no disponible'
        )
        : (
            $reserva->cliente?->nombre_completo
            ?? 'Cliente no disponible'
        );

    $nombreEstado = match (
        $operacion->estado
    ) {
        'pendiente' => 'Pendiente',
        'preparacion' => 'En preparación',
        'completo' => 'Completo',
        'notificado' => 'Notificado',
        default => 'Sin información',
    };
@endphp

<main id="main" class="main pagina-operacion-viaje">
    <header class="expediente-encabezado">
        <div>
            <a
                href="{{ route('operaciones.index') }}"
                class="volver-operaciones"
            >
                <i class="bi bi-arrow-left"></i>
                Volver a preparación de viajes
            </a>

            <span class="expediente-modulo">
                Expediente operativo
            </span>

            <h1>{{ $reserva->codigo_reserva }}</h1>

            <p>
                {{ $nombreReserva }} ·
                {{ $reserva->destino?->nombre_paquete }}
            </p>
        </div>

        <span
            class="estado-expediente estado-{{ $operacion->estado }}"
        >
            {{ $nombreEstado }}
        </span>
    </header>

    <section class="bloque-expediente">
        <div class="bloque-expediente-titulo">
            <div>
                <span>Personas incluidas</span>
                <h2>Integrantes del viaje</h2>
            </div>
            @if ($editable && $composicionFamiliar)
                <button type="button" class="btn-agregar-expediente" id="btnNuevoViajero">
                    <i class="bi bi-person-plus"></i> Agregar acompañante
                </button>
            @endif
        </div>

        <div class="lista-viajeros-expediente">
            @foreach ($progreso['personas'] as $persona)
                <article>
                    <div class="viajero-inicial">
                        {{ mb_strtoupper(mb_substr($persona['nombre'], 0, 1)) }}
                    </div>

                    <div>
                        <strong>{{ $persona['nombre'] }}</strong>
                        <small>{{ ucfirst(str_replace('_', ' ', $persona['categoria'] ?? 'Sin categoría')) }} · {{ $persona['edad'] }} años</small>
                        <small>{{ $persona['tipo_documento'] ? ucfirst($persona['tipo_documento']) : 'Documento' }}: {{ $persona['documento_enmascarado'] }}</small>
                        @if ($persona['es_titular'])<small><strong>Titular</strong></small>@endif
                        @if (!$persona['requiere_boleto'])
                            <small class="etiqueta-infante-operacion">Infante — no requiere boleto ni habitación</small>
                            @php
                                $registroHistoricoInfante = $operacion->vuelos->contains(fn ($vuelo) =>
                                    $vuelo->boletos->contains(fn ($boleto) =>
                                        $persona['tipo'] === 'viajero'
                                            ? (int) $boleto->viajero_reserva_id === (int) $persona['id']
                                            : (int) $boleto->cliente_id === (int) $persona['id']
                                    )
                                ) || $operacion->alojamientos->contains(fn ($alojamiento) =>
                                    $alojamiento->asignacionesHabitacion->contains(fn ($asignacion) =>
                                        $persona['tipo'] === 'viajero'
                                            ? (int) $asignacion->viajero_reserva_id === (int) $persona['id']
                                            : (int) $asignacion->cliente_id === (int) $persona['id']
                                    )
                                );
                            @endphp
                            @if ($registroHistoricoInfante)
                                <small class="advertencia-registro-historico">Existe un boleto o asignación histórica; se conserva sin modificar.</small>
                            @endif
                        @endif
                    </div>
                    @if ($editable && $persona['tipo'] === 'viajero' && !$persona['es_titular'])
                        <button type="button" class="btn-gestionar-boleto btnEditarViajero"
                            data-viajero-id="{{ $persona['id'] }}">Editar</button>
                    @endif

                </article>
            @endforeach
        </div>
    </section>

    <section class="resumen-expediente">
        <article>
            <span>Fecha del viaje</span>

            <strong>
                {{ $reserva->fecha_viaje
                    ?->format('d/m/Y')
                    ?? 'Sin fecha' }}
            </strong>
        </article>

        <article>
            <span>Viajeros</span>
            <strong>
                {{ $totalViajerosEsperados }}
            </strong>
        </article>

        <article>
            <span>Vuelos</span>
            <strong>{{ $operacion->vuelos->count() }}</strong>
        </article>

        <article>
            <span>Alojamientos</span>
            <strong>{{ $operacion->alojamientos->count() }}</strong>
        </article>

        <article>
            <span>Guías</span>
            <strong>{{ $operacion->guias->count() }}</strong>
        </article>
    </section>

    <section class="bloque-expediente resumen-progreso-operacion">
        <div class="bloque-expediente-titulo">
            <div><span>Seguimiento</span><h2>Resumen de progreso</h2></div>
            <strong class="porcentaje-progreso">{{ $progreso['porcentaje_general'] }}%</strong>
        </div>
        <div class="metricas-progreso">
            @foreach ([
                'Viajeros identificados' => $progreso['viajeros_identificados'],
                'Documentos registrados' => $progreso['documentos_registrados'],
                'Boletos emitidos' => $progreso['boletos_emitidos'],
                'Asientos asignados' => $progreso['asientos_asignados'],
                'Viajeros con habitación' => $progreso['viajeros_con_habitacion'],
            ] as $etiqueta => $metrica)
                @if (($metrica['aplica'] ?? true))
                    <article><span>{{ $etiqueta }}</span>
                        <strong>{{ $metrica['actual'] }} de {{ $metrica['total'] }}</strong>
                    </article>
                @endif
            @endforeach
            <article><span>Estado del pago</span><strong>{{ ucfirst($progreso['estado_pago']) }}</strong></article>
            <article><span>Saldo pendiente</span><strong>{{ $reserva->moneda }} {{ number_format($progreso['saldo_pendiente'], 2) }}</strong></article>
        </div>
        @if ($progreso['motivos_pendientes'])
            <ul class="motivos-progreso">
                @foreach ($progreso['motivos_pendientes'] as $motivo)<li>{{ $motivo }}</li>@endforeach
            </ul>
        @endif
    </section>
    @include(
        'modules.operaciones.partials.tareas-itinerario'
    )
    @if ($composicionFamiliar)
        <div class="aviso-expediente-bloqueado">
            <i class="bi bi-info-circle"></i>
            <span>
                Esta familia incluye
                <strong>{{ $composicionFamiliar['cantidad_infantes'] }}</strong>
                infantes,
                <strong>{{ $composicionFamiliar['cantidad_ninos'] }}</strong>
                niños,
                <strong>{{ $composicionFamiliar['cantidad_adultos'] }}</strong>
                adultos y
                <strong>{{ $composicionFamiliar['cantidad_adultos_mayores'] }}</strong>
                adultos mayores. Los datos personales de los acompañantes
                todavía deben recopilarse antes de emitir sus boletos.
            </span>
        </div>
        @if ($editable && !$reserva->viajerosReserva->contains('es_titular', true))
            <form method="POST" action="{{ route('operaciones.viajeros.titular', $reserva) }}"
                  class="aviso-expediente-bloqueado">
                @csrf
                <span>Esta reserva todavía no tiene al titular inicializado en el seguimiento.</span>
                <button type="submit" class="btn-agregar-expediente">Inicializar integrantes del viaje</button>
            </form>
        @endif
    @endif

    @if (!$editable)
        <div class="aviso-expediente-bloqueado">
            <i class="bi bi-lock"></i>

            <span>
                Este expediente no admite modificaciones porque fue
                notificado o la reserva está cancelada.
            </span>
        </div>
    @endif

    <section class="bloque-expediente">
        <div class="bloque-expediente-titulo">
            <div>
                <span>Información general</span>
                <h2>Estado de preparación</h2>
            </div>
        </div>

        <form
            id="formularioEstadoOperacion"
            method="POST"
            action="{{ route(
                'operaciones.update',
                $operacion->id
            ) }}"
            class="formulario-estado-operacion"
            novalidate
        >
            @csrf
            @method('PUT')

            <div class="campo-operacion">
                <label for="estado">
                    Estado del expediente
                </label>

                <select
                    id="estado"
                    name="estado"
                    @disabled(!$editable)
                >
                    <option
                        value="pendiente"
                        @selected(
                            $operacion->estado ===
                            'pendiente'
                        )
                    >
                        Pendiente
                    </option>

                    <option
                        value="preparacion"
                        @selected(
                            $operacion->estado ===
                            'preparacion'
                        )
                    >
                        En preparación
                    </option>

                    <option
                        value="completo"
                        @selected(
                            $operacion->estado ===
                            'completo'
                        )
                    >
                        Completo
                    </option>

                    @if (
                        $operacion->estado ===
                        'notificado'
                    )
                        <option
                            value="notificado"
                            selected
                        >
                            Notificado
                        </option>
                    @endif
                </select>
            </div>

            <div class="campo-operacion campo-observaciones">
                <label for="observaciones">
                    Observaciones internas
                </label>

                <textarea
                    id="observaciones"
                    name="observaciones"
                    maxlength="2000"
                    rows="3"
                    placeholder="Información pendiente, coordinaciones o indicaciones internas..."
                    @disabled(!$editable)
                >{{ old(
                    'observaciones',
                    $operacion->observaciones
                ) }}</textarea>
            </div>

            @if ($editable)
                <button
                    type="submit"
                    class="btn-guardar-operacion"
                >
                    <i class="bi bi-check-lg"></i>
                    Guardar estado
                </button>
            @endif
        </form>
    </section>

    {{--
        Los módulos generales de vuelos, alojamientos y guías se conservan
        en el backend por compatibilidad histórica. Su gestión visual se
        realiza ahora desde cada tarea contextual del itinerario.
    --}}
    @if (false)
    <section
        id="vuelos"
        class="bloque-expediente"
    >
        <div class="bloque-expediente-titulo">
            <div>
                <span>Transporte aéreo</span>
                <h2>Vuelos y boletos</h2>
            </div>

            @if ($editable)
                <button
                    type="button"
                    class="btn-agregar-expediente"
                    id="btnNuevoVuelo"
                >
                    <i class="bi bi-airplane"></i>
                    Agregar vuelo
                </button>
            @endif
        </div>

        <div class="lista-elementos-expediente">
            @forelse ($operacion->vuelos as $vuelo)
                <article class="elemento-expediente">
                    <div class="elemento-expediente-cabecera">
                        <div>
                            <span class="etiqueta-tramo">
                                {{ ucfirst(
                                    $vuelo->tipo_tramo
                                ) }}
                            </span>

                            <h3>
                                {{ $vuelo->ciudad_origen }}
                                <i class="bi bi-arrow-right"></i>
                                {{ $vuelo->ciudad_destino }}
                            </h3>

                            <p>
                                {{ $vuelo->aerolinea }}
                                @if ($vuelo->numero_vuelo)
                                    · {{ $vuelo->numero_vuelo }}
                                @endif
                            </p>
                        </div>

                        @if ($editable)
                            <div class="acciones-elemento">
                                <button
                                    type="button"
                                    class="btn-editar-elemento btnEditarVuelo"
                                    data-id="{{ $vuelo->id }}"
                                    title="Editar vuelo"
                                >
                                    <i class="bi bi-pencil-square"></i>
                                </button>

                                <form
                                    method="POST"
                                    action="{{ route(
                                        'operaciones.vuelos.destroy',
                                        $vuelo->id
                                    ) }}"
                                    class="formulario-eliminar-expediente"
                                    data-tipo="vuelo"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="button"
                                        class="btn-eliminar-elemento btnEliminarExpediente"
                                        title="Eliminar vuelo"
                                    >
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>

                    <div class="datos-elemento">
                        <div>
                            <span>Salida</span>
                            <strong>
                                {{ $vuelo->fecha_hora_salida
                                    ->format('d/m/Y H:i') }}
                            </strong>
                            <small>
                                {{ $vuelo->aeropuerto_origen
                                    ?: 'Aeropuerto no registrado' }}
                            </small>
                        </div>

                        <div>
                            <span>Llegada</span>
                            <strong>
                                {{ $vuelo->fecha_hora_llegada
                                    ->format('d/m/Y H:i') }}
                            </strong>
                            <small>
                                {{ $vuelo->aeropuerto_destino
                                    ?: 'Aeropuerto no registrado' }}
                            </small>
                        </div>

                        <div>
                            <span>Localizador</span>
                            <strong>
                                {{ $vuelo->localizador_reserva
                                    ?: 'Pendiente' }}
                            </strong>
                        </div>

                        <div>
                            <span>Equipaje</span>
                            <strong>
                                {{ $vuelo->equipaje_incluido
                                    ?: 'Sin información' }}
                            </strong>
                        </div>
                    </div>

                    <div class="boletos-vuelo">
                        <div class="boletos-titulo">
                            <h4>Boletos de viajeros</h4>
                            <span>
                                {{ $progreso['boletos_por_vuelo'][$vuelo->id]['actual'] }}
                                de {{ $progreso['boletos_por_vuelo'][$vuelo->id]['total'] }} boletos emitidos
                            </span>
                        </div>

                        <div class="tabla-boletos-responsive">
                            <table class="tabla-boletos">
                                <thead>
                                    <tr>
                                        <th>Viajero</th>
                                        <th>Número de boleto</th>
                                        <th>Asiento</th>
                                        <th>Estado</th>
                                        <th>Documento</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($progreso['personas'] as $persona)
                                        @php
                                            $boleto = $vuelo
                                                ->boletos
                                                ->first(function ($item) use ($persona) {
                                                    return $persona['tipo'] === 'viajero'
                                                        ? (int) $item->viajero_reserva_id === (int) $persona['id']
                                                        : (int) $item->cliente_id === (int) $persona['id'];
                                                });
                                        @endphp

                                        <tr>
                                            <td>
                                                {{ $persona['nombre'] }}
                                            </td>

                                            <td>
                                                {{ !$persona['requiere_boleto']
                                                    ? 'No requiere boleto'
                                                    : ($boleto
                                                    ?->numero_boleto
                                                    ?? 'Pendiente') }}
                                            </td>

                                            <td>
                                                {{ $boleto?->asiento
                                                    ?? '—' }}
                                            </td>

                                            <td>
                                                <span
                                                    class="estado-boleto estado-{{ $boleto?->estado_emision ?? 'pendiente' }}"
                                                >
                                                    {{ ucfirst(
                                                        $boleto
                                                            ?->estado_emision
                                                        ?? 'pendiente'
                                                    ) }}
                                                </span>
                                            </td>

                                            <td>
                                                @if (
                                                    $boleto
                                                    ?->archivo_boleto
                                                )
                                                    <a
                                                        href="{{ asset(
                                                            'storage/' .
                                                            $boleto
                                                                ->archivo_boleto
                                                        ) }}"
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                    >
                                                        Ver archivo
                                                    </a>
                                                @else
                                                    —
                                                @endif
                                            </td>

                                            <td>
                                                @if ($editable && $persona['requiere_boleto'])
                                                    <button
                                                        type="button"
                                                        class="btn-gestionar-boleto btnGestionarBoleto"
                                                        data-vuelo-id="{{ $vuelo->id }}"
                                                        data-persona-id="{{ $persona['id'] }}"
                                                        data-persona-tipo="{{ $persona['tipo'] }}"
                                                    >
                                                        {{ $boleto
                                                            ? 'Editar'
                                                            : 'Asignar' }}
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </article>
            @empty
                <div class="sin-elementos-expediente">
                    <i class="bi bi-airplane"></i>
                    <strong>No hay vuelos registrados</strong>
                    <span>Agrega los vuelos de ida, regreso o conexión.</span>
                </div>
            @endforelse
        </div>
    </section>

    <section
        id="alojamientos"
        class="bloque-expediente"
    >
        <div class="bloque-expediente-titulo">
            <div>
                <span>Hospedaje</span>
                <h2>Alojamientos</h2>
            </div>

            @if ($editable)
                <button
                    type="button"
                    class="btn-agregar-expediente"
                    id="btnNuevoAlojamiento"
                >
                    <i class="bi bi-building"></i>
                    Agregar alojamiento
                </button>
            @endif
        </div>

        <div class="lista-elementos-expediente">
            @forelse (
                $operacion->alojamientos
                as $alojamiento
            )
                <article class="elemento-expediente">
                    <div class="elemento-expediente-cabecera">
                        <div>
                            <span class="etiqueta-tramo">
                                {{ ucfirst(
                                    $alojamiento->estado
                                ) }}
                            </span>

                            <h3>
                                {{ $alojamiento->nombre_hotel }}
                            </h3>

                            <p>
                                {{ $alojamiento->ciudad }}
                                @if ($alojamiento->pais)
                                    · {{ $alojamiento->pais }}
                                @endif
                            </p>
                        </div>

                        @if ($editable)
                            <div class="acciones-elemento">
                                <button
                                    type="button"
                                    class="btn-editar-elemento btnEditarAlojamiento"
                                    data-id="{{ $alojamiento->id }}"
                                >
                                    <i class="bi bi-pencil-square"></i>
                                </button>

                                <form
                                    method="POST"
                                    action="{{ route(
                                        'operaciones.alojamientos.destroy',
                                        $alojamiento->id
                                    ) }}"
                                    class="formulario-eliminar-expediente"
                                    data-tipo="alojamiento"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="button"
                                        class="btn-eliminar-elemento btnEliminarExpediente"
                                    >
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>

                    <div class="datos-elemento">
                        <div>
                            <span>Entrada</span>
                            <strong>
                                {{ $alojamiento
                                    ->fecha_hora_entrada
                                    ->format('d/m/Y H:i') }}
                            </strong>
                        </div>

                        <div>
                            <span>Salida</span>
                            <strong>
                                {{ $alojamiento
                                    ->fecha_hora_salida
                                    ->format('d/m/Y H:i') }}
                            </strong>
                        </div>

                        <div>
                            <span>Habitaciones</span>
                            <strong>
                                {{ $alojamiento
                                    ->cantidad_habitaciones }}
                                ·
                                {{ $alojamiento
                                    ->tipo_habitacion
                                    ?: 'Tipo no registrado' }}
                            </strong>
                        </div>

                        <div>
                            <span>Confirmación</span>
                            <strong>
                                {{ $alojamiento
                                    ->codigo_confirmacion
                                    ?: 'Pendiente' }}
                            </strong>
                        </div>
                    </div>

                    @if (
                        $alojamiento
                            ->distribucion_habitaciones
                    )
                        <p class="detalle-adicional">
                            <strong>
                                Distribución:
                            </strong>

                            {{ $alojamiento
                                ->distribucion_habitaciones }}
                        </p>
                    @endif

                    <div class="distribucion-habitaciones">
                        <div class="boletos-titulo">
                            <h4>Distribución de habitaciones</h4>
                            <span>{{ $progreso['habitaciones_por_alojamiento'][$alojamiento->id]['actual'] }} de {{ $progreso['habitaciones_por_alojamiento'][$alojamiento->id]['total'] }} viajeros con habitación</span>
                        </div>
                        @php
                            $sinHabitacion = $progreso['personas']->filter(fn ($persona) => $persona['requiere_habitacion'])->reject(fn ($persona) =>
                                $alojamiento->asignacionesHabitacion->contains(
                                    $progreso['familia_nueva'] ? 'viajero_reserva_id' : 'cliente_id',
                                    $persona['id']
                                )
                            );
                        @endphp
                        <p class="detalle-adicional"><strong>Sin habitación:</strong>
                            {{ $sinHabitacion->isEmpty() ? 'Ninguno' : $sinHabitacion->pluck('nombre')->join(', ') }}
                        </p>
                        @foreach ($alojamiento->habitaciones as $habitacion)
                            <article class="habitacion-operativa">
                                <div><strong>{{ ucfirst($habitacion->tipo) }} {{ $habitacion->referencia ? '· '.$habitacion->referencia : '' }}</strong>
                                    <small>{{ $habitacion->asignaciones->count() }} de {{ $habitacion->capacidad }} ocupantes</small></div>
                                <div class="ocupantes-habitacion">
                                    @foreach ($habitacion->asignaciones as $asignacion)
                                        @php $ocupante = $asignacion->viajeroReserva ?: $asignacion->cliente; @endphp
                                        <span>{{ $ocupante?->nombre_completo }}
                                            @if ($editable)<form method="POST" action="{{ route('operaciones.habitaciones.retirar', $asignacion) }}">@csrf @method('DELETE')<button type="submit" aria-label="Retirar">×</button></form>@endif
                                        </span>
                                    @endforeach
                                </div>
                                @if ($editable && $habitacion->asignaciones->count() < $habitacion->capacidad)
                                    <form method="POST" action="{{ route('operaciones.habitaciones.asignar', $habitacion) }}" class="formulario-asignacion-habitacion">
                                        @csrf
                                        <select name="{{ $progreso['familia_nueva'] ? 'viajero_reserva_id' : 'cliente_id' }}" required>
                                            <option value="">Seleccionar viajero</option>
                                            @foreach ($progreso['personas'] as $persona)
                                                @php $yaAsignado = $alojamiento->asignacionesHabitacion->contains($progreso['familia_nueva'] ? 'viajero_reserva_id' : 'cliente_id', $persona['id']); @endphp
                                                @if ($persona['requiere_habitacion'] && !$yaAsignado)<option value="{{ $persona['id'] }}">{{ $persona['nombre'] }}</option>@endif
                                            @endforeach
                                        </select><button type="submit">Asignar</button>
                                    </form>
                                @endif
                            </article>
                        @endforeach
                        @if ($editable)
                            <form method="POST" action="{{ route('operaciones.habitaciones.store', $alojamiento) }}" class="formulario-nueva-habitacion">
                                @csrf
                                <select name="tipo" required><option value="">Tipo de habitación</option>
                                    @foreach (\App\Models\HabitacionAlojamiento::CAPACIDADES as $tipo => $capacidad)<option value="{{ $tipo }}">{{ ucfirst($tipo) }} ({{ $capacidad }})</option>@endforeach
                                </select>
                                <input name="referencia" maxlength="100" placeholder="Número o referencia (opcional)">
                                <button type="submit">Agregar habitación</button>
                            </form>
                        @endif
                    </div>
                </article>
            @empty
                <div class="sin-elementos-expediente">
                    <i class="bi bi-building"></i>
                    <strong>No hay alojamientos registrados</strong>
                    <span>Agrega hoteles y datos de las habitaciones.</span>
                </div>
            @endforelse
        </div>
    </section>

    <section
        id="guias"
        class="bloque-expediente"
    >
        <div class="bloque-expediente-titulo">
            <div>
                <span>Acompañamiento</span>
                <h2>Guías</h2>
            </div>

            @if ($editable)
                <button
                    type="button"
                    class="btn-agregar-expediente"
                    id="btnNuevoGuia"
                >
                    <i class="bi bi-person-badge"></i>
                    Agregar guía
                </button>
            @endif
        </div>

        <div class="lista-elementos-expediente">
            @forelse ($operacion->guias as $guia)
                <article class="elemento-expediente">
                    <div class="elemento-expediente-cabecera">
                        <div>
                            <span class="etiqueta-tramo">
                                {{ ucfirst($guia->estado) }}
                            </span>

                            <h3>
                                {{ $guia->nombre_completo }}
                            </h3>

                            <p>
                                {{ $guia->empresa
                                    ?: 'Guía independiente' }}
                            </p>
                        </div>

                        @if ($editable)
                            <div class="acciones-elemento">
                                <button
                                    type="button"
                                    class="btn-editar-elemento btnEditarGuia"
                                    data-id="{{ $guia->id }}"
                                >
                                    <i class="bi bi-pencil-square"></i>
                                </button>

                                <form
                                    method="POST"
                                    action="{{ route(
                                        'operaciones.guias.destroy',
                                        $guia->id
                                    ) }}"
                                    class="formulario-eliminar-expediente"
                                    data-tipo="guía"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="button"
                                        class="btn-eliminar-elemento btnEliminarExpediente"
                                    >
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>

                    <div class="datos-elemento">
                        <div>
                            <span>Teléfono</span>
                            <strong>{{ $guia->telefono }}</strong>
                        </div>

                        <div>
                            <span>Ciudad</span>
                            <strong>
                                {{ $guia->ciudad_servicio
                                    ?: 'Sin información' }}
                            </strong>
                        </div>

                        <div>
                            <span>Periodo</span>
                            <strong>
                                {{ $guia->fecha_inicio
                                    ?->format('d/m/Y')
                                    ?? 'Sin fecha' }}
                                —
                                {{ $guia->fecha_fin
                                    ?->format('d/m/Y')
                                    ?? 'Sin fecha' }}
                            </strong>
                        </div>

                        <div>
                            <span>Punto de encuentro</span>
                            <strong>
                                {{ $guia->punto_encuentro
                                    ?: 'Pendiente' }}
                            </strong>
                        </div>
                    </div>
                </article>
            @empty
                <div class="sin-elementos-expediente">
                    <i class="bi bi-person-badge"></i>
                    <strong>No hay guías registrados</strong>
                    <span>
                        Si el paquete incluye guía, registra aquí sus datos.
                    </span>
                </div>
            @endforelse
        </div>
    </section>
    @endif

    @if ($editable)
        {{-- Activador interno requerido temporalmente por el modal de guía. --}}
        <button
            type="button"
            id="btnNuevoGuia"
            hidden
            aria-hidden="true"
            tabindex="-1"
        ></button>
    @endif
</main>

@include(
    'modules.operaciones.partials.modal-vuelo'
)

@include('modules.operaciones.partials.modal-viajero')

@include(
    'modules.operaciones.partials.modal-boleto'
)

@include(
    'modules.operaciones.partials.modal-alojamiento'
)

@include(
    'modules.operaciones.partials.modal-guia'
)

<script>
    window.configuracionOperacionViaje = {
        baseOperaciones: @json(
            route('operaciones.index', [], false)
        ),
        mensajeExito: @json(session('success')),
        mensajeError: @json(session('error')),
        errores: @json($errors->toArray()),
        vuelos: @json($operacion->vuelos->values()),
        alojamientos: @json(
            $operacion->alojamientos->values()
        ),
        guias: @json($operacion->guias->values()),
        viajerosReserva: @json($reserva->viajerosReserva->values()),

        datosPaquete: {
            aerolinea: @json(
                $reserva->destino?->aerolinea
            ),
            ciudadSalida: @json(
                $reserva->destino?->ciudad_salida
            ),
            ciudadDestino: @json(
                $reserva->destino?->ciudad_destino
            ),
            paisDestino: @json(
                $reserva->destino?->pais
            ),
            fechaSalida: @json(
                $reserva->destino?->fecha_salida
                    ?->format('Y-m-d')
            ),
            fechaRegreso: @json(
                $reserva->destino?->fecha_regreso
                    ?->format('Y-m-d')
            ),
            hotel: @json(
                $reserva->destino?->hotel
            ),
            moneda: @json(
                $reserva->destino?->moneda ?? 'USD'
            ),
            incluye: @json(
                $reserva->destino?->incluye ?? []
            )
        }
    };
</script>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="{{ asset('js/operacion-viaje.js') }}?v={{ filemtime(public_path('js/operacion-viaje.js')) }}"></script>
<script src="{{ asset('js/gestion-alimentacion.js') }}?v={{ filemtime(public_path('js/gestion-alimentacion.js')) }}"></script>
@endsection
