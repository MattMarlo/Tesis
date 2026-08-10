@extends('layouts.main')

@section('title', $titulo)

@section('header', 'Paquetes turísticos')

@section('content')

<link
    href="{{ asset('css/paquetes-listado.css') }}"
    rel="stylesheet"
>

<div class="pagina-paquetes">

    <div class="encabezado-listado">
        <div>
            <span class="encabezado-etiqueta">
                Catálogo turístico
            </span>

            <h1>Paquetes turísticos</h1>

            <p>
                Administra la información que se muestra
                en la página pública.
            </p>
        </div>

        <a
            href="{{ route('destinos.create') }}"
            class="btn-nuevo-paquete"
        >
            <i class="bi bi-plus-lg"></i>
            Nuevo paquete
        </a>
    </div>

    <div class="panel-filtros">
        <div class="campo-busqueda">
            <i class="bi bi-search"></i>

            <input
                type="search"
                id="buscarPaquete"
                placeholder="Buscar por nombre, país o ciudad..."
                autocomplete="off"
            >
        </div>

        <div class="campo-filtro">
            <label for="filtrarCategoria">
                Categoría
            </label>

            <select id="filtrarCategoria">
                <option value="">Todas</option>

                @foreach(
                    $destinos
                        ->pluck('categoria')
                        ->filter()
                        ->unique()
                        ->sort()
                    as $categoria
                )
                    <option value="{{ Str::lower($categoria) }}">
                        {{ $categoria }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="campo-filtro">
            <label for="filtrarEstado">
                Estado
            </label>

            <select id="filtrarEstado">
                <option value="">Todos</option>
                <option value="publicado">Publicado</option>
                <option value="borrador">Borrador</option>
                <option value="no_disponible">
                    No disponible
                </option>
            </select>
        </div>
    </div>

    <div class="resumen-resultados">
        <p>
            Mostrando
            <strong id="cantidadResultados">
                {{ $destinos->count() }}
            </strong>
            de
            <strong>{{ $destinos->count() }}</strong>
            paquetes
        </p>
    </div>

    <div id="contenedorPaquetes" class="grid-paquetes">

        @forelse($destinos as $destino)
            @php
                $nombrePaquete =
                    $destino->nombre_paquete
                    ?: $destino->pais;

                $estado =
                    $destino->estado_publicacion
                    ?: 'borrador';

                $textoBusqueda = Str::lower(
                    implode(' ', [
                        $nombrePaquete,
                        $destino->pais,
                        $destino->ciudad_destino,
                        $destino->categoria,
                        $destino->etiqueta,
                    ])
                );
            @endphp

            <article
                class="tarjeta-paquete"
                data-paquete-id="{{ $destino->id }}"
                data-busqueda="{{ $textoBusqueda }}"
                data-categoria="{{ Str::lower(
                    $destino->categoria ?? ''
                ) }}"
                data-estado="{{ $estado }}"
            >
                <div class="tarjeta-imagen">
                    <img
                        src="{{ $destino->imagen
                            ? asset('storage/' . $destino->imagen)
                            : asset('assets/images/packages/p1.jpg') }}"
                        alt="{{ $nombrePaquete }}"
                    >

                    <div class="tarjeta-etiquetas">
                        <span class="estado estado-{{ $estado }}">
                            {{ match($estado) {
                                'publicado' => 'Publicado',
                                'no_disponible' => 'No disponible',
                                default => 'Borrador',
                            } }}
                        </span>

                        @if($destino->destacado)
                            <span class="etiqueta-destacado">
                                <i class="bi bi-star-fill"></i>
                                Destacado
                            </span>
                        @endif
                    </div>

                    <span class="etiqueta-cupos">
                        <i class="bi bi-people"></i>
                        {{ $destino->cupos_disponibles }} cupos disponibles
                    </span>
                </div>

                <div class="tarjeta-contenido">
                    <div class="tarjeta-categoria">
                        {{ $destino->categoria ?: 'Sin categoría' }}
                    </div>

                    <h2>{{ $nombrePaquete }}</h2>

                    <p class="tarjeta-destino">
                        <i class="bi bi-geo-alt"></i>

                        {{ $destino->ciudad_destino
                            ? $destino->ciudad_destino . ', '
                            : '' }}

                        {{ $destino->pais }}
                    </p>

                    <p class="tarjeta-descripcion">
                        {{ Str::limit(
                            $destino->descripcion_corta
                            ?: $destino->etiqueta,
                            115
                        ) }}
                    </p>

                    <div class="tarjeta-datos">
                        <span>
                            <i class="bi bi-calendar3"></i>

                            @if($destino->fecha_salida)
                                {{ $destino->fecha_salida->format('d/m/Y') }}
                            @else
                                Fecha pendiente
                            @endif
                        </span>

                        <span>
                            <i class="bi bi-clock"></i>
                            {{ $destino->dias }} días
                        </span>
                    </div>

                    <div class="tarjeta-pie">
                        <div class="tarjeta-precio">
                            <small>Desde</small>

                            @if($destino->precio_promocional)
                                <span class="precio-anterior">
                                    {{ $destino->moneda ?? 'USD' }}
                                    {{ number_format(
                                        $destino->precio,
                                        2
                                    ) }}
                                </span>

                                <strong>
                                    {{ $destino->moneda ?? 'USD' }}
                                    {{ number_format(
                                        $destino->precio_promocional,
                                        2
                                    ) }}
                                </strong>
                            @else
                                <strong>
                                    {{ $destino->moneda ?? 'USD' }}
                                    {{ number_format(
                                        $destino->precio,
                                        2
                                    ) }}
                                </strong>
                            @endif
                        </div>

                        <div class="tarjeta-acciones">
                            <button
                                type="button"
                                class="btn-vista-rapida"
                                data-paquete-id="{{ $destino->id }}"
                                title="Vista rápida"
                            >
                                <i class="bi bi-eye"></i>
                            </button>

                            <a
                                href="{{ route(
                                    'destinos.edit',
                                    $destino->id
                                ) }}"
                                class="btn-editar-paquete"
                                title="Editar paquete"
                            >
                                <i class="bi bi-pencil"></i>
                            </a>

                            <form
                                action="{{ route(
                                    'destinos.destroy',
                                    $destino->id
                                ) }}"
                                method="POST"
                                class="formulario-eliminar-paquete"
                            >
                                @csrf
                                @method('DELETE')

                                <button
                                    type="button"
                                    class="btn-eliminar-paquete"
                                    data-nombre="{{ $nombrePaquete }}"
                                    title="Eliminar paquete"
                                >
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </article>
        @empty
            <div class="estado-vacio">
                <i class="bi bi-suitcase-lg"></i>

                <h2>No hay paquetes registrados</h2>

                <p>
                    Registra el primer paquete turístico
                    para comenzar a construir el catálogo.
                </p>

                <a
                    href="{{ route('destinos.create') }}"
                    class="btn-nuevo-paquete"
                >
                    <i class="bi bi-plus-lg"></i>
                    Registrar paquete
                </a>
            </div>
        @endforelse
    </div>

    <div
        id="sinResultados"
        class="estado-vacio"
        hidden
    >
        <i class="bi bi-search"></i>

        <h2>No encontramos resultados</h2>

        <p>
            Prueba con otros términos o limpia los filtros.
        </p>

        <button
            type="button"
            id="limpiarFiltros"
            class="btn-limpiar-filtros"
        >
            Limpiar filtros
        </button>
    </div>
