@extends('layouts.main')

@section('titulo', $titulo)

@section('content')
<link
    rel="stylesheet"
    href="{{ asset('css/operacion-viaje.css') }}"
>

<link
    rel="stylesheet"
    href="{{ asset('css/gestion-boletos-vuelo.css') }}"
>

@php
    $nombreTramo = match ($vuelo->tipo_tramo) {
        'ida' => 'Ida',
        'regreso' => 'Regreso',
        'conexion' => 'Conexión',
        default => 'Tramo',
    };

    $nombreEstado = match ($vuelo->estado) {
        'confirmado' => 'Confirmado',
        'pendiente' => 'Pendiente',
        'cancelado' => 'Cancelado',
        default => 'Sin información',
    };

    $totalBoletos =
        (int) ($progresoVuelo['total'] ?? 0);

    $boletosEmitidos =
        (int) ($progresoVuelo['actual'] ?? 0);

    $porcentajeBoletos = $totalBoletos > 0
        ? (int) round(
            ($boletosEmitidos / $totalBoletos) * 100
        )
        : 100;

    $porcentajeAsientos = $totalBoletos > 0
        ? (int) round(
            ($asientosAsignados / $totalBoletos) * 100
        )
        : 100;

    $urlRegreso = route(
        'operaciones.show',
        $reserva->id
    );

    if ($tarea) {
        $urlRegreso .=
            '#tarea-itinerario-' .
            $tarea->id;
    } else {
        $urlRegreso .= '#tareasItinerario';
    }

    /*
     * Se prepara fuera de @json para evitar que el compilador
     * de Blade interprete incorrectamente el arreglo y la
     * función utilizada para transformar los boletos.
     */
    $boletosConfiguracion = $vuelo->boletos
        ->map(
            function ($boleto): array {
                return [
                    'id' =>
                        $boleto->id,

                    'cliente_id' =>
                        $boleto->cliente_id,

                    'viajero_reserva_id' =>
                        $boleto->viajero_reserva_id,

                    'numero_boleto' =>
                        $boleto->numero_boleto,

                    'asiento' =>
                        $boleto->asiento,

                    'clase' =>
                        $boleto->clase,

                    'estado_emision' =>
                        $boleto->estado_emision,

                    'archivo_boleto' =>
                        $boleto->archivo_boleto,

                    'archivo_url' =>
                        $boleto->archivo_boleto
                            ? asset(
                                'storage/' .
                                $boleto->archivo_boleto
                            )
                            : null,

                    'observaciones' =>
                        $boleto->observaciones,
                ];
            }
        )
        ->values();
@endphp

<main
    id="main"
    class="main pagina-operacion-viaje pagina-gestion-boletos"
