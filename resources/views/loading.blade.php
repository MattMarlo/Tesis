@extends('layouts.publico')

@section('title', 'Passion Travel | Viajes y paquetes turísticos')

@section('descripcion',
    'Descubre paquetes turísticos, destinos, ofertas y experiencias de viaje con Passion Travel.'
)

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/inicio-publico.css') }}?v={{ filemtime(public_path('css/inicio-publico.css')) }}">
@endsection

@section('content')

@php
    $paquetesCarrusel = $destacados->isNotEmpty()
        ? $destacados->take(4)
        : $destinos->take(4);
@endphp

{{-- Carrusel principal --}}
<section class="hero-publico" id="inicio">

    <div class="hero-carrusel" id="heroCarrusel">

        @forelse($paquetesCarrusel as $indice => $paquete)
            <article
                class="hero-diapositiva {{ $indice === 0 ? 'hero-activa' : '' }}"
                data-indice="{{ $indice }}"
            >
                <img
                    src="{{ asset('storage/' . $paquete->imagen) }}"
                    alt="{{ $paquete->nombre_paquete }}"
                    class="hero-imagen"
                >

                <div class="hero-sombra"></div>

                <div class="contenedor hero-contenido">

                    <span class="hero-etiqueta">
                        <i class="fa fa-star"></i>
                        {{ $paquete->destacado
                            ? 'Experiencia destacada'
                            : 'Descubre un nuevo destino' }}
                    </span>

                    <h1>
                        {{ $paquete->nombre_paquete }}
                    </h1>

                    <p>
                        {{ $paquete->descripcion_corta
                            ?: 'Vive una experiencia inolvidable y descubre nuevos lugares con Passion Travel.' }}
                    </p>

                    <div class="hero-datos">

                        <span>
                            <i class="fa fa-map-marker"></i>
                            {{ $paquete->ciudad_destino ?: $paquete->pais }}
                        </span>

                        <span>
                            <i class="fa fa-clock-o"></i>
                            {{ $paquete->dias }} días
                        </span>

                        @if($paquete->fecha_salida)
                            <span>
                                <i class="fa fa-calendar"></i>
                                {{ $paquete->fecha_salida->format('d/m/Y') }}
                            </span>
                        @endif

                    </div>

                    <div class="hero-acciones">

                        <a
                            href="{{ route('paquetes.detalle', $paquete->slug) }}"
                            class="boton-hero-principal"
                        >
                            Ver experiencia
                            <i class="fa fa-arrow-right"></i>
                        </a>

                        <a
                            href="#paquetes"
                            class="boton-hero-secundario"
                        >
                            Explorar paquetes
                        </a>

                    </div>

                </div>
            </article>
        @empty
            <article class="hero-diapositiva hero-activa hero-predeterminada">

                <img
                    src="{{ asset('assets/images/home/banner.jpg') }}"
                    alt="Viaja con Passion Travel"
                    class="hero-imagen"
                >

                <div class="hero-sombra"></div>

                <div class="contenedor hero-contenido">

                    <span class="hero-etiqueta">
                        <i class="fa fa-plane"></i>
                        Tu próxima aventura comienza aquí
                    </span>

                    <h1>Descubre el mundo con Passion Travel</h1>

                    <p>
                        Encuentra experiencias, destinos y paquetes pensados
                        para crear recuerdos inolvidables.
                    </p>

                    <div class="hero-acciones">
                        <a href="#paquetes" class="boton-hero-principal">
                            Explorar paquetes
                            <i class="fa fa-arrow-right"></i>
                        </a>
                    </div>

                </div>
            </article>
        @endforelse

        @if($paquetesCarrusel->count() > 1)
            <button
                type="button"
                class="control-hero control-anterior"
                id="heroAnterior"
                aria-label="Destino anterior"
            >
                <i class="fa fa-angle-left"></i>
            </button>

            <button
                type="button"
                class="control-hero control-siguiente"
                id="heroSiguiente"
                aria-label="Siguiente destino"
            >
                <i class="fa fa-angle-right"></i>
            </button>

            <div class="indicadores-hero">
                @foreach($paquetesCarrusel as $indice => $paquete)
                    <button
                        type="button"
                        class="indicador-hero {{ $indice === 0 ? 'indicador-activo' : '' }}"
                        data-diapositiva="{{ $indice }}"
                        aria-label="Ver destino {{ $indice + 1 }}"
                    ></button>
                @endforeach
            </div>
        @endif

    </div>

