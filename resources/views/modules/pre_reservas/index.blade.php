@extends('layouts.main')

@section('titulo', $titulo)

@section('content')

<link
    rel="stylesheet"
    href="https://cdn.datatables.net/3.0.1/css/dataTables.bootstrap5.min.css"
>

<link
    rel="stylesheet"
    href="https://cdn.datatables.net/buttons/4.0.1/css/buttons.bootstrap5.min.css"
>

<link
    rel="stylesheet"
    href="{{ asset('css/prerreservas-listado.css') }}?v={{ filemtime(public_path('css/prerreservas-listado.css')) }}"
>

<main
    id="main"
    class="main pagina-prerreservas"
>
    <header class="prerreservas-encabezado">
        <span>Solicitudes desde Telegram</span>

        <h1>Prerreservas</h1>

        <p>
            Revisa las solicitudes recibidas, contacta al cliente
            y conviértelas en reservas cuando la información esté completa.
        </p>
    </header>

    <section class="resumen-prerreservas">
        <article>
            <span>Total recibidas</span>
            <strong>{{ $resumen['total'] }}</strong>
            <small>Historial completo</small>
        </article>

        <article>
            <span>Pendientes</span>
            <strong>{{ $resumen['pendientes'] }}</strong>
            <small>Esperan contacto</small>
        </article>

        <article>
            <span>Contactadas</span>
            <strong>{{ $resumen['contactadas'] }}</strong>
            <small>En seguimiento</small>
        </article>

        <article>
            <span>Convertidas</span>
            <strong>{{ $resumen['convertidas'] }}</strong>
            <small>Reservas generadas</small>
        </article>
    </section>

    <form
        method="GET"
        action="{{ route('prereservas.index') }}"
        class="filtros-prerreservas"
    >
        <div class="buscar-prerreserva">
            <i class="bi bi-search"></i>

            <input
                type="search"
                name="buscar"
                value="{{ request('buscar') }}"
                placeholder="Cliente, correo, teléfono o paquete"
                autocomplete="off"
            >
        </div>

        <select name="estado">
            <option value="">
                Todos los estados
            </option>

            <option
                value="pendiente_contacto"
                @selected(
                    request('estado') ===
                    'pendiente_contacto'
                )
            >
                Pendientes
            </option>

            <option
                value="contactado"
                @selected(
                    request('estado') ===
                    'contactado'
                )
            >
                Contactadas
            </option>

            <option
                value="convertida"
                @selected(
                    request('estado') ===
                    'convertida'
                )
            >
                Convertidas
            </option>

            <option
                value="perdida"
                @selected(
                    request('estado') ===
                    'perdida'
                )
            >
                Descartadas
            </option>
        </select>

        <button type="submit">
            <i class="bi bi-funnel"></i>
            Filtrar
        </button>

        @if (
            request()->filled('buscar') ||
            request()->filled('estado')
        )
            <a href="{{ route('prereservas.index') }}">
                Limpiar
            </a>
        @endif
    </form>

    <section class="contenedor-prerreservas">
        <div class="tabla-prerreservas-responsive">
            <table
                id="tablaPrerreservas"
                class="tabla-prerreservas"
            >
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Contacto</th>
                        <th>Paquete solicitado</th>
                        <th>Viajeros</th>
                        <th>Recibida</th>
                        <th>Origen</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($preReservas as $pre)
                        @php
                            $nombreEstado = match (
                                $pre->estado
                            ) {
                                'pendiente_contacto' =>
                                    'Pendiente',
                                'contactado' =>
                                    'Contactada',
                                'convertida' =>
                                    'Convertida',
                                'perdida' =>
                                    'Descartada',
                                default =>
                                    'Sin información',
                            };
                        @endphp

                        <tr>
                            <td>
                                <strong>
                                    {{ $pre->cliente_nombre }}
                                </strong>

                                <small>
                                    Cédula:
                                    {{ $pre->cedula ?: 'Pendiente' }}
                                </small>
                            </td>

                            <td>
                                <strong>{{ $pre->telefono }}</strong>

                                <small>{{ $pre->email }}</small>
                            </td>

                            <td>
                                <strong>
                                    {{
                                        $pre->destinoRelacionado
                                            ?->nombre_paquete
                                        ?? $pre->destino
                                    }}
                                </strong>

                                <small>
                                    Fecha tentativa:
                                    {{
                                        $pre->fecha_viaje
                                            ?->format('d/m/Y')
                                        ?? 'Sin fecha'
                                    }}
                                </small>
                            </td>

                            <td data-order="{{ $pre->cantidad_personas }}">
                                <strong>
                                    {{ $pre->cantidad_personas }}
                                </strong>

                                <small>
                                    {{
                                        $pre->cantidad_personas === 1
                                            ? 'Persona'
                                            : 'Personas'
                                    }}
                                </small>
                            </td>

                            <td data-order="{{ $pre->created_at?->timestamp ?? 0 }}">
                                <strong>
                                    {{
                                        $pre->created_at
                                            ?->format('d/m/Y')
                                    }}
                                </strong>

                                <small>
                                    {{
                                        $pre->created_at
                                            ?->format('H:i')
                                    }}
                                </small>
                            </td>

                            <td>
                                @if ($pre->origen === \App\Models\PreReserva::ORIGEN_WHATSAPP)
                                    <span class="origen-whatsapp">
                                        <i class="bi bi-whatsapp"></i>
                                        WhatsApp
                                    </span>
                                @else
                                    <span class="origen-telegram">
                                        <i class="bi bi-telegram"></i>
                                        Telegram
                                    </span>
                                @endif
                            </td>

                            <td>
                                <span
                                    class="estado-prerreserva estado-{{ $pre->estado }}"
                                >
                                    {{ $nombreEstado }}
                                </span>

                                @if ($pre->reserva)
                                    <small>
                                        {{ $pre->reserva->codigo_reserva }}
                                    </small>
                                @endif
                            </td>

                            <td>
                                @if ($pre->puedeGestionarse())
                                    <div class="acciones-prerreserva">
                                        <a
                                            href="{{ route(
                                                'prereservas.edit',
                                                $pre->id
                                            ) }}"
                                            class="btn-editar-prerreserva"
                                            title="Revisar y editar"
                                        >
                                            <i class="bi bi-pencil-square"></i>
                                        </a>

                                        <form
                                            method="POST"
                                            action="{{ route(
                                                'prereservas.convertir',
                                                $pre->id
                                            ) }}"
                                            class="formulario-convertir-prerreserva"
                                        >
                                            @csrf

                                            <button
                                                type="button"
                                                class="btn-convertir-prerreserva"
                                                title="Convertir en reserva"
                                            >
                                                <i class="bi bi-arrow-right-circle"></i>
                                            </button>
                                        </form>

                                        <form
                                            method="POST"
                                            action="{{ route(
                                                'prereservas.destroy',
                                                $pre->id
                                            ) }}"
                                            class="formulario-descartar-prerreserva"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="button"
                                                class="btn-descartar-prerreserva"
                                                title="Descartar prerreserva"
                                            >
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                        </form>
                                    </div>
                                @elseif ($pre->estaConvertida())
                                    <span class="reserva-generada">
                                        <i class="bi bi-check-circle"></i>
                                        Reserva generada
                                    </span>
                                @else
                                    <span class="reserva-generada">
                                        Sin acciones
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</main>

<script>
    window.configuracionPrerreservas = {
        mensajeExito: @json(session('success')),
        mensajeError: @json(session('error')),
        errores: @json($errors->toArray())
    };
</script>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script
    src="https://cdn.datatables.net/3.0.1/js/dataTables.min.js"
></script>

<script
    src="https://cdn.datatables.net/3.0.1/js/dataTables.bootstrap5.min.js"
></script>

<script
    src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"
></script>

<script
    src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"
></script>

<script
    src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"
></script>

<script
    src="https://cdn.datatables.net/buttons/4.0.1/js/dataTables.buttons.min.js"
></script>

<script
    src="https://cdn.datatables.net/buttons/4.0.1/js/buttons.bootstrap5.min.js"
></script>

<script
    src="https://cdn.datatables.net/buttons/4.0.1/js/buttons.html5.min.js"
></script>

<script
    src="{{ asset('js/prerreservas-listado.js') }}?v={{ filemtime(public_path('js/prerreservas-listado.js')) }}"
></script>

@endsection
