@extends('layouts.publico')

@section('title', $destino->nombre_paquete . ' | Passion Travel')

@section('descripcion',
    $destino->descripcion_corta
        ?: 'Conoce todos los detalles de este paquete turístico.'
)

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/paquete-detalle.css') }}">
@endsection

@section('content')

@php
    $precioActual = $destino->precio_promocional
        ?: $destino->precio;

    $mensajeTelegram = 'Hola, deseo recibir información sobre el paquete: '
        . $destino->nombre_paquete;

    $enlaceTelegram = 'https://t.me/PassionTravelBot?text=' .
        urlencode($mensajeTelegram);
@endphp

{{-- Navegación secundaria --}}
<section class="migas-navegacion">
    <div class="contenedor">

        <a href="{{ url('/') }}">
            <i class="fa fa-home"></i>
            Inicio
        </a>

        <i class="fa fa-angle-right"></i>

        <a href="{{ url('/#paquetes') }}">
            Paquetes
        </a>

        <i class="fa fa-angle-right"></i>

        <span>{{ $destino->nombre_paquete }}</span>

    </div>
</section>

{{-- Portada --}}
<section class="detalle-portada">

    <img
        src="{{ asset('storage/' . $destino->imagen) }}"
        alt="{{ $destino->nombre_paquete }}"
        class="detalle-portada-imagen"
    >

    <div class="detalle-portada-sombra"></div>

    <div class="contenedor detalle-portada-contenido">

        <div class="detalle-etiquetas">

            @if($destino->categoria)
                <span class="etiqueta-categoria">
                    {{ $destino->categoria }}
                </span>
            @endif

            @if($destino->destacado)
                <span class="etiqueta-destacado">
                    <i class="fa fa-star"></i>
                    Experiencia destacada
                </span>
            @endif

            @if($destino->precio_promocional)
                <span class="etiqueta-oferta">
                    Oferta especial
                </span>
            @endif

        </div>

        <h1>{{ $destino->nombre_paquete }}</h1>

        <p class="detalle-ruta">
            <i class="fa fa-map-marker"></i>

            {{ $destino->ciudad_salida ?: 'Salida por confirmar' }}

            <i class="fa fa-long-arrow-right"></i>

            {{ $destino->ciudad_destino ?: $destino->pais }}
        </p>

        @if($destino->descripcion_corta)
            <p class="detalle-resumen">
                {{ $destino->descripcion_corta }}
            </p>
        @endif

        <div class="detalle-portada-datos">

            <span>
                <i class="fa fa-clock-o"></i>

                <strong>{{ $destino->dias }} días</strong>

                @if($destino->noches)
                    / {{ $destino->noches }} noches
                @endif
            </span>

            <span>
                <i class="fa fa-users"></i>
                <strong>{{ $destino->capacidad }}</strong> cupos
            </span>

            @if($destino->fecha_salida)
                <span>
                    <i class="fa fa-calendar"></i>
                    Salida:
                    <strong>
                        {{ $destino->fecha_salida->format('d/m/Y') }}
                    </strong>
                </span>
            @endif

        </div>

    </div>

</section>

{{-- Navegación interna --}}
<nav class="detalle-navegacion-interna" id="navegacionDetalle">
    <div class="contenedor">

        <a href="#descripcion" class="enlace-detalle activo">
            Descripción
        </a>

        @if($destino->incluye || $destino->no_incluye)
            <a href="#servicios" class="enlace-detalle">
                Incluye
            </a>
        @endif

        @if($destino->itinerario)
            <a href="#itinerario" class="enlace-detalle">
                Itinerario
            </a>
        @endif

        <a href="#informacion" class="enlace-detalle">
            Información
        </a>

        @if($destino->condiciones)
            <a href="#condiciones" class="enlace-detalle">
                Condiciones
            </a>
        @endif

    </div>
</nav>