>
    <header class="expediente-encabezado">
        <div>
            <a
                href="{{ $urlRegreso }}"
                class="volver-operaciones"
            >
                <i class="bi bi-arrow-left"></i>

                @if($tarea)
                    Volver a la tarea del itinerario
                @else
                    Volver al expediente
                @endif
            </a>

            <span class="expediente-modulo">
                Transporte aéreo
            </span>

            <h1>Gestión de boletos</h1>

            <p>
                {{ $reserva->codigo_reserva }}
                ·
                {{ $reserva->destino?->nombre_paquete
                    ?? 'Paquete no disponible' }}
            </p>
        </div>

        <span
            class="estado-expediente estado-vuelo-{{ $vuelo->estado }}"
        >
            {{ $nombreEstado }}
        </span>
    </header>

    @if(session('success'))
        <div
            class="alert alert-success"
            role="alert"
        >
            <i class="bi bi-check-circle"></i>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div
            class="alert alert-danger"
            role="alert"
        >
            <i class="bi bi-exclamation-triangle"></i>
            {{ session('error') }}
        </div>
    @endif

    @if($tarea)
        <section class="origen-gestion-boletos">
            <div class="origen-gestion-boletos-icono">
                <i class="bi bi-list-check"></i>
            </div>

            <div>
                <span>
                    Tarea del itinerario
                </span>

                <strong>
                    Día {{ $tarea->dia }}
                    ·
                    {{ $tarea->nombre }}
                </strong>

                <small>
                    Los avances registrados aquí actualizarán
                    automáticamente esta tarea.
                </small>
            </div>
        </section>
    @endif

    <section class="tarjeta-vuelo-boletos">
        <header class="encabezado-vuelo-boletos">
            <div class="identidad-vuelo-boletos">
                <span class="icono-vuelo-boletos">
                    <i class="bi bi-airplane"></i>
                </span>

                <div>
                    <span>
                        {{ $nombreTramo }}
                    </span>

                    <h2>
                        {{ $vuelo->ciudad_origen }}
                        <i class="bi bi-arrow-right"></i>
                        {{ $vuelo->ciudad_destino }}
                    </h2>

                    <p>
                        {{ $vuelo->aerolinea }}

                        @if($vuelo->numero_vuelo)
                            · {{ $vuelo->numero_vuelo }}
                        @endif
                    </p>
                </div>
            </div>

            <a
                href="{{ $urlRegreso }}"
                class="btn-ver-expediente"
            >
                <i class="bi bi-folder2-open"></i>
                Ver expediente
            </a>
        </header>

        <div class="datos-principales-vuelo">
            <article>
                <span>Salida</span>

                <strong>
                    {{ $vuelo->fecha_hora_salida
                        ?->format('d/m/Y H:i')
                        ?? 'Pendiente' }}
                </strong>

                <small>
                    {{ $vuelo->aeropuerto_origen
                        ?: $vuelo->ciudad_origen }}
                </small>
            </article>

            <article>
                <span>Llegada</span>

                <strong>
                    {{ $vuelo->fecha_hora_llegada
                        ?->format('d/m/Y H:i')
                        ?? 'Pendiente' }}
                </strong>

                <small>
                    {{ $vuelo->aeropuerto_destino
                        ?: $vuelo->ciudad_destino }}
                </small>
            </article>

            <article>
                <span>Localizador</span>

                <strong>
                    {{ $vuelo->localizador_reserva
                        ?: 'Pendiente' }}
                </strong>

                <small>
                    Referencia de la reserva aérea
                </small>
            </article>

            <article>
                <span>Equipaje</span>

                <strong>
                    {{ $vuelo->equipaje_incluido
                        ?: 'Sin información' }}
                </strong>

                <small>
                    Condiciones registradas
                </small>
            </article>
        </div>
    </section>

    <section class="resumen-gestion-boletos">
        <article>
            <div class="resumen-gestion-icono">
                <i class="bi bi-ticket-perforated"></i>
            </div>

            <div class="resumen-gestion-contenido">
                <span>Boletos emitidos</span>

                <strong>
                    {{ $boletosEmitidos }}
                    de
                    {{ $totalBoletos }}
                </strong>

                <div class="barra-gestion-boletos">
                    <span
                        style="width: {{ $porcentajeBoletos }}%"
                    ></span>
                </div>
            </div>
        </article>

        <article>
            <div class="resumen-gestion-icono">
                <i class="bi bi-person-workspace"></i>
            </div>

            <div class="resumen-gestion-contenido">
                <span>Asientos asignados</span>

                <strong>
                    {{ $asientosAsignados }}
                    de
                    {{ $totalBoletos }}
                </strong>

                <div class="barra-gestion-boletos">
                    <span
                        style="width: {{ $porcentajeAsientos }}%"
                    ></span>
                </div>
            </div>
        </article>
    </section>

    <section class="panel-viajeros-boletos">
        <header class="panel-viajeros-encabezado">
            <div>
                <span class="expediente-modulo">
                    Personas incluidas
                </span>

                <h2>Boletos por viajero</h2>

                <p>
                    Cada viajero debe tener su propio número
                    de boleto y asiento.
                </p>
            </div>

            <span class="cantidad-viajeros-boletos">
                {{ $personas->count() }}
                {{ $personas->count() === 1
                    ? 'viajero'
                    : 'viajeros' }}
            </span>
        </header>

        <div class="tabla-gestion-boletos-responsive">
            <table class="tabla-gestion-boletos">
                <thead>
                    <tr>
                        <th>Viajero</th>
                        <th>Documento</th>
                        <th>Número de boleto</th>
                        <th>Asiento</th>
                        <th>Clase</th>
                        <th>Estado</th>
                        <th>Archivo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($personas as $persona)
                        @php
                            $boleto = $vuelo->boletos
                                ->first(
                                    function ($item) use (
                                        $persona
                                    ) {
                                        if (
                                            $persona['tipo'] ===
                                            'viajero'
                                        ) {
                                            return (int)
                                                $item
                                                    ->viajero_reserva_id ===
                                                (int)
                                                $persona['id'];
                                        }

                                        return (int)
                                            $item->cliente_id ===
                                            (int)
                                            $persona['id'];
                                    }
                                );

                            $requiereBoleto = (bool) (
                                $persona['requiere_boleto']
                                ?? false
                            );
                        @endphp

                        <tr>
                            <td>
                                <div class="persona-tabla-boletos">
                                    <span>
                                        {{ mb_strtoupper(
                                            mb_substr(
                                                $persona['nombre'],
                                                0,
                                                1
                                            )
                                        ) }}
                                    </span>

                                    <div>
                                        <strong>
                                            {{ $persona['nombre'] }}
                                        </strong>

                                        <small>
                                            {{ ucfirst(
                                                str_replace(
                                                    '_',
                                                    ' ',
                                                    $persona['categoria']
                                                    ?? 'viajero'
                                                )
                                            ) }}

                                            @if(
                                                $persona['es_titular']
                                                ?? false
                                            )
                                                · Titular
                                            @endif
                                        </small>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <strong>
                                    {{ $persona[
                                        'documento_enmascarado'
                                    ] ?? 'Pendiente' }}
                                </strong>

                                <small class="dato-secundario-tabla">
                                    {{ $persona['tipo_documento']
                                        ?? 'Sin tipo' }}
                                </small>
                            </td>

                            @if(!$requiereBoleto)
                                <td colspan="5">
                                    <span class="boleto-no-requerido">
                                        <i class="bi bi-info-circle"></i>
                                        No requiere boleto individual
                                    </span>
                                </td>

                                <td>—</td>
                            @else
                                <td>
                                    {{ $boleto?->numero_boleto
                                        ?: 'Pendiente' }}
                                </td>

                                <td>
                                    {{ $boleto?->asiento
                                        ?: '—' }}
                                </td>

                                <td>
                                    {{ $boleto?->clase
                                        ?: '—' }}
                                </td>

                                <td>
                                    <span
                                        class="estado-boleto estado-{{ $boleto?->estado_emision ?? 'pendiente' }}"
                                    >
                                        {{ ucfirst(
                                            $boleto?->estado_emision
                                            ?? 'pendiente'
                                        ) }}
                                    </span>
                                </td>

                                <td>
                                    @if($boleto?->archivo_boleto)
                                        <a
                                            href="{{ asset(
                                                'storage/' .
                                                $boleto->archivo_boleto
                                            ) }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="enlace-archivo-boleto"
                                        >
                                            <i class="bi bi-file-earmark"></i>
                                            Ver
                                        </a>
                                    @else
                                        —
                                    @endif
                                </td>

                                <td>
                                    <div class="acciones-tabla-boletos">
                                        @if($editable)
                                            <button
                                                type="button"
                                                class="btn-gestionar-boleto btnGestionarBoletoPagina"
                                                data-persona-id="{{ $persona['id'] }}"
                                                data-persona-tipo="{{ $persona['tipo'] }}"
                                            >
                                                <i class="bi bi-pencil-square"></i>

                                                {{ $boleto
                                                    ? 'Editar'
                                                    : 'Asignar' }}
                                            </button>

                                            @if($boleto)
                                                <form
                                                    method="POST"
                                                    action="{{ route(
                                                        'operaciones.boletos.destroy',
                                                        $boleto
                                                    ) }}"
                                                    class="formEliminarBoletoPagina"
                                                >
                                                    @csrf
                                                    @method('DELETE')

                                                    <button
                                                        type="submit"
                                                        class="btn-eliminar-boleto-pagina"
                                                        title="Eliminar boleto"
                                                        aria-label="Eliminar boleto de {{ $persona['nombre'] }}"
                                                    >
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        @else
                                            <span class="expediente-no-editable">
                                                Solo lectura
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="8"
                                class="sin-viajeros-boletos"
                            >
                                No hay viajeros registrados
                                en esta reserva.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</main>

@include(
    'modules.operaciones.partials.modal-boleto'
)

<script>
    window.configuracionGestionBoletos = {
        storeUrl: @json(
            route(
                'operaciones.boletos.store',
                $vuelo
            )
        ),

        editable: @json($editable),

        personas: @json(
            $personas->values()
        ),

        boletos: @json(
            $boletosConfiguracion
        ),

        errores: @json(
            $errors->toArray()
        ),

        formularioAnterior: {
            clienteId: @json(
                old('cliente_id')
            ),

            viajeroId: @json(
                old('viajero_reserva_id')
            ),

            numero: @json(
                old('numero_boleto')
            ),

            asiento: @json(
                old('asiento')
            ),

            clase: @json(
                old('clase')
            ),

            estado: @json(
                old('estado_emision')
            ),

            observaciones: @json(
                old('observaciones')
            )
        }
    };
</script>

<script
    src="{{ asset('js/gestion-boletos-vuelo.js') }}?v={{ filemtime(public_path('js/gestion-boletos-vuelo.js')) }}"
></script>
@endsection
