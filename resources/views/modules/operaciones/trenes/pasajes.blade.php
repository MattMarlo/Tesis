@extends('layouts.main')

@section('titulo', $titulo)

@section('content')
<link
    rel="stylesheet"
    href="{{ asset('css/operacion-viaje.css') }}"
>
<link
    rel="stylesheet"
    href="{{ asset('css/gestion-pasajes-tren.css') }}?v={{ filemtime(public_path('css/gestion-pasajes-tren.css')) }}"
>

@php
    use App\Models\GestionOperativaViajero;

    $totalPasajes = $integrantes->count();
    $porcentaje = $totalPasajes > 0
        ? (int) round($confirmados / $totalPasajes * 100)
        : 0;

    $urlRegreso = route(
        'operaciones.show',
        $reserva->id
    ).($tarea
        ? '#tarea-itinerario-'.$tarea->id
        : '#tareasItinerario');

    $pasajeConError = $integrantes
        ->pluck('pasaje')
        ->filter()
        ->firstWhere('id', (int) old('pasaje_id'));
@endphp

<main
    id="main"
    class="main pagina-operacion-viaje pagina-pasajes-tren"
>
    <header class="expediente-encabezado">
        <div>
            <a href="{{ $urlRegreso }}" class="volver-operaciones">
                <i class="bi bi-arrow-left"></i>
                Volver a la tarea del itinerario
            </a>

            <span class="expediente-modulo">
                Transporte ferroviario
            </span>

            <h1>Gestión de pasajes de tren</h1>

            <p>
                {{ $reserva->codigo_reserva }} ·
                {{ $reserva->destino?->nombre_paquete
                    ?? 'Paquete no disponible' }}
            </p>
        </div>

        <span class="estado-expediente">
            {{ $confirmados }} de {{ $totalPasajes }} confirmados
        </span>
    </header>

    @if (session('success'))
        <div class="alert alert-success" role="alert">
            <i class="bi bi-check-circle"></i>
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->has('pasaje'))
        <div class="alert alert-danger" role="alert">
            {{ $errors->first('pasaje') }}
        </div>
    @endif

    @if ($tarea)
        <section class="origen-pasajes-tren">
            <i class="bi bi-list-check"></i>
            <div>
                <span>Tarea del itinerario</span>
                <strong>
                    Día {{ $tarea->dia }} · {{ $tarea->nombre }}
                </strong>
                <small>
                    Cada pasaje confirmado actualizará automáticamente
                    el progreso de esta tarea.
                </small>
            </div>
        </section>
    @endif

    <section class="tarjeta-trayecto-tren">
        <div class="identidad-trayecto-tren">
            <span><i class="bi bi-train-front"></i></span>
            <div>
                <small>Trayecto ferroviario</small>
                <h2>
                    {{ $gestion->ubicacion_origen }}
                    <i class="bi bi-arrow-right"></i>
                    {{ $gestion->destino }}
                </h2>
                <p>
                    {{ data_get(
                        $gestion->datos_adicionales,
                        'empresa_ferroviaria',
                        $gestion->proveedor
                    ) }}
                    @if ($gestion->referencia_confirmacion)
                        · {{ $gestion->referencia_confirmacion }}
                    @endif
                </p>
            </div>
        </div>

        <div class="datos-trayecto-tren">
            <article>
                <span>Salida</span>
                <strong>
                    {{ $gestion->fecha_hora_inicio
                        ?->format('d/m/Y H:i') }}
                </strong>
            </article>
            <article>
                <span>Llegada</span>
                <strong>
                    {{ $gestion->fecha_hora_fin
                        ?->format('d/m/Y H:i') }}
                </strong>
            </article>
            <article>
                <span>Progreso</span>
                <strong>{{ $porcentaje }}%</strong>
            </article>
        </div>
    </section>

    <section class="panel-pasajes-tren">
        <header>
            <div>
                <span>Viajeros de la reserva</span>
                <h2>Pasajes individuales</h2>
                <p>
                    Solo aparecen las personas registradas en esta reserva.
                </p>
            </div>
            <strong>{{ $totalPasajes }} viajeros</strong>
        </header>

        <div class="tabla-pasajes-tren-responsive">
            <table class="tabla-pasajes-tren">
                <thead>
                    <tr>
                        <th>Viajero</th>
                        <th>Número de pasaje</th>
                        <th>Asiento</th>
                        <th>Estado</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($integrantes as $integrante)
                        @php
                            $pasaje = $integrante['pasaje'];
                            $estado = $pasaje?->estado
                                ?? GestionOperativaViajero::ESTADO_PENDIENTE;
                            $estadoTexto = match ($estado) {
                                GestionOperativaViajero::ESTADO_CONFIRMADO => 'Confirmado',
                                GestionOperativaViajero::ESTADO_EN_PROCESO => 'En proceso',
                                GestionOperativaViajero::ESTADO_CANCELADO => 'Cancelado',
                                default => 'Pendiente',
                            };
                        @endphp
                        <tr>
                            <td>
                                <div class="persona-pasaje-tren">
                                    <span>
                                        {{ mb_strtoupper(
                                            mb_substr(
                                                $integrante['nombre'],
                                                0,
                                                1
                                            )
                                        ) }}
                                    </span>
                                    <div>
                                        <strong>{{ $integrante['nombre'] }}</strong>
                                        <small>{{ $integrante['documento'] }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                {{ $pasaje?->numero_documento ?: 'Pendiente' }}
                            </td>
                            <td>{{ $pasaje?->asiento ?: '—' }}</td>
                            <td>
                                <span class="estado-pasaje estado-pasaje-{{ $estado }}">
                                    {{ $estadoTexto }}
                                </span>
                            </td>
                            <td>
                                @if ($editable && $pasaje)
                                    <button
                                        type="button"
                                        class="btn-gestionar-pasaje"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalPasajeTren"
                                        data-id="{{ $pasaje->id }}"
                                        data-nombre="{{ $integrante['nombre'] }}"
                                        data-documento="{{ $pasaje->numero_documento }}"
                                        data-asiento="{{ $pasaje->asiento }}"
                                        data-referencia="{{ $pasaje->referencia_individual }}"
                                        data-estado="{{ $estado }}"
                                        data-restricciones="{{ $pasaje->restricciones }}"
                                        data-observaciones="{{ $pasaje->observaciones }}"
                                    >
                                        <i class="bi bi-ticket-perforated"></i>
                                        Gestionar
                                    </button>
                                @else
                                    <span class="solo-lectura-pasaje">
                                        Solo lectura
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="sin-pasajes-tren">
                                No existen integrantes registrados en la reserva.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</main>

<div
    class="modal fade"
    id="modalPasajeTren"
    tabindex="-1"
    aria-labelledby="modalPasajeTrenTitulo"
    aria-hidden="true"
    data-reabrir="{{ $pasajeConError ? 'true' : 'false' }}"
>
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content modal-pasaje-tren">
            <div class="modal-header">
                <div>
                    <span>DOCUMENTO DE VIAJE</span>
                    <h2 id="modalPasajeTrenTitulo">Gestionar pasaje de tren</h2>
                </div>
                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Cerrar"
                ></button>
            </div>

            <form
                id="formPasajeTren"
                method="POST"
                action="{{ route(
                    'operaciones.trenes.pasajes.update-from-index',
                    [
                        'operacion' => $operacion->id,
                        'gestion' => $gestion->id,
                        'tarea_id' => request('tarea_id'),
                    ]
                ) }}"
                novalidate
            >
                @csrf

                <input
                    type="hidden"
                    name="pasaje_id"
                    value="{{ old('pasaje_id') }}"
                >

                <div class="modal-body">
                    <div class="viajero-modal-pasaje">
                        <i class="bi bi-person-check"></i>
                        <div>
                            <span>Viajero</span>
                            <strong id="nombreViajeroPasaje"></strong>
                        </div>
                    </div>

                    <div class="campos-pasaje-tren">
                        <div class="campo-pasaje-tren">
                            <label for="numeroPasajeTren">
                                Número de pasaje
                            </label>
                            <input
                                id="numeroPasajeTren"
                                type="text"
                                name="numero_documento"
                                maxlength="150"
                                value="{{ old('numero_documento') }}"
                            >
                            @error('numero_documento')
                                <small class="mensaje-error-pasaje">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="campo-pasaje-tren">
                            <label for="asientoPasajeTren">Asiento</label>
                            <input
                                id="asientoPasajeTren"
                                type="text"
                                name="asiento"
                                maxlength="30"
                                placeholder="Ejemplo: 12A"
                                value="{{ old('asiento') }}"
                            >
                            @error('asiento')
                                <small class="mensaje-error-pasaje">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="campo-pasaje-tren">
                            <label for="referenciaPasajeTren">
                                Código o localizador
                            </label>
                            <input
                                id="referenciaPasajeTren"
                                type="text"
                                name="referencia_individual"
                                maxlength="150"
                                value="{{ old('referencia_individual') }}"
                            >
                            @error('referencia_individual')
                                <small class="mensaje-error-pasaje">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="campo-pasaje-tren">
                            <label for="estadoPasajeTren">Estado *</label>
                            <select
                                id="estadoPasajeTren"
                                name="estado"
                                required
                            >
                                <option value="pendiente" @selected(old('estado') === 'pendiente')>Pendiente</option>
                                <option value="en_proceso" @selected(old('estado') === 'en_proceso')>En proceso</option>
                                <option value="confirmado" @selected(old('estado') === 'confirmado')>Confirmado</option>
                                <option value="cancelado" @selected(old('estado') === 'cancelado')>Cancelado</option>
                            </select>
                            @error('estado')
                                <small class="mensaje-error-pasaje">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="campo-pasaje-tren campo-pasaje-amplio">
                            <label for="restriccionesPasajeTren">
                                Restricciones
                            </label>
                            <textarea
                                id="restriccionesPasajeTren"
                                name="restricciones"
                                rows="2"
                                maxlength="2000"
                            >{{ old('restricciones') }}</textarea>
                            @error('restricciones')
                                <small class="mensaje-error-pasaje">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="campo-pasaje-tren campo-pasaje-amplio">
                            <label for="observacionesPasajeTren">
                                Observaciones
                            </label>
                            <textarea
                                id="observacionesPasajeTren"
                                name="observaciones"
                                rows="3"
                                maxlength="2000"
                            >{{ old('observaciones') }}</textarea>
                            @error('observaciones')
                                <small class="mensaje-error-pasaje">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-light"
                        data-bs-dismiss="modal"
                    >
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check2"></i>
                        Guardar pasaje
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    window.pasajeTrenConError = @json(
        $pasajeConError
            ? [
                'id' => $pasajeConError->id,
                'nombre' => $integrantes
                    ->first(
                        fn ($integrante) =>
                            $integrante['pasaje']?->id ===
                                $pasajeConError->id
                    )['nombre'] ?? '',
            ]
            : null
    );
</script>
<script
    src="{{ asset('js/gestion-pasajes-tren.js') }}?v={{ filemtime(public_path('js/gestion-pasajes-tren.js')) }}"
></script>
@endsection