{{-- Contenido --}}
<section class="detalle-contenido">
    <div class="contenedor detalle-columnas">

        <div class="detalle-columna-principal">

            {{-- Resumen --}}
            <section class="tarjeta-detalle" id="descripcion">

                <div class="titulo-tarjeta">
                    <span class="titulo-icono">
                        <i class="fa fa-suitcase"></i>
                    </span>

                    <div>
                        <span>Conoce esta experiencia</span>
                        <h2>Descripción del paquete</h2>
                    </div>
                </div>

                @if($destino->descripcion_corta)
                    <p class="descripcion-destacada">
                        {{ $destino->descripcion_corta }}
                    </p>
                @endif

                @if($destino->descripcion)
                    <div class="texto-detalle">
                        {!! nl2br(e($destino->descripcion)) !!}
                    </div>
                @else
                    <div class="texto-detalle">
                        Consulta todos los servicios, fechas y actividades
                        disponibles para esta experiencia.
                    </div>
                @endif

            </section>

            {{-- Datos rápidos --}}
            <section class="rejilla-datos-viaje">

                <article class="dato-viaje">
                    <span class="dato-viaje-icono">
                        <i class="fa fa-plane"></i>
                    </span>

                    <div>
                        <span>Ciudad de salida</span>
                        <strong>
                            {{ $destino->ciudad_salida ?: 'Por confirmar' }}
                        </strong>
                    </div>
                </article>

                <article class="dato-viaje">
                    <span class="dato-viaje-icono">
                        <i class="fa fa-map-marker"></i>
                    </span>

                    <div>
                        <span>Destino</span>
                        <strong>
                            {{ $destino->ciudad_destino ?: $destino->pais }}
                        </strong>
                    </div>
                </article>

                <article class="dato-viaje">
                    <span class="dato-viaje-icono">
                        <i class="fa fa-calendar"></i>
                    </span>

                    <div>
                        <span>Fecha de salida</span>
                        <strong>
                            {{ $destino->fecha_salida
                                ? $destino->fecha_salida->format('d/m/Y')
                                : 'Por confirmar' }}
                        </strong>
                    </div>
                </article>

                <article class="dato-viaje">
                    <span class="dato-viaje-icono">
                        <i class="fa fa-calendar-check-o"></i>
                    </span>

                    <div>
                        <span>Fecha de regreso</span>
                        <strong>
                            {{ $destino->fecha_regreso
                                ? $destino->fecha_regreso->format('d/m/Y')
                                : 'Por confirmar' }}
                        </strong>
                    </div>
                </article>

            </section>

            {{-- Incluye y no incluye --}}
            @if($destino->incluye || $destino->no_incluye)
                <section class="tarjeta-detalle" id="servicios">

                    <div class="titulo-tarjeta">
                        <span class="titulo-icono">
                            <i class="fa fa-list"></i>
                        </span>

                        <div>
                            <span>Servicios considerados</span>
                            <h2>¿Qué incluye el viaje?</h2>
                        </div>
                    </div>

                    <div class="columnas-servicios">

                        @if($destino->incluye)
                            <div class="lista-detalle lista-incluye">

                                <h3>
                                    <i class="fa fa-check-circle"></i>
                                    El paquete incluye
                                </h3>

                                <ul>
                                    @foreach($destino->incluye as $servicio)
                                        @if($servicio)
                                            <li>
                                                <i class="fa fa-check"></i>
                                                <span>{{ $servicio }}</span>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>

                            </div>
                        @endif

                        @if($destino->no_incluye)
                            <div class="lista-detalle lista-no-incluye">

                                <h3>
                                    <i class="fa fa-times-circle"></i>
                                    El paquete no incluye
                                </h3>

                                <ul>
                                    @foreach($destino->no_incluye as $servicio)
                                        @if($servicio)
                                            <li>
                                                <i class="fa fa-times"></i>
                                                <span>{{ $servicio }}</span>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>

                            </div>
                        @endif

                    </div>

                </section>
            @endif

            {{-- Itinerario --}}
            @if($destino->itinerario)
                <section class="tarjeta-detalle" id="itinerario">

                    <div class="titulo-tarjeta">
                        <span class="titulo-icono">
                            <i class="fa fa-map"></i>
                        </span>

                        <div>
                            <span>Actividades programadas</span>
                            <h2>Itinerario del viaje</h2>
                        </div>
                    </div>

                    <div class="linea-itinerario">

                        @foreach($destino->itinerario as $indice => $dia)
                            <article class="dia-itinerario">

                                <button
                                    type="button"
                                    class="encabezado-dia {{ $indice === 0 ? 'dia-abierto' : '' }}"
                                    aria-expanded="{{ $indice === 0 ? 'true' : 'false' }}"
                                >
                                    <span class="numero-dia">
                                        {{ $indice + 1 }}
                                    </span>

                                    <span class="nombre-dia">
                                        <small>Día {{ $indice + 1 }}</small>

                                        <strong>
                                            {{ $dia['titulo']
                                                ?? 'Actividades programadas' }}
                                        </strong>
                                    </span>

                                    <i class="fa fa-angle-down"></i>
                                </button>

                                <div
                                    class="contenido-dia"
                                    @if($indice !== 0) style="display: none;" @endif
                                >
                                    <p>
                                        {!! nl2br(e(
                                            $dia['actividades']
                                            ?? 'Actividades por confirmar.'
                                        )) !!}
                                    </p>
                                </div>

                            </article>
                        @endforeach

                    </div>

                </section>
            @endif

            {{-- Información adicional --}}
            <section class="tarjeta-detalle" id="informacion">

                <div class="titulo-tarjeta">
                    <span class="titulo-icono">
                        <i class="fa fa-info-circle"></i>
                    </span>

                    <div>
                        <span>Datos importantes</span>
                        <h2>Información del viaje</h2>
                    </div>
                </div>

                <div class="tabla-informacion">

                    <div class="fila-informacion">
                        <span>
                            <i class="fa fa-globe"></i>
                            País
                        </span>

                        <strong>
                            {{ $destino->pais ?: 'Por confirmar' }}
                        </strong>
                    </div>

                    <div class="fila-informacion">
                        <span>
                            <i class="fa fa-tags"></i>
                            Categoría
                        </span>

                        <strong>
                            {{ $destino->categoria ?: 'General' }}
                        </strong>
                    </div>

                    <div class="fila-informacion">
                        <span>
                            <i class="fa fa-clock-o"></i>
                            Duración
                        </span>

                        <strong>
                            {{ $destino->dias }} días

                            @if($destino->noches)
                                y {{ $destino->noches }} noches
                            @endif
                        </strong>
                    </div>

                    @if($destino->aerolinea)
                        <div class="fila-informacion">
                            <span>
                                <i class="fa fa-plane"></i>
                                Aerolínea
                            </span>

                            <strong>{{ $destino->aerolinea }}</strong>
                        </div>
                    @endif

                    @if($destino->hotel)
                        <div class="fila-informacion">
                            <span>
                                <i class="fa fa-building"></i>
                                Hospedaje
                            </span>

                            <strong>{{ $destino->hotel }}</strong>
                        </div>
                    @endif

                    <div class="fila-informacion">
                        <span>
                            <i class="fa fa-users"></i>
                            Cupos disponibles
                        </span>

                        <strong>{{ $destino->capacidad }}</strong>
                    </div>

                </div>

            </section>

            {{-- Condiciones --}}
            @if($destino->condiciones)
                <section class="tarjeta-detalle" id="condiciones">

                    <div class="titulo-tarjeta">
                        <span class="titulo-icono">
                            <i class="fa fa-file-text-o"></i>
                        </span>

                        <div>
                            <span>Antes de reservar</span>
                            <h2>Condiciones importantes</h2>
                        </div>
                    </div>

                    <div class="aviso-condiciones">
                        <i class="fa fa-exclamation-circle"></i>

                        <p>
                            Revisa esta información antes de iniciar el proceso
                            de prerreserva.
                        </p>
                    </div>

                    <div class="texto-detalle">
                        {!! nl2br(e($destino->condiciones)) !!}
                    </div>

                </section>
            @endif

        </div>

        {{-- Columna de precio --}}
        <aside class="detalle-columna-lateral">

            <div class="tarjeta-reserva" id="tarjetaReserva">

                <div class="reserva-encabezado">
                    <span>Precio por persona</span>

                    @if($destino->precio_promocional)
                        <span class="descuento-reserva">
                            Oferta
                        </span>
                    @endif
                </div>

                @if($destino->precio_promocional)
                    <div class="precio-anterior">
                        Antes:
                        <del>
                            {{ $destino->moneda }}
                            ${{ number_format($destino->precio, 2) }}
                        </del>
                    </div>
                @endif

                <div class="precio-reserva">
                    <small>{{ $destino->moneda }}</small>

                    <strong>
                        ${{ number_format($precioActual, 2) }}
                    </strong>
                </div>

                <span class="precio-aclaracion">
                    Precio referencial por viajero
                </span>

                <div class="separador-reserva"></div>

                <div class="reserva-dato">
                    <i class="fa fa-map-marker"></i>

                    <div>
                        <span>Destino</span>
                        <strong>
                            {{ $destino->ciudad_destino ?: $destino->pais }}
                        </strong>
                    </div>
                </div>

                <div class="reserva-dato">
                    <i class="fa fa-calendar"></i>

                    <div>
                        <span>Fecha de salida</span>
                        <strong>
                            {{ $destino->fecha_salida
                                ? $destino->fecha_salida->format('d/m/Y')
                                : 'Por confirmar' }}
                        </strong>
                    </div>
                </div>

                <div class="reserva-dato">
                    <i class="fa fa-users"></i>

                    <div>
                        <span>Disponibilidad</span>
                        <strong>
                            {{ $destino->capacidad }} cupos
                        </strong>
                    </div>
                </div>

                <button
                    type="button"
                    class="boton-consultar-paquete"
                    id="consultarPaquete"
                    data-enlace="{{ $enlaceTelegram }}"
                    data-paquete="{{ $destino->nombre_paquete }}"
                >
                    <i class="fa fa-telegram"></i>

                    <span>
                        <small>Consulta disponibilidad</small>
                        Solicitar información
                    </span>
                </button>

                <p class="mensaje-reserva">
                    <i class="fa fa-shield"></i>
                    Te enviaremos a nuestro asistente de Telegram.
                </p>

            </div>

            <div class="tarjeta-ayuda">

                <span class="ayuda-icono">
                    <i class="fa fa-comments"></i>
                </span>

                <div>
                    <strong>¿Tienes alguna duda?</strong>

                    <p>
                        Nuestro asistente puede ayudarte con la información
                        de este paquete.
                    </p>

                    <a
                        href="{{ $enlaceTelegram }}"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        Iniciar conversación
                        <i class="fa fa-arrow-right"></i>
                    </a>
                </div>

            </div>

            <a href="{{ url('/#paquetes') }}" class="volver-paquetes">
                <i class="fa fa-arrow-left"></i>
                Volver a todos los paquetes
            </a>

        </aside>

    </div>
</section>

@endsection

@section('scripts')
    <script src="{{ asset('js/paquete-detalle.js') }}"></script>
@endsection