</div>

{{-- Modal de vista rápida --}}

<div
    class="modal fade"
    id="modalVistaPaquete"
    tabindex="-1"
    aria-labelledby="tituloModalPaquete"
    aria-hidden="true"
>
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content modal-paquete">
            <div class="modal-header">
                <div>
                    <span id="modalCategoria"></span>

                    <h2
                        class="modal-title"
                        id="tituloModalPaquete"
                    ></h2>
                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Cerrar"
                ></button>
            </div>

            <div class="modal-body">
                <div class="modal-resumen-grid">
                    <img
                        id="modalImagen"
                        src=""
                        alt="Imagen del paquete"
                    >

                    <div class="modal-informacion">
                        <p id="modalDescripcion"></p>

                        <div class="modal-datos">
                            <div>
                                <i class="bi bi-geo-alt"></i>

                                <span>
                                    <small>Ruta</small>
                                    <strong id="modalRuta"></strong>
                                </span>
                            </div>

                            <div>
                                <i class="bi bi-calendar3"></i>

                                <span>
                                    <small>Fechas</small>
                                    <strong id="modalFechas"></strong>
                                </span>
                            </div>

                            <div>
                                <i class="bi bi-clock"></i>

                                <span>
                                    <small>Duración</small>
                                    <strong id="modalDuracion"></strong>
                                </span>
                            </div>

                            <div>
                                <i class="bi bi-cash-coin"></i>

                                <span>
                                    <small>Precio</small>
                                    <strong id="modalPrecio"></strong>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-listas">
                    <div>
                        <h3>
                            <i class="bi bi-check-circle"></i>
                            Incluye
                        </h3>

                        <ul id="modalIncluye"></ul>
                    </div>

                    <div>
                        <h3>
                            <i class="bi bi-x-circle"></i>
                            No incluye
                        </h3>

                        <ul id="modalNoIncluye"></ul>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button
                    type="button"
                    class="btn-cerrar-modal"
                    data-bs-dismiss="modal"
                >
                    Cerrar
                </button>

                <a
                    href="#"
                    id="modalEditar"
                    class="btn-editar-modal"
                >
                    <i class="bi bi-pencil"></i>
                    Editar paquete
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    window.paquetesRegistrados =
        {{ Illuminate\Support\Js::from($destinos) }};

    window.rutaEditarPaquete =
        {{ Illuminate\Support\Js::from(
            url('/destinos/edit')
        ) }};

    window.imagenPaquetePredeterminada =
        {{ Illuminate\Support\Js::from(
            asset('assets/images/packages/p1.jpg')
        ) }};

    window.rutaAlmacenamiento =
        {{ Illuminate\Support\Js::from(
            asset('storage')
        ) }};
</script>

@endsection

@section('scripts')

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script src="{{ asset('js/paquetes-listado.js') }}"></script>

@endsection
