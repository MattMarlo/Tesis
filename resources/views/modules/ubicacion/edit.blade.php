@extends('layouts.main')

@section('title', 'Ubicación')
@section('header', 'Ubicación')

@section('content')

<link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css"
>
<link
    rel="stylesheet"
    href="{{ asset('css/ubicacion-formulario.css') }}?v={{
        filemtime(public_path('css/ubicacion-formulario.css'))
    }}"
>

<form
    action="{{ route('ubicacion.update') }}"
    method="POST"
    class="formulario-ubicacion"
>
    @csrf
    @method('PUT')

    <div class="encabezado-ubicacion">
        <div>
            <span>Administración</span>
            <h2>Ubicación de la agencia</h2>
            <p>
                Estos datos se mostrarán en la sección “Visítanos” de la
                página pública.
            </p>
        </div>

        <span class="icono-ubicacion">
            <i class="bi bi-geo-alt-fill"></i>
        </span>
    </div>

    <section class="tarjeta-ubicacion">
        <div class="titulo-tarjeta-ubicacion">
            <span><i class="bi bi-pin-map"></i></span>

            <div>
                <h3>Datos de ubicación</h3>
                <p>
                    Al guardar, también se actualizarán el mapa y el botón
                    “Cómo llegar”.
                </p>
            </div>
        </div>

        <div class="selector-mapa-ubicacion">
            <div class="instruccion-mapa-ubicacion">
                <div>
                    <i class="bi bi-cursor-fill"></i>

                    <span>
                        <strong>Selecciona el punto exacto</strong>
                        Haz clic sobre el mapa o arrastra el marcador.
                    </span>
                </div>

                <span class="coordenadas-ubicacion" id="coordenadasUbicacion">
                    Punto aún no seleccionado
                </span>
            </div>

            <div
                id="selectorMapaUbicacion"
                data-latitud="{{ old('latitud', $ubicacion->latitud) }}"
                data-longitud="{{ old('longitud', $ubicacion->longitud) }}"
                aria-label="Mapa para seleccionar la ubicación de la agencia"
            ></div>

            <div
                class="estado-direccion-mapa"
                id="estadoDireccionMapa"
                aria-live="polite"
            >
                <i class="bi bi-info-circle"></i>
                <span>
                    Al seleccionar un punto se completarán automáticamente
                    los datos inferiores.
                </span>
            </div>

            <input
                type="hidden"
                id="latitud"
                name="latitud"
                value="{{ old('latitud', $ubicacion->latitud) }}"
            >

            <input
                type="hidden"
                id="longitud"
                name="longitud"
                value="{{ old('longitud', $ubicacion->longitud) }}"
            >

            @error('latitud')
                <p class="error-ubicacion error-mapa">{{ $message }}</p>
            @enderror

            @error('longitud')
                <p class="error-ubicacion error-mapa">{{ $message }}</p>
            @enderror
        </div>

        <div class="rejilla-ubicacion">
            <div class="campo-ubicacion">
                <label for="localidad">
                    Localidad <span>*</span>
                </label>

                <input
                    type="text"
                    id="localidad"
                    name="localidad"
                    maxlength="100"
                    value="{{ old('localidad', $ubicacion->localidad) }}"
                    placeholder="Ejemplo: Salcedo"
                    required
                >

                <small>Completa el título “Estamos ubicados en...”.</small>

                @error('localidad')
                    <p class="error-ubicacion">{{ $message }}</p>
                @enderror
            </div>

            <div class="campo-ubicacion">
                <label for="direccion">
                    Dirección visible <span>*</span>
                </label>

                <input
                    type="text"
                    id="direccion"
                    name="direccion"
                    maxlength="255"
                    value="{{ old('direccion', $ubicacion->direccion) }}"
                    placeholder="Ejemplo: Salcedo, Cotopaxi, Ecuador"
                    required
                >

                <small>
                    Aparecerá junto al icono de dirección en la landing.
                </small>

                @error('direccion')
                    <p class="error-ubicacion">{{ $message }}</p>
                @enderror
            </div>

            <div class="campo-ubicacion campo-completo">
                <label for="consulta_mapa">
                    Referencia para Google Maps <span>*</span>
                </label>

                <input
                    type="text"
                    id="consulta_mapa"
                    name="consulta_mapa"
                    maxlength="255"
                    value="{{ old(
                        'consulta_mapa',
                        $ubicacion->consulta_mapa
                    ) }}"
                    placeholder="Ejemplo: Passion Travel, Salcedo, Cotopaxi, Ecuador"
                    required
                >

                <small>
                    Usa el nombre del negocio y una dirección completa para
                    ubicar el marcador con mayor precisión.
                </small>

                @error('consulta_mapa')
                    <p class="error-ubicacion">{{ $message }}</p>
                @enderror
            </div>

            <div class="campo-ubicacion campo-completo">
                <label for="enlace_mapa">
                    Enlace de Google Maps <span>*</span>
                </label>

                <input
                    type="url"
                    id="enlace_mapa"
                    name="enlace_mapa"
                    maxlength="2048"
                    value="{{ old('enlace_mapa', $ubicacion->enlace_mapa) }}"
                    placeholder="https://maps.app.goo.gl/..."
                    required
                >

                <small>
                    Este enlace abrirá el botón “Cómo llegar”. Puedes copiarlo
                    desde la opción Compartir de Google Maps.
                </small>

                @error('enlace_mapa')
                    <p class="error-ubicacion">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </section>

    <div class="acciones-ubicacion">
        <a
            href="{{ url('/#ubicacion') }}"
            target="_blank"
            rel="noopener noreferrer"
            class="boton-vista-publica"
        >
            <i class="bi bi-box-arrow-up-right"></i>
            Ver página pública
        </a>

        <button type="submit" class="boton-guardar-ubicacion">
            <i class="bi bi-check-circle"></i>
            Guardar ubicación
        </button>
    </div>
</form>

@endsection

@section('scripts')
    <script
        src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"
    ></script>
    <script src="{{ asset('js/ubicacion-formulario.js') }}?v={{
        filemtime(public_path('js/ubicacion-formulario.js'))
    }}"></script>
@endsection