</section>

{{-- Buscador --}}
<section class="seccion-buscador">
    <div class="contenedor">

        <form class="buscador-paquetes" id="formularioBusqueda">

            <div class="encabezado-buscador">
                <span class="icono-buscador">
                    <i class="fa fa-search"></i>
                </span>

                <div>
                    <strong>Encuentra tu próximo viaje</strong>
                    <span>Busca entre nuestros paquetes disponibles</span>
                </div>
            </div>

            <div class="campo-buscador">
                <label for="buscarDestino">
                    Destino o paquete
                </label>

                <div class="campo-con-icono">
                    <i class="fa fa-map-marker"></i>

                    <input
                        type="text"
                        id="buscarDestino"
                        placeholder="Ejemplo: Cartagena"
                        autocomplete="off"
                    >
                </div>
            </div>

            <div class="campo-buscador">
                <label for="buscarCategoria">Tipo de viaje</label>

                <div class="campo-con-icono">
                    <i class="fa fa-tags"></i>

                    <select id="buscarCategoria">
                        <option value="">Todas las categorías</option>

                        @foreach($categorias as $categoria)
                            <option value="{{ $categoria }}">
                                {{ $categoria }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="campo-buscador">
                <label for="buscarSalida">Ciudad de salida</label>

                <div class="campo-con-icono">
                    <i class="fa fa-plane"></i>

                    <input
                        type="text"
                        id="buscarSalida"
                        placeholder="Ciudad de salida"
                        autocomplete="off"
                    >
                </div>
            </div>

            <button type="submit" class="boton-buscar">
                <i class="fa fa-search"></i>
                Buscar viajes
            </button>

        </form>

    </div>
</section>

{{-- Paquetes --}}
<section class="seccion-publica seccion-paquetes" id="paquetes">
    <div class="contenedor">

        <div class="cabecera-seccion">

            <div>
                <span class="subtitulo-seccion">Experiencias para ti</span>
                <h2>Paquetes turísticos disponibles</h2>

                <p>
                    Explora diferentes destinos y encuentra una experiencia
                    que se adapte a tu próxima aventura.
                </p>
            </div>

            <div class="contador-resultados">
                <strong id="cantidadResultados">
                    {{ $destinos->count() }}
                </strong>
                <span>paquetes encontrados</span>
            </div>

        </div>

        <div class="filtros-rapidos">
            <button
                type="button"
                class="filtro-rapido filtro-activo"
                data-categoria=""
            >
                Todos
            </button>

            @foreach($categorias as $categoria)
                <button
                    type="button"
                    class="filtro-rapido"
                    data-categoria="{{ $categoria }}"
                >
                    {{ $categoria }}
                </button>
            @endforeach
        </div>

        <div class="rejilla-paquetes" id="rejillaPaquetes">

            @forelse($destinos as $paquete)
                @php
                    $precioVisible = $paquete->precio_promocional
                        ?: $paquete->precio;
                @endphp

                <article
                    class="tarjeta-paquete-publico"
                    data-nombre="{{ $paquete->nombre_paquete }}"
                    data-destino="{{ $paquete->ciudad_destino }} {{ $paquete->pais }}"
                    data-salida="{{ $paquete->ciudad_salida }}"
                    data-categoria="{{ $paquete->categoria }}"
                >

                    <div class="paquete-imagen-contenedor">

                        <img
                            src="{{ asset('storage/' . $paquete->imagen) }}"
                            alt="{{ $paquete->nombre_paquete }}"
                            class="paquete-imagen"
                            loading="lazy"
                            role="button"
                            tabindex="0"
                            aria-label="Ampliar imagen de {{ $paquete->nombre_paquete }}"
                        >

                        <div class="paquete-etiquetas">

                            @if($paquete->destacado)
                                <span class="distintivo destacado">
                                    <i class="fa fa-star"></i>
                                    Destacado
                                </span>
                            @endif

                            @if($paquete->precio_promocional)
                                <span class="distintivo promocion">
                                    Oferta
                                </span>
                            @endif

                        </div>

                        <button
                            type="button"
                            class="boton-favorito"
                            aria-label="Agregar a favoritos"
                            data-paquete="{{ $paquete->nombre_paquete }}"
                        >
                            <i class="fa fa-heart-o"></i>
                        </button>

                        @if($paquete->categoria)
                            <span class="categoria-imagen">
                                {{ $paquete->categoria }}
                            </span>
                        @endif

                    </div>

                    <div class="paquete-contenido">

                        <div class="paquete-ruta">
                            <i class="fa fa-map-marker"></i>

                            <span>
                                {{ $paquete->ciudad_salida ?: 'Salida por confirmar' }}
                                <i class="fa fa-long-arrow-right"></i>
                                {{ $paquete->ciudad_destino ?: $paquete->pais }}
                            </span>
                        </div>

                        <h3>{{ $paquete->nombre_paquete }}</h3>

                        <p class="paquete-descripcion">
                            {{ $paquete->descripcion_corta
                                ?: 'Descubre todos los detalles de esta experiencia turística.' }}
                        </p>

                        <div class="paquete-caracteristicas">

                            <span>
                                <i class="fa fa-clock-o"></i>
                                {{ $paquete->dias }} días

                                @if($paquete->noches)
                                    / {{ $paquete->noches }} noches
                                @endif
                            </span>

                            <span>
                                <i class="fa fa-users"></i>
                                {{ $paquete->cupos_disponibles }} cupos
                            </span>

                            @if($paquete->fecha_salida)
                                <span>
                                    <i class="fa fa-calendar"></i>
                                    {{ $paquete->fecha_salida->format('d/m/Y') }}
                                </span>
                            @endif

                        </div>

                        <div class="paquete-pie">

                            <div class="paquete-precio">
                                <span>Desde</span>

                                @if($paquete->precio_promocional)
                                    <del>
                                        {{ $paquete->moneda }}
                                        ${{ number_format($paquete->precio, 2) }}
                                    </del>
                                @endif

                                <strong>
                                    <small>{{ $paquete->moneda }}</small>
                                    ${{ number_format($precioVisible, 2) }}
                                </strong>

                                <span>por persona</span>
                                <span>aplica descuento para niños y tercera edad</span>
                            </div>

                            <a
                                href="{{ route('paquetes.detalle', $paquete->slug) }}"
                                class="boton-ver-paquete"
                            >
                                Ver detalles
                                <i class="fa fa-arrow-right"></i>
                            </a>

                        </div>

                    </div>

                </article>
            @empty
                <div class="sin-paquetes">
                    <i class="fa fa-suitcase"></i>
                    <h3>Próximamente tendremos nuevas experiencias</h3>

                    <p>
                        En este momento no existen paquetes publicados.
                    </p>
                </div>
            @endforelse

        </div>

        <div class="sin-resultados" id="sinResultados">
            <i class="fa fa-search"></i>
            <h3>No encontramos paquetes con esos datos</h3>

            <p>
                Intenta cambiar el destino, la categoría o la ciudad de salida.
            </p>

            <button type="button" id="limpiarBusqueda">
                Mostrar todos los paquetes
            </button>
        </div>

    </div>
</section>

{{-- Información de la empresa --}}
<section class="seccion-publica seccion-nosotros" id="nosotros">
    <div class="contenedor nosotros-contenido">

        <div class="nosotros-imagenes">

            <div class="imagen-nosotros imagen-principal">
                <img
                    src="{{ asset('assets/images/gallary/landing_yate.jpg') }}"
                    alt="Experiencias de viaje"
                    loading="lazy"
                >
            </div>

            <div class="imagen-nosotros imagen-secundaria">
                <img
                    src="{{ asset('assets/images/gallary/landig_viaje.jpg') }}"
                    alt="Destinos turísticos"
                    loading="lazy"
                >
            </div>

            <div class="sello-experiencia">
                <i class="fa fa-globe"></i>
                <span>
                    <strong>Viajes</strong>
                    hechos para ti
                </span>
            </div>

        </div>

        <div class="nosotros-texto">

            <span class="subtitulo-seccion">Quiénes somos</span>

			<h2>Viajes preparados con dedicación y amor</h2>

			<p class="texto-principal-nosotros">
				Passion Travel es una agencia de viajes ubicada en Salcedo,
				Ecuador. Nació por iniciativa de una profesional que identificó
				la oportunidad de ayudar a otras personas a descubrir nuevos
				destinos y disfrutar experiencias organizadas con dedicación y
				responsabilidad.
			</p>

			<p>
				La agencia se caracteriza por brindar una atención confiable,
				eficiente y cercana. Cada viaje se prepara cuidando los detalles
				y pensando en las necesidades de sus clientes. Más que ofrecer
				paquetes turísticos, Passion Travel busca crear experiencias
				especiales, planificadas con compromiso y, sobre todo, con amor
				por lo que hace.
			</p>

			<p>
				Su propósito es acompañar a cada viajero desde la elección del
				destino hasta la realización del viaje, proporcionando
				información clara y una atención que genere seguridad y
				confianza.
			</p>

            <div class="valores-nosotros">

                <div class="valor-nosotros">
                    <i class="fa fa-heart"></i>

                    <div>
                        <strong>Atención personalizada</strong>
                        <span>
                            Orientación durante la consulta y planificación.
                        </span>
                    </div>
                </div>

                <div class="valor-nosotros">
                    <i class="fa fa-shield"></i>

                    <div>
                        <strong>Información clara</strong>
                        <span>
                            Conoce los detalles antes de elegir tu viaje.
                        </span>
                    </div>
                </div>

                <div class="valor-nosotros">
                    <i class="fa fa-paper-plane"></i>

                    <div>
                        <strong>Comunicación directa</strong>
                        <span>
                            Atención mediante nuestro asistente en WhatsApp.
                        </span>
                    </div>
                </div>

            </div>

            <a href="#ubicacion" class="boton-secundario">
                Conocer más sobre nosotros
                <i class="fa fa-arrow-right"></i>
            </a>

        </div>

    </div>
</section>

{{-- Servicios --}}
<section class="seccion-publica seccion-servicios" id="beneficios">
    <div class="contenedor">

        <div class="cabecera-seccion cabecera-centrada">
            <div>
                <span class="subtitulo-seccion">Pensamos en tu experiencia</span>
                <h2>Servicios para planificar tu viaje</h2>

                <p>
                    Consulta información y encuentra diferentes alternativas
                    desde un solo lugar.
                </p>
            </div>
        </div>

        <div class="rejilla-servicios">

            <article class="tarjeta-servicio">
                <span class="servicio-numero">01</span>

                <div class="servicio-icono">
                    <i class="fa fa-plane"></i>
                </div>

                <h3>Paquetes turísticos</h3>

                <p>
                    Consulta destinos, fechas, itinerarios y servicios
                    incluidos en cada experiencia.
                </p>
            </article>

            <article class="tarjeta-servicio">
                <span class="servicio-numero">02</span>

                <div class="servicio-icono">
                    <i class="fa fa-building"></i>
                </div>

                <h3>Hospedaje</h3>

                <p>
                    Encuentra información sobre los hoteles contemplados
                    dentro de los paquetes disponibles.
                </p>
            </article>

            <article class="tarjeta-servicio">
                <span class="servicio-numero">03</span>

                <div class="servicio-icono">
                    <i class="fa fa-comments"></i>
                </div>

                <h3>Asistencia en línea</h3>

                <p>
                    Comunícate con nuestro asistente para consultar
                    disponibilidad o iniciar una prerreserva.
                </p>
            </article>

            <article class="tarjeta-servicio">
                <span class="servicio-numero">04</span>

                <div class="servicio-icono">
                    <i class="fa fa-calendar-check-o"></i>
                </div>

                <h3>Planificación</h3>

                <p>
                    Revisa las fechas, duración y actividades programadas
                    antes de elegir tu próxima experiencia.
                </p>
            </article>

        </div>

    </div>
</section>

{{-- Galería --}}
<section class="seccion-publica seccion-galeria" id="destinos">
    <div class="contenedor">

        <div class="cabecera-seccion">

            <div>
                <span class="subtitulo-seccion">Momentos para recordar</span>
                <h2>Galería de experiencias</h2>

                <p>
                    Una mirada a los destinos y experiencias que pueden formar
                    parte de tu próxima aventura.
                </p>
            </div>

            <div class="controles-galeria">
                <button
                    type="button"
                    id="galeriaAnterior"
                    aria-label="Imagen anterior"
                >
                    <i class="fa fa-angle-left"></i>
                </button>

                <button
                    type="button"
                    id="galeriaSiguiente"
                    aria-label="Siguiente imagen"
                >
                    <i class="fa fa-angle-right"></i>
                </button>
            </div>

        </div>

        <div class="galeria-ventana">
            <div class="galeria-carril" id="galeriaCarril">

				<figure class="galeria-elemento">
					<img
						src="{{ asset('assets/images/viajes/viaje-punta-cana.jpg') }}"
						alt="Viaje organizado por Passion Travel a Punta Cana"
						loading="lazy"
					>

					<figcaption>
						<span>Experiencia internacional</span>
						<strong>Punta Cana</strong>
						<small>
							Momentos compartidos por nuestros viajeros.
						</small>
					</figcaption>
				</figure>

				<figure class="galeria-elemento">
					<img
						src="{{ asset('assets/images/viajes/viaje-brasil.jpg') }}"
						alt="Viaje organizado por Passion Travel a Brasil"
						loading="lazy"
					>

					<figcaption>
						<span>Destino internacional</span>
						<strong>Brasil</strong>
						<small>
							Una experiencia grupal llena de alegría y recuerdos.
						</small>
					</figcaption>
				</figure>

				<figure class="galeria-elemento">
					<img
						src="{{ asset('assets/images/viajes/viaje-mexico-basilica.jpg') }}"
						alt="Viajeros de Passion Travel en la Basílica de Guadalupe"
						loading="lazy"
					>

					<figcaption>
						<span>Turismo cultural y religioso</span>
						<strong>Basílica de Guadalupe</strong>
						<small>
							Un recorrido especial por uno de los lugares más
							representativos de México.
						</small>
					</figcaption>
				</figure>

				<figure class="galeria-elemento">
					<img
						src="{{ asset('assets/images/viajes/viaje-mexico-teotihuacan.jpg') }}"
						alt="Viaje de Passion Travel a México"
						loading="lazy"
					>

					<figcaption>
						<span>Historia y cultura</span>
						<strong>México</strong>
						<small>
							Nuestros viajeros descubriendo lugares llenos de historia.
						</small>
					</figcaption>
				</figure>

				<figure class="galeria-elemento">
					<img
						src="{{ asset('assets/images/viajes/viaje-panama.jpg') }}"
						alt="Viaje organizado por Passion Travel a Panamá"
						loading="lazy"
					>

					<figcaption>
						<span>Experiencia grupal</span>
						<strong>Panamá</strong>
						<small>
							Una nueva aventura organizada junto a Passion Travel.
						</small>
					</figcaption>
				</figure>

			</div>
        </div>

    </div>
</section>

{{-- Testimonios publicados --}}
@if($testimonios->isNotEmpty())
    <section
        class="seccion-publica seccion-testimonios"
        id="testimonios"
    >
        <div class="contenedor">

            <div class="cabecera-seccion cabecera-centrada">
                <div>
                    <span class="subtitulo-seccion">
                        Opiniones de viajeros
                    </span>

                    <h2>Experiencias compartidas</h2>

                    <p>
                        Conoce las experiencias de quienes han viajado
                        junto a Passion Travel.
                    </p>
                </div>
            </div>

            <div
                class="testimonios-carrusel"
                id="testimoniosCarrusel"
            >

                @foreach($testimonios as $indice => $testimonio)
                    <article
                        class="testimonio {{
                            $indice === 0
                                ? 'testimonio-activo'
                                : ''
                        }}"
                    >
                        <div class="comillas-testimonio">
                            “
                        </div>

                        <div class="estrellas-testimonio">

                            @for(
                                $estrella = 1;
                                $estrella <= 5;
                                $estrella++
                            )
                                <i class="fa {{
                                    $estrella <=
                                    $testimonio->calificacion
                                        ? 'fa-star'
                                        : 'fa-star-o'
                                }}"></i>
                            @endfor

                        </div>

                        <p>
                            “{{ $testimonio->comentario }}”
                        </p>

                        <div class="cliente-testimonio">

                            @if($testimonio->foto)
                                <img
                                    src="{{ asset(
                                        'storage/' .
                                        $testimonio->foto
                                    ) }}"
                                    alt="{{ $testimonio->nombre }}"
                                    class="foto-testimonio-publico"
                                >
                            @else
                                <span class="avatar-testimonio">
                                    {{ mb_strtoupper(
                                        mb_substr(
                                            $testimonio->nombre,
                                            0,
                                            1
                                        )
                                    ) }}
                                </span>
                            @endif

                            <div>
                                <strong>
                                    {{ $testimonio->nombre }}
                                </strong>

                                <span>
                                    @if($testimonio->destino)
                                        Experiencia en
                                        {{ $testimonio->destino }}
                                    @else
                                        Viajero de Passion Travel
                                    @endif
                                </span>
                            </div>

                        </div>

                    </article>
                @endforeach

                @if($testimonios->count() > 1)
                    <div class="controles-testimonios">

                        <button
                            type="button"
                            id="testimonioAnterior"
                            aria-label="Testimonio anterior"
                        >
                            <i class="fa fa-angle-left"></i>
                        </button>

                        <div id="indicadoresTestimonios"></div>

                        <button
                            type="button"
                            id="testimonioSiguiente"
                            aria-label="Siguiente testimonio"
                        >
                            <i class="fa fa-angle-right"></i>
                        </button>

                    </div>
                @endif

            </div>

        </div>
    </section>
