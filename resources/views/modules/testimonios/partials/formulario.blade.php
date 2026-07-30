@php
    $esEdicion = $testimonio->exists;
@endphp

<link
    rel="stylesheet"
    href="{{ asset('css/testimonios-formulario.css') }}"
>

<form
    id="formularioTestimonio"
    action="{{ $esEdicion
        ? route('testimonios.update', $testimonio)
        : route('testimonios.store') }}"
    method="POST"
    enctype="multipart/form-data"
    novalidate
>
    @csrf

    @if($esEdicion)
        @method('PUT')
    @endif

    <div
        id="erroresServidorTestimonio"
        data-errores='@json($errors->all())'
    ></div>

    <div class="encabezado-formulario-testimonio">

        <div>
            <span class="formulario-etiqueta">
                Gestión de testimonios
            </span>

            <h2>
                {{ $esEdicion
                    ? 'Editar testimonio'
                    : 'Registrar testimonio' }}
            </h2>

            <p>
                Registra únicamente opiniones autorizadas y utiliza el estado
                “Publicado” cuando puedan mostrarse en la página principal.
            </p>
        </div>

        <span class="encabezado-icono-testimonio">
            <i class="bi bi-chat-quote"></i>
        </span>

    </div>

    <section class="seccion-formulario-testimonio">

        <div class="titulo-seccion-formulario">
            <span>
                <i class="bi bi-person"></i>
            </span>

            <div>
                <h3>Información del cliente</h3>
                <p>Datos que aparecerán junto a su experiencia.</p>
            </div>
        </div>

        <div class="rejilla-formulario-testimonio">

            <div class="grupo-testimonio">
                <label for="nombre">
                    Nombre del cliente
                    <span>*</span>
                </label>

                <input
                    type="text"
                    id="nombre"
                    name="nombre"
                    maxlength="100"
                    value="{{ old('nombre', $testimonio->nombre) }}"
                    placeholder="Ejemplo: María López"
                    required
                >

                <small class="mensaje-error"></small>
            </div>

            <div class="grupo-testimonio">
                <label for="destino">
                    Destino visitado
                </label>

                <input
                    type="text"
                    id="destino"
                    name="destino"
                    maxlength="150"
                    value="{{ old('destino', $testimonio->destino) }}"
                    placeholder="Ejemplo: Panamá"
                >

                <small class="mensaje-error"></small>
            </div>

            <div class="grupo-testimonio grupo-completo">
                <label for="comentario">
                    Experiencia del cliente
                    <span>*</span>
                </label>

                <textarea
                    id="comentario"
                    name="comentario"
                    rows="6"
                    maxlength="1000"
                    placeholder="Escribe el comentario proporcionado por el cliente..."
                    required
                >{{ old('comentario', $testimonio->comentario) }}</textarea>

                <div class="contador-comentario">
                    <small class="mensaje-error"></small>

                    <small>
                        <span id="cantidadComentario">0</span>/1000
                    </small>
                </div>
            </div>

        </div>

    </section>

    <section class="seccion-formulario-testimonio">

        <div class="titulo-seccion-formulario">
            <span>
                <i class="bi bi-star"></i>
            </span>

            <div>
                <h3>Calificación</h3>
                <p>Selecciona la valoración otorgada por el cliente.</p>
            </div>
        </div>

        <div class="selector-calificacion">

            @for($estrella = 5; $estrella >= 1; $estrella--)
                <input
                    type="radio"
                    id="estrella{{ $estrella }}"
                    name="calificacion"
                    value="{{ $estrella }}"
                    {{ (int) old(
                        'calificacion',
                        $testimonio->calificacion ?: 5
                    ) === $estrella ? 'checked' : '' }}
                >

                <label
                    for="estrella{{ $estrella }}"
                    title="{{ $estrella }} estrellas"
                >
                    <i class="bi bi-star-fill"></i>
                </label>
            @endfor

        </div>

        <p class="texto-calificacion" id="textoCalificacion">
            Excelente
        </p>

        <small
            class="mensaje-error"
            id="errorCalificacion"
        ></small>

    </section>

    <section class="seccion-formulario-testimonio">

        <div class="titulo-seccion-formulario">
            <span>
                <i class="bi bi-image"></i>
            </span>

            <div>
                <h3>Fotografía</h3>
                <p>
                    La imagen es opcional. Puedes utilizar una fotografía
                    autorizada del cliente o del viaje.
                </p>
            </div>
        </div>

        <div class="campo-fotografia-testimonio">

            <div class="vista-fotografia" id="vistaFotografia">

                @if($testimonio->foto)
                    <img
                        src="{{ asset('storage/' . $testimonio->foto) }}"
                        alt="Fotografía del testimonio"
                        id="imagenTestimonio"
                    >

                    <span
                        class="imagen-sin-contenido"
                        id="imagenSinContenido"
                        style="display: none;"
                    >
                        <i class="bi bi-image"></i>
                        Sin fotografía
                    </span>
                @else
                    <img
                        src=""
                        alt="Vista previa"
                        id="imagenTestimonio"
                        style="display: none;"
                    >

                    <span
                        class="imagen-sin-contenido"
                        id="imagenSinContenido"
                    >
                        <i class="bi bi-image"></i>
                        Sin fotografía
                    </span>
                @endif

            </div>

            <div class="controles-fotografia">

                <label
                    for="foto"
                    class="boton-seleccionar-foto"
                >
                    <i class="bi bi-upload"></i>
                    Seleccionar fotografía
                </label>

                <input
                    type="file"
                    id="foto"
                    name="foto"
                    accept=".jpg,.jpeg,.png,.webp"
                >

                <p>
                    Formatos permitidos: JPG, PNG o WEBP.
                    Tamaño máximo: 4 MB.
                </p>

                <small
                    class="mensaje-error"
                    id="errorFoto"
                ></small>

            </div>

        </div>

    </section>

    <section class="seccion-formulario-testimonio">

        <div class="titulo-seccion-formulario">
            <span>
                <i class="bi bi-eye"></i>
            </span>

            <div>
                <h3>Publicación</h3>
                <p>Controla cuándo y cómo aparecerá el testimonio.</p>
            </div>
        </div>

        <div class="rejilla-formulario-testimonio">

            <div class="grupo-testimonio">
                <label for="estado">
                    Estado
                    <span>*</span>
                </label>

                <select
                    id="estado"
                    name="estado"
                    required
                >
                    <option value="pendiente"
                        {{ old(
                            'estado',
                            $testimonio->estado ?: 'pendiente'
                        ) === 'pendiente' ? 'selected' : '' }}
                    >
                        Pendiente
                    </option>

                    <option value="publicado"
                        {{ old(
                            'estado',
                            $testimonio->estado
                        ) === 'publicado' ? 'selected' : '' }}
                    >
                        Publicado
                    </option>

                    <option value="oculto"
                        {{ old(
                            'estado',
                            $testimonio->estado
                        ) === 'oculto' ? 'selected' : '' }}
                    >
                        Oculto
                    </option>
                </select>

                <small class="mensaje-error"></small>
            </div>

            <div class="grupo-testimonio">
                <label for="orden">
                    Orden de aparición
                </label>

                <input
                    type="number"
                    id="orden"
                    name="orden"
                    min="0"
                    max="9999"
                    value="{{ old('orden', $testimonio->orden ?: 0) }}"
                >

                <small class="mensaje-error"></small>
            </div>

            <div class="grupo-testimonio grupo-completo">
                <label class="opcion-destacado">

                    <input
                        type="checkbox"
                        name="destacado"
                        value="1"
                        {{ old(
                            'destacado',
                            $testimonio->destacado
                        ) ? 'checked' : '' }}
                    >

                    <span class="interruptor-testimonio"></span>

                    <span>
                        <strong>Destacar testimonio</strong>
                        <small>
                            Los testimonios destacados aparecerán primero.
                        </small>
                    </span>

                </label>
            </div>

        </div>

    </section>

    <div class="acciones-formulario-testimonio">

        <a
            href="{{ route('testimonios.index') }}"
            class="boton-cancelar-testimonio"
            id="cancelarTestimonio"
        >
            <i class="bi bi-arrow-left"></i>
            Cancelar
        </a>

        <button
            type="submit"
            class="boton-guardar-testimonio"
        >
            <i class="bi bi-check-circle"></i>

            {{ $esEdicion
                ? 'Guardar cambios'
                : 'Registrar testimonio' }}
        </button>

    </div>

</form>