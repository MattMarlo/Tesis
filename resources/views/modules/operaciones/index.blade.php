@extends('layouts.main')

@section('titulo', $titulo)

@section('content')
<link
    rel="stylesheet"
    href="{{ asset('css/operaciones-listado.css') }}"
>

<main id="main" class="main pagina-operaciones">
    <header class="operaciones-encabezado">
        <div>
            <span class="operaciones-modulo">
                Seguimiento operativo
            </span>

            <h1>Preparación de viajes</h1>

            <p>
                Organiza vuelos, boletos, alojamientos y guías
                antes de informar al cliente.
            </p>
        </div>

        <a
            href="{{ route('reservas') }}"
            class="btn-ir-reservas"
        >
            <i class="bi bi-calendar-check"></i>
            Ver reservas
        </a>
    </header>

    <section class="resumen-operaciones">
        <article>
            <span>Total de viajes</span>
            <strong>{{ $resumen['total'] }}</strong>
            <small>Reservas activas</small>
        </article>

        <article>
            <span>Sin iniciar</span>
            <strong>{{ $resumen['sin_iniciar'] }}</strong>
            <small>Sin expediente operativo</small>
        </article>

        <article>
            <span>En preparación</span>
            <strong>{{ $resumen['preparacion'] }}</strong>
            <small>Con información pendiente</small>
        </article>

        <article>
            <span>Completos</span>
            <strong>{{ $resumen['completas'] }}</strong>
            <small>Listos o notificados</small>
        </article>
    </section>

    <form
        method="GET"
        action="{{ route('operaciones.index') }}"
        class="filtros-operaciones"
    >
        <div class="buscar-operacion">
            <i class="bi bi-search"></i>

            <input
                type="search"
                name="buscar"
                value="{{ request('buscar') }}"
                placeholder="Código, cliente, grupo o paquete"
                autocomplete="off"
            >
        </div>

        <select name="estado">
            <option value="">
                Todos los estados
            </option>

            <option
                value="sin_iniciar"
                @selected(
                    request('estado') ===
                    'sin_iniciar'
                )
            >
                Sin iniciar
            </option>

            <option
                value="pendiente"
                @selected(
                    request('estado') ===
                    'pendiente'
                )
            >
                Pendiente
            </option>

            <option
                value="preparacion"
                @selected(
                    request('estado') ===
                    'preparacion'
                )
            >
                En preparación
            </option>

            <option
                value="completo"
                @selected(
                    request('estado') ===
                    'completo'
                )
            >
                Completo
            </option>

            <option
                value="notificado"
                @selected(
                    request('estado') ===
                    'notificado'
                )
            >
                Notificado
            </option>
        </select>

        <button type="submit">
            Filtrar
        </button>

        @if (
            request()->filled('buscar') ||
            request()->filled('estado')
        )
            <a href="{{ route('operaciones.index') }}">
                Limpiar
            </a>
        @endif
    </form>

    <section class="contenedor-operaciones">
        <div class="tabla-operaciones-responsive">
            <table class="tabla-operaciones">
                <thead>
                    <tr>
                        <th>Reserva</th>
                        <th>Cliente o grupo</th>
                        <th>Paquete</th>
                        <th>Fecha del viaje</th>
                        <th>Viajeros</th>
                        <th>Preparación</th>
                        <th>Acción</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($reservas as $reserva)
                        @php
                            $operacion =
                                $reserva->operacionViaje;

                            $estado =
                                $operacion?->estado
                                ?? 'sin_iniciar';

                            $nombreEstado = match (
                                $estado
                            ) {
                                'pendiente' =>
                                    'Pendiente',
                                'preparacion' =>
                                    'En preparación',
                                'completo' =>
                                    'Completo',
                                'notificado' =>
                                    'Notificado',
                                default =>
                                    'Sin iniciar',
                            };

                            $nombreCliente =
                                $reserva->esGrupal()
                                    ? (
                                        $reserva->grupo
                                            ?->nombre_grupo
                                        ?? 'Grupo no disponible'
                                    )
                                    : (
                                        $reserva->cliente
                                            ?->nombre_completo
                                        ?? 'Cliente no disponible'
                                    );
                        @endphp

                        <tr>
                            <td>
                                <strong class="codigo-operacion">
                                    {{ $reserva->codigo_reserva }}
                                </strong>

                                <small>
                                    {{ ucfirst($reserva->tipo) }}
                                </small>
                            </td>

                            <td>
                                <strong>
                                    {{ $nombreCliente }}
                                </strong>

                                <small>
                                    {{ $reserva->esGrupal()
                                        ? 'Reserva grupal'
                                        : 'Reserva individual' }}
                                </small>
                            </td>

                            <td>
                                <strong>
                                    {{ $reserva->destino
                                        ?->nombre_paquete
                                        ?? 'Paquete no disponible' }}
                                </strong>

                                <small>
                                    {{ $reserva->destino
                                        ?->ciudad_destino
                                        ?? 'Destino no registrado' }}
                                </small>
                            </td>

                            <td>
                                <strong>
                                    {{ $reserva->fecha_viaje
                                        ?->format('d/m/Y')
                                        ?? 'Sin fecha' }}
                                </strong>

                                <small>
                                    Salida programada
                                </small>
                            </td>

                            <td>
                                <strong>
                                    {{ $reserva->cantidad_viajeros }}
                                </strong>

                                <small>
                                    Persona(s)
                                </small>
                            </td>

                            <td>
                                <span
                                    class="estado-operacion estado-{{ $estado }}"
                                >
                                    {{ $nombreEstado }}
                                </span>

                                @if (
                                    $operacion
                                    ?->fecha_documentacion_completa
                                )
                                    <small>
                                        Completado:
                                        {{ $operacion
                                            ->fecha_documentacion_completa
                                            ->format('d/m/Y') }}
                                    </small>
                                @endif
                            </td>

                            <td>
                                @if ($operacion)
                                    <a href="{{ route('operaciones.show', $reserva->id) }}"
                                       class="btn-gestionar-operacion">
                                        <i class="bi bi-folder2-open"></i>
                                        Abrir expediente
                                    </a>
                                @else
                                    <form
                                            method="POST"
                                            action="{{ route('operaciones.iniciar', $reserva->id) }}"
                                            class="form-iniciar-operacion"
                                        >
                                        @csrf
                                        <button type="submit" class="btn-gestionar-operacion">
                                            <i class="bi bi-play-circle"></i>
                                            Iniciar preparación
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="7"
                                class="sin-operaciones"
                            >
                                <i class="bi bi-luggage"></i>

                                <strong>
                                    No existen viajes para mostrar
                                </strong>

                                <span>
                                    Cambia los filtros o registra una reserva.
                                </span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($reservas->hasPages())
        <div class="paginacion-operaciones">
            {{ $reservas->links() }}
        </div>
    @endif
</main>

<script>
    window.configuracionOperaciones = {
        mensajeExito: @json(session('success')),
        mensajeError: @json(session('error'))
    };
</script>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="{{ asset('js/operaciones-listado.js') }}?v={{ filemtime(public_path('js/operaciones-listado.js')) }}"></script>
@endsection