@endif

{{-- Ubicación --}}
{{-- Ubicación y contacto --}}
<section class="seccion-publica seccion-ubicacion" id="ubicacion">
    <div class="contenedor">

        <div class="ubicacion-contenido">

            <div class="informacion-ubicacion">

                <span class="subtitulo-seccion">Visítanos</span>

                <h2>Estamos ubicados en Salcedo</h2>

                <p>
                    Visita nuestra agencia o comunícate con nosotros para
                    recibir información sobre paquetes turísticos, destinos
                    y opciones de viaje.
                </p>

                <div class="dato-ubicacion">
                    <i class="fa fa-map-marker"></i>

                    <div>
                        <span>Dirección</span>
                        <strong>Salcedo, Cotopaxi, Ecuador</strong>
                    </div>
                </div>

                <div class="dato-ubicacion">
                    <i class="fa fa-clock-o"></i>

                    <div>
                        <span>Lunes a viernes</span>
                        <strong>
                            9:00 a. m. – 1:00 p. m. y
                            3:00 p. m. – 6:00 p. m.
                        </strong>
                    </div>
                </div>

                <div class="dato-ubicacion">
                    <i class="fa fa-calendar"></i>

                    <div>
                        <span>Fin de semana</span>
                        <strong>
                            Sábado: 9:00 a. m. – 12:00 p. m.<br>
                            Domingo: cerrado
                        </strong>
                    </div>
                </div>

                <div class="dato-ubicacion">
                    <i class="fa fa-phone"></i>

                    <div>
                        <span>Teléfono</span>

                        <a href="tel:+593984443833">
                            <strong>098 444 3833</strong>
                        </a>
                    </div>
                </div>

                <div class="dato-ubicacion">
                    <i class="fa fa-envelope"></i>

                    <div>
                        <span>Correo electrónico</span>

                        <a href="mailto:passiontravel48@gmail.com">
                            <strong>passiontravel48@gmail.com</strong>
                        </a>
                    </div>
                </div>

                <div class="botones-ubicacion">

                    <a
                        href="https://maps.app.goo.gl/BcySuXQbntDDHPZY8"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="boton-secundario"
                    >
                        <i class="fa fa-map-marker"></i>
                        Cómo llegar
                    </a>

                    

                </div>

            </div>

            <div class="mapa-contenedor">

                <iframe
                    src="https://www.google.com/maps?q=Passion+Travel,+Salcedo,+Cotopaxi,+Ecuador&output=embed"
                    title="Ubicación de Passion Travel en Salcedo"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    allowfullscreen
                ></iframe>

            </div>

        </div>

    </div>
</section>

{{-- Preguntas frecuentes --}}
<section class="seccion-publica seccion-preguntas" id="preguntas">
    <div class="contenedor">

        <div class="cabecera-seccion cabecera-centrada">
            <div>
                <span class="subtitulo-seccion">Resolvemos tus dudas</span>
                <h2>Preguntas frecuentes</h2>
            </div>
        </div>

        <div class="lista-preguntas">

            <article class="pregunta">
                <button type="button" class="pregunta-boton">
                    <span>¿Cómo puedo consultar un paquete turístico?</span>
                    <i class="fa fa-plus"></i>
                </button>

                <div class="pregunta-respuesta">
                    <p>
                        Puedes revisar los detalles del paquete y comunicarte
                        mediante WhatsApp para solicitar información adicional.
                    </p>
                </div>
            </article>

            <article class="pregunta">
                <button type="button" class="pregunta-boton">
                    <span>¿Dónde puedo revisar las fechas disponibles?</span>
                    <i class="fa fa-plus"></i>
                </button>

                <div class="pregunta-respuesta">
                    <p>
                        La fecha de salida y regreso se encuentra dentro de la
                        información de cada paquete publicado.
                    </p>
                </div>
            </article>

            <article class="pregunta">
                <button type="button" class="pregunta-boton">
                    <span>¿Cómo se realiza una prerreserva?</span>
                    <i class="fa fa-plus"></i>
                </button>

                <div class="pregunta-respuesta">
                    <p>
                        Nuestro asistente en WhatsApp recopilará la información
                        necesaria para iniciar el proceso de prerreserva.
                    </p>
                </div>
            </article>

            <article class="pregunta">
                <button type="button" class="pregunta-boton">
                    <span>¿Qué servicios incluye cada viaje?</span>
                    <i class="fa fa-plus"></i>
                </button>

                <div class="pregunta-respuesta">
                    <p>
                        Cada paquete cuenta con un apartado que explica los
                        servicios incluidos, no incluidos y el itinerario.
                    </p>
                </div>
            </article>

        </div>

    </div>
</section>

@endsection

@section('scripts')
    <script src="{{ asset('js/inicio-publico.js') }}?v={{ filemtime(public_path('js/inicio-publico.js')) }}"></script>
@endsection
