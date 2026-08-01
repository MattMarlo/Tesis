@extends('layouts.main')

@section('titulo', $titulo)

@section('content')
<link
    rel="stylesheet"
    href="{{ asset('css/operacion-viaje.css') }}"
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
            <strong>{{ $viajeros->count() }}</strong>
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

    <section class="bloque-expediente">
        <div class="bloque-expediente-titulo">
            <div>
                <span>Personas incluidas</span>
                <h2>Viajeros</h2>
            </div>
        </div>

        <div class="lista-viajeros-expediente">
            @foreach ($viajeros as $viajero)
                <article>
                    <div class="viajero-inicial">
                        {{ mb_strtoupper(
                            mb_substr(
                                $viajero->nombres,
                                0,
                                1
                            )
                        ) }}
                    </div>

                    <div>
                        <strong>
                            {{ $viajero->nombre_completo }}
                        </strong>

                        <small>
                            {{ $viajero->tipo_documento }}
                            {{ $viajero->documento }}
                        </small>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

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
                                {{ $vuelo->boletos
                                    ->where(
                                        'estado_emision',
                                        'emitido'
                                    )
                                    ->count() }}
                                de {{ $viajeros->count() }} emitidos
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
                                    @foreach ($viajeros as $viajero)
                                        @php
                                            $boleto = $vuelo
                                                ->boletos
                                                ->firstWhere(
                                                    'cliente_id',
                                                    $viajero->id
                                                );
                                        @endphp

                                        <tr>
                                            <td>
                                                {{ $viajero
                                                    ->nombre_completo }}
                                            </td>

                                            <td>
                                                {{ $boleto
                                                    ?->numero_boleto
                                                    ?? 'Pendiente' }}
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
                                                @if ($editable)
                                                    <button
                                                        type="button"
                                                        class="btn-gestionar-boleto btnGestionarBoleto"
                                                        data-vuelo-id="{{ $vuelo->id }}"
                                                        data-cliente-id="{{ $viajero->id }}"
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
</main>

@include(
    'modules.operaciones.partials.modal-vuelo'
)

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
<script src="{{ asset('js/operacion-viaje.js') }}"></script>
@endsection