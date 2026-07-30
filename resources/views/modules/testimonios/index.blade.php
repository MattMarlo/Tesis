@extends('layouts.main')

@section('title', 'Testimonios')

@section('header', 'Testimonios')

@section('content')

<link
    rel="stylesheet"
    href="{{ asset('css/testimonios-listado.css') }}"
>

<section class="modulo-testimonios">

    <div class="encabezado-listado-testimonios">

        <div>
            <span>Contenido público</span>
            <h2>Testimonios de clientes</h2>

            <p>
                Administra las experiencias que aparecerán en la página
                principal de Passion Travel.
            </p>
        </div>

        <a
            href="{{ route('testimonios.create') }}"
            class="boton-nuevo-testimonio"
        >
            <i class="bi bi-plus-circle"></i>
            Registrar testimonio
        </a>

    </div>

    <div class="filtros-testimonios">

        <div class="campo-busqueda-testimonio">
            <i class="bi bi-search"></i>

            <input
                type="text"
                id="buscarTestimonio"
                placeholder="Buscar por cliente o destino..."
            >
        </div>

        <select id="filtrarEstado">
            <option value="">Todos los estados</option>
            <option value="publicado">Publicado</option>
            <option value="pendiente">Pendiente</option>
            <option value="oculto">Oculto</option>
        </select>

        <div class="cantidad-testimonios">
            <strong id="cantidadTestimonios">
                {{ $testimonios->count() }}
            </strong>

            <span>resultados</span>
        </div>

    </div>

    <div class="rejilla-testimonios" id="rejillaTestimonios">

        @forelse($testimonios as $testimonio)
            <article
                class="tarjeta-testimonio-admin"
                data-nombre="{{ $testimonio->nombre }}"
                data-destino="{{ $testimonio->destino }}"
                data-estado="{{ $testimonio->estado }}"
            >

                <div class="testimonio-admin-superior">

                    <div class="cliente-testimonio-admin">

                        @if($testimonio->foto)
                            <img
                                src="{{ asset(
                                    'storage/' . $testimonio->foto
                                ) }}"
                                alt="{{ $testimonio->nombre }}"
                            >
                        @else
                            <span class="avatar-testimonio-admin">
                                {{ mb_strtoupper(
                                    mb_substr($testimonio->nombre, 0, 1)
                                ) }}
                            </span>
                        @endif

                        <div>
                            <h3>{{ $testimonio->nombre }}</h3>

                            <span>
                                <i class="bi bi-geo-alt"></i>
                                {{ $testimonio->destino
                                    ?: 'Destino no especificado' }}
                            </span>
                        </div>

                    </div>

                    <div class="insignias-testimonio">

                        <span
                            class="estado-testimonio estado-{{ $testimonio->estado }}"
                        >
                            {{ ucfirst($testimonio->estado) }}
                        </span>

                        @if($testimonio->destacado)
                            <span class="testimonio-destacado">
                                <i class="bi bi-star-fill"></i>
                                Destacado
                            </span>
                        @endif

                    </div>

                </div>

                <div class="calificacion-testimonio-admin">

                    @for($estrella = 1; $estrella <= 5; $estrella++)
                        <i class="bi {{
                            $estrella <= $testimonio->calificacion
                                ? 'bi-star-fill'
                                : 'bi-star'
                        }}"></i>
                    @endfor

                    <span>
                        {{ $testimonio->calificacion }}/5
                    </span>

                </div>

                <blockquote>
                    “{{ $testimonio->comentario }}”
                </blockquote>

                <div class="datos-testimonio-admin">

                    <span>
                        <i class="bi bi-sort-numeric-down"></i>
                        Orden: {{ $testimonio->orden }}
                    </span>

                    <span>
                        <i class="bi bi-calendar3"></i>
                        {{ $testimonio->created_at->format('d/m/Y') }}
                    </span>

                </div>

                <div class="acciones-testimonio-admin">

                    <a
                        href="{{ route(
                            'testimonios.edit',
                            $testimonio
                        ) }}"
                        class="boton-editar-testimonio"
                    >
                        <i class="bi bi-pencil-square"></i>
                        Editar
                    </a>

                    <form
                        action="{{ route(
                            'testimonios.destroy',
                            $testimonio
                        ) }}"
                        method="POST"
                        class="formulario-eliminar-testimonio"
                        data-nombre="{{ $testimonio->nombre }}"
                    >
                        @csrf
                        @method('DELETE')

                        <button
                            type="submit"
                            class="boton-eliminar-testimonio"
                        >
                            <i class="bi bi-trash"></i>
                            Eliminar
                        </button>
                    </form>

                </div>

            </article>
        @empty
            <div class="sin-testimonios">
                <i class="bi bi-chat-quote"></i>

                <h3>No existen testimonios registrados</h3>

                <p>
                    Registra el primer testimonio para mostrarlo en la
                    página pública.
                </p>

                <a href="{{ route('testimonios.create') }}">
                    Registrar testimonio
                </a>
            </div>
        @endforelse

    </div>

    <div class="sin-resultados-testimonios" id="sinResultadosTestimonios">
        <i class="bi bi-search"></i>
        <h3>No se encontraron testimonios</h3>
        <p>Intenta cambiar el texto o el estado seleccionado.</p>

        <button type="button" id="limpiarFiltrosTestimonios">
            Mostrar todos
        </button>
    </div>

</section>

@endsection

@section('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="{{ asset('js/testimonios-listado.js') }}"></script>
@endsection