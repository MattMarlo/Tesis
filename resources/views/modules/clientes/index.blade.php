@extends('layouts.main')

@section('titulo', 'Clientes')

@section('content')
<link
    rel="stylesheet"
    href="{{ asset('css/clientes-listado.css') }}"
>

<main id="main" class="main pagina-clientes">
    <div class="clientes-encabezado">
        <div>
            <span class="clientes-modulo">Gestión de viajes</span>

            <h1>Clientes</h1>

            <p>
                Consulta y administra la información de las personas
                registradas en la agencia.
            </p>
        </div>

        <a
            href="{{ route('clientes.create') }}"
            class="btn-nuevo-cliente"
        >
            <i class="bi bi-person-plus"></i>
            Registrar cliente
        </a>
    </div>

    <section class="clientes-resumen">
        <div class="resumen-cliente">
            <span>Total de clientes</span>
            <strong>{{ $clientes->count() }}</strong>
        </div>

        <div class="resumen-cliente">
            <span>Clientes activos</span>
            <strong>
                {{ $clientes->where('estado', 'activo')->count() }}
            </strong>
        </div>

        <div class="resumen-cliente">
            <span>Clientes inactivos</span>
            <strong>
                {{ $clientes->where('estado', 'inactivo')->count() }}
            </strong>
        </div>
    </section>

    <section class="clientes-herramientas">
        <div class="clientes-buscador">
            <i class="bi bi-search"></i>

            <input
                id="buscarCliente"
                type="search"
                placeholder="Buscar por nombre, documento, correo o teléfono"
                autocomplete="off"
            >
        </div>

        <div class="clientes-filtro">
            <label for="filtrarClientes">
                Estado:
            </label>

            <select id="filtrarClientes">
                <option value="">Todos</option>
                <option value="activo">Activos</option>
                <option value="inactivo">Inactivos</option>
            </select>
        </div>
    </section>

    <section id="contenedorClientes" class="clientes-grid">
        @forelse ($clientes as $cliente)
            @php
                $tieneMovimientos =
                    $cliente->reservas_count > 0 ||
                    $cliente->pagos_count > 0 ||
                    $cliente->grupos_count > 0;

                $iniciales =
                    strtoupper(substr($cliente->nombres, 0, 1)) .
                    strtoupper(substr($cliente->apellidos, 0, 1));
            @endphp

            <article
                class="tarjeta-cliente"
                data-estado="{{ $cliente->estado }}"
                data-busqueda="{{ strtolower(
                    $cliente->nombre_completo . ' ' .
                    $cliente->documento . ' ' .
                    $cliente->email . ' ' .
                    $cliente->telefono
                ) }}"
            >
                <header class="tarjeta-cliente-encabezado">
                    <div class="cliente-principal">
                        <span class="cliente-iniciales">
                            {{ $iniciales }}
                        </span>

                        <div>
                            <h2>{{ $cliente->nombre_completo }}</h2>

                            <span class="cliente-registro">
                                Cliente desde
                                {{ $cliente->created_at?->format('d/m/Y') }}
                            </span>
                        </div>
                    </div>

                    @if ($cliente->estado === 'activo')
                        <span class="estado-cliente activo">
                            <span></span>
                            Activo
                        </span>
                    @else
                        <span class="estado-cliente inactivo">
                            <span></span>
                            Inactivo
                        </span>
                    @endif
                </header>

                <div class="tarjeta-cliente-contenido">
                    <div class="dato-cliente">
                        <i class="bi bi-person-vcard"></i>

                        <div>
                            <span>
                                @if ($cliente->tipo_documento === 'cedula')
                                    Cédula
                                @elseif ($cliente->tipo_documento === 'pasaporte')
                                    Pasaporte
                                @else
                                    Documento
                                @endif
                            </span>

                            <strong>{{ $cliente->documento }}</strong>
                        </div>
                    </div>

                    <div class="dato-cliente">
                        <i class="bi bi-envelope"></i>

                        <div>
                            <span>Correo electrónico</span>
                            <strong>{{ $cliente->email }}</strong>
                        </div>
                    </div>

                    <div class="dato-cliente">
                        <i class="bi bi-telephone"></i>

                        <div>
                            <span>Teléfono</span>
                            <strong>{{ $cliente->telefono }}</strong>
                        </div>
                    </div>

                    <div class="dato-cliente">
                        <i class="bi bi-globe-americas"></i>

                        <div>
                            <span>Nacionalidad</span>
                            <strong>
                                {{ $cliente->nacionalidad ?: 'Sin registrar' }}
                            </strong>
                        </div>
                    </div>
                </div>

                <div class="actividad-cliente">
                    <div>
                        <strong>{{ $cliente->reservas_count }}</strong>
                        <span>
                            {{ $cliente->reservas_count === 1
                                ? 'Reserva'
                                : 'Reservas' }}
                        </span>
                    </div>

                    <div>
                        <strong>{{ $cliente->grupos_count }}</strong>
                        <span>
                            {{ $cliente->grupos_count === 1
                                ? 'Viaje grupal'
                                : 'Viajes grupales' }}
                        </span>
                    </div>

                    <div>
                        <strong>{{ $cliente->pagos_count }}</strong>
                        <span>
                            {{ $cliente->pagos_count === 1
                                ? 'Pago'
                                : 'Pagos' }}
                        </span>
                    </div>
                </div>

                <footer class="tarjeta-cliente-pie">
                    <div class="documento-cliente">
                        @if ($cliente->archivo)
                            <a
                                href="{{ Storage::url($cliente->archivo) }}"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <i class="bi bi-paperclip"></i>
                                Ver documento
                            </a>
                        @else
                            <span>
                                <i class="bi bi-file-earmark"></i>
                                Sin documento adjunto
                            </span>
                        @endif
                    </div>

                    <div class="acciones-cliente">
                        <a
                            href="{{ route(
                                'clientes.edit',
                                $cliente->id
                            ) }}"
                            class="accion-cliente editar"
                            title="Editar cliente"
                        >
                            <i class="bi bi-pencil"></i>
                            Editar
                        </a>

                        @if (!$tieneMovimientos)
                            <form
                                action="{{ route(
                                    'clientes.destroy',
                                    $cliente->id
                                ) }}"
                                method="POST"
                                class="formulario-eliminar-cliente"
                                data-nombre="{{ $cliente->nombre_completo }}"
                            >
                                @csrf
                                @method('DELETE')

                                <button
                                    type="button"
                                    class="accion-cliente eliminar"
                                    title="Eliminar cliente"
                                >
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        @else
                            <button
                                type="button"
                                class="accion-cliente bloqueada"
                                title="Tiene historial registrado. Puedes cambiar su estado a inactivo."
                                disabled
                            >
                                <i class="bi bi-lock"></i>
                            </button>
                        @endif
                    </div>
                </footer>
            </article>
        @empty
            <div class="clientes-vacio">
                <i class="bi bi-people"></i>

                <strong>No hay clientes registrados</strong>

                <span>
                    Registra un cliente para comenzar a gestionar sus
                    viajes.
                </span>
            </div>
        @endforelse
    </section>

    <div id="clientesSinResultados" class="clientes-sin-resultados">
        <i class="bi bi-search"></i>
        <strong>No se encontraron clientes</strong>
        <span>Prueba con otro nombre, documento o estado.</span>
    </div>
</main>

<script>
    window.mensajesListadoClientes = {
        exito: @json(session('success')),
        error: @json(session('error'))
    };
</script>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="{{ asset('js/clientes-listado.js') }}"></script>
@endsection