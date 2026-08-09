@php
    $destino = $destinos ?? null;
    $esEdicion = !is_null($destino);

    $serviciosIncluidos = old(
        'incluye',
        $destino?->incluye ?: ['']
    );

    $serviciosNoIncluidos = old(
        'no_incluye',
        $destino?->no_incluye ?: ['']
    );

    $diasItinerario = old(
        'itinerario',
        $destino?->itinerario ?: [
            [
                'dia' => 1,
                'titulo' => '',
                'descripcion' => '',
                'actividades' => [],
            ]
        ]
    );
@endphp

<link
    href="{{ asset('css/paquetes-formulario.css') }}"
    rel="stylesheet"
>

<div
    id="erroresServidor"
    data-errores="{{ json_encode($errors->all()) }}"
    hidden
></div>

<form
    id="formularioPaquete"
    action="{{ $esEdicion
        ? route('destinos.update', $destino->id)
        : route('destinos.store') }}"
    method="POST"
    enctype="multipart/form-data"
    novalidate
>
    @csrf

    @if($esEdicion)
        @method('PUT')
    @endif

    <div class="encabezado-formulario">
        <div>
            <span class="encabezado-etiqueta">
                Gestión de paquetes
            </span>

            <h1>
                {{ $esEdicion
                    ? 'Editar paquete turístico'
                    : 'Nuevo paquete turístico' }}
            </h1>

            <p>
                Completa la información que se mostrará
                en el catálogo público.
            </p>
        </div>

        <div class="encabezado-acciones">
            <a
                href="{{ route('destinos') }}"
                class="btn-cancelar"
            >
                <i class="bi bi-arrow-left"></i>
                Cancelar
            </a>

            <button
                type="submit"
                class="btn-guardar"
                data-texto-carga="Guardando paquete..."
            >
                <i class="bi bi-check2-circle"></i>

                <span class="texto-boton">
                    {{ $esEdicion
                        ? 'Guardar cambios'
                        : 'Registrar paquete' }}
                </span>
            </button>
        </div>
    </div>

    <div class="formulario-contenido">

        {{-- Información general --}}
        <section class="seccion-formulario">
            <div class="seccion-titulo">
                <span class="seccion-icono">
                    <i class="bi bi-info-circle"></i>
                </span>

                <div>
                    <h2>Información general</h2>

                    <p>
                        Datos principales para identificar
                        el paquete turístico.
                    </p>
                </div>
            </div>

            <div class="campos-grid">
                <div class="campo campo-completo">
                    <label for="nombre_paquete">
                        Nombre del paquete
                        <span>*</span>
                    </label>

                    <input
                        type="text"
                        name="nombre_paquete"
                        id="nombre_paquete"
                        class="form-control"
                        value="{{ old(
                            'nombre_paquete',
                            $destino?->nombre_paquete
                        ) }}"
                        placeholder="Ej. Cancún todo incluido"
                        maxlength="150"
                    >

                    <small class="mensaje-error"></small>
                </div>

                <div class="campo">
                    <label for="etiqueta">
                        Etiqueta promocional
                        <span>*</span>
                    </label>

                    <input
                        type="text"
                        name="etiqueta"
                        id="etiqueta"
                        class="form-control"
                        value="{{ old(
                            'etiqueta',
                            $destino?->etiqueta
                        ) }}"
                        placeholder="Ej. Oferta, familiar o aventura"
                        maxlength="100"
                    >

                    <small class="mensaje-error"></small>
                </div>

                <div class="campo">
                    <label for="categoria">
                        Categoría
                        <span>*</span>
                    </label>

                    <select
                        name="categoria"
                        id="categoria"
                        class="form-select"
                    >
                        <option value="">
                            Selecciona una categoría
                        </option>

                        @foreach([
                            'Aventura',
                            'Cultural',
                            'Familiar',
                            'Luna de miel',
                            'Playa',
                            'Compras',
                            'Crucero',
                            'Todo incluido',
                            'Temporada',
                            'Otro'
                        ] as $categoria)
                            <option
                                value="{{ $categoria }}"
                                @selected(
                                    old(
                                        'categoria',
                                        $destino?->categoria
                                    ) === $categoria
                                )
                            >
                                {{ $categoria }}
                            </option>
                        @endforeach
                    </select>

                    <small class="mensaje-error"></small>
                </div>

                <div class="campo campo-completo">
                    <label for="descripcion_corta">
                        Descripción corta
                        <span>*</span>
                    </label>

                    <textarea
                        name="descripcion_corta"
                        id="descripcion_corta"
                        class="form-control"
                        rows="3"
                        maxlength="300"
                        placeholder="Resumen que aparecerá en la tarjeta del paquete"
                    >{{ old(
                        'descripcion_corta',
                        $destino?->descripcion_corta
                    ) }}</textarea>

                    <div class="campo-informacion">
                        <small class="mensaje-error"></small>

                        <small id="contadorDescripcion">
                            0 de 300 caracteres
                        </small>
                    </div>
                </div>

                <div class="campo campo-completo">
                    <label for="descripcion">
                        Descripción completa
                        <span>*</span>
                    </label>

                    <textarea
                        name="descripcion"
                        id="descripcion"
                        class="form-control"
                        rows="6"
                        placeholder="Describe la experiencia, atractivos y características del viaje"
                    >{{ old(
                        'descripcion',
                        $destino?->descripcion
                    ) }}</textarea>

                    <small class="mensaje-error"></small>
                </div>
            </div>
        </section>

        {{-- Ruta y fechas --}}
        <section class="seccion-formulario">
            <div class="seccion-titulo">
                <span class="seccion-icono">
                    <i class="bi bi-geo-alt"></i>
                </span>

                <div>
                    <h2>Ruta y fechas</h2>

                    <p>
                        Lugar de partida, destino y duración
                        del viaje.
                    </p>
                </div>
            </div>

            <div class="campos-grid">
                <div class="campo">
                    <label for="ciudad_salida">
                        Ciudad de salida
                        <span>*</span>
                    </label>

                    <input
                        type="text"
                        name="ciudad_salida"
                        id="ciudad_salida"
                        class="form-control"
                        pattern="[A-Za-zÁÉÍÓÚÜÑáéíóúüñÀÈÌÒÙàèìòùÇç\s.'’-]+"
                        value="{{ old(
                            'ciudad_salida',
                            $destino?->ciudad_salida
                        ) }}"
                        placeholder="Ej. Quito o Guayaquil"
                        maxlength="150"
                    >

                    <small class="mensaje-error"></small>
                </div>

                <div class="campo">
                    <label for="pais">
                        País de destino
                        <span>*</span>
                    </label>

                    <input
                        type="text"
                        name="pais"
                        id="pais"
                        class="form-control"
                        pattern="[A-Za-zÁÉÍÓÚÜÑáéíóúüñÀÈÌÒÙàèìòùÇç\s.'’-]+"
                        value="{{ old(
                            'pais',
                            $destino?->pais
                        ) }}"
                        placeholder="Ej. México"
                        maxlength="100"
                    >

                    <small class="mensaje-error"></small>
                </div>

                <div class="campo">
                    <label for="ciudad_destino">
                        Ciudad de destino
                        <span>*</span>
                    </label>

                    <input
                        type="text"
                        name="ciudad_destino"
                        id="ciudad_destino"
                        class="form-control"
                        pattern="[A-Za-zÁÉÍÓÚÜÑáéíóúüñÀÈÌÒÙàèìòùÇç\s.'’-]+"
                        value="{{ old(
                            'ciudad_destino',
                            $destino?->ciudad_destino
                        ) }}"
                        placeholder="Ej. Cancún"
                        maxlength="100"
                    >

                    <small class="mensaje-error"></small>
                </div>

                <div class="campo">
                    <label for="aerolinea">
                        Aerolínea
                    </label>

                    <input
                        type="text"
                        name="aerolinea"
                        id="aerolinea"
                        class="form-control"
                        value="{{ old(
                            'aerolinea',
                            $destino?->aerolinea
                        ) }}"
                        placeholder="Ej. Copa Airlines"
                        maxlength="120"
                    >
                </div>

                <div class="campo">
                    <label for="fecha_salida">
                        Fecha de salida
                        <span>*</span>
                    </label>

                    <input
                        type="date"
                        name="fecha_salida"
                        id="fecha_salida"
                        class="form-control"
                        min="{{ now()->format('Y-m-d') }}"
                        value="{{ old(
                            'fecha_salida',
                            $destino?->fecha_salida?->format('Y-m-d')
                        ) }}"
                    >

                    <small class="mensaje-error"></small>
                </div>

                <div class="campo">
                    <label for="fecha_regreso">
                        Fecha de regreso
                        <span>*</span>
                    </label>

                    <input
                        type="date"
                        name="fecha_regreso"
                        id="fecha_regreso"
                        class="form-control"
                        min="{{ old(
                            'fecha_salida',
                            $destino?->fecha_salida?->format('Y-m-d')
                        ) ?: now()->format('Y-m-d') }}"
                        value="{{ old(
                            'fecha_regreso',
                            $destino?->fecha_regreso?->format('Y-m-d')
                        ) }}"
                    >

                    <small class="mensaje-error"></small>
                </div>

                <div class="campo">
                    <label for="dias">
                        Cantidad de días
                        <span>*</span>
                    </label>

                    <input
                        type="number"
                        name="dias"
                        id="dias"
                        class="form-control"
                        value="{{ old(
                            'dias',
                            $destino?->dias
                        ) }}"
                        min="1"
                        max="365"
                        placeholder="Ej. 5"
                    >

                    <small class="mensaje-error"></small>
                </div>

                <div class="campo">
                    <label for="noches">
                        Cantidad de noches
                        <span>*</span>
                    </label>

                    <input
                        type="number"
                        name="noches"
                        id="noches"
                        class="form-control"
                        value="{{ old(
                            'noches',
                            $destino?->noches
                        ) }}"
                        min="0"
                        max="365"
                        placeholder="Ej. 4"
                    >

                    <small class="mensaje-error"></small>
                </div>

                <div class="campo campo-completo">
                    <label for="hotel">
                        Hotel o alojamiento
                    </label>

                    <input
                        type="text"
                        name="hotel"
                        id="hotel"
                        class="form-control"
                        value="{{ old(
                            'hotel',
                            $destino?->hotel
                        ) }}"
                        placeholder="Ej. Hotel Riu Cancún"
                        maxlength="150"
                    >
                </div>
            </div>
        </section>

        {{-- Precio y disponibilidad --}}
        <section class="seccion-formulario">
            <div class="seccion-titulo">
                <span class="seccion-icono">
                    <i class="bi bi-cash-coin"></i>
                </span>

                <div>
                    <h2>Precio y disponibilidad</h2>

                    <p>
                        Valores por persona y cantidad
                        de cupos disponibles.
                    </p>
                </div>
            </div>

            <div class="campos-grid tres-columnas">
                <div class="campo">
                    <label for="precio">
                        Precio normal
                        <span>*</span>
                    </label>

                    <input
                        type="number"
                        name="precio"
                        id="precio"
                        class="form-control"
                        value="{{ old(
                            'precio',
                            $destino?->precio
                        ) }}"
                        min="0.01"
                        step="0.01"
                        placeholder="0.00"
                    >

                    <small class="mensaje-error"></small>
                </div>

                <div class="campo">
                    <label for="precio_promocional">
                        Precio promocional
                    </label>

                    <input
                        type="number"
                        name="precio_promocional"
                        id="precio_promocional"
                        class="form-control"
                        value="{{ old(
                            'precio_promocional',
                            $destino?->precio_promocional
                        ) }}"
                        min="0.01"
                        step="0.01"
                        placeholder="Opcional"
                    >

                    <small class="mensaje-error"></small>
                </div>

                <div class="campo">
                    <label for="moneda">
                        Moneda
                        <span>*</span>
                    </label>

                    <select
                        name="moneda"
                        id="moneda"
                        class="form-select"
                    >
                        <option
                            value="USD"
                            @selected(
                                old(
                                    'moneda',
                                    $destino?->moneda ?? 'USD'
                                ) === 'USD'
                            )
                        >
                            USD - Dólar estadounidense
                        </option>

                        <option
                            value="EUR"
                            @selected(
                                old(
                                    'moneda',
                                    $destino?->moneda
                                ) === 'EUR'
                            )
                        >
                            EUR - Euro
                        </option>
                    </select>
                </div>

                <div class="campo">
                    <label for="capacidad">
                        Capacidad
                        <span>*</span>
                    </label>

                    <input
                        type="number"
                        name="capacidad"
                        id="capacidad"
                        class="form-control"
                        value="{{ old(
                            'capacidad',
                            $destino?->capacidad
                        ) }}"
                        min="1"
                        placeholder="Ej. 30"
                    >

                    <small class="mensaje-error"></small>
                </div>
            </div>
        </section>

        {{-- Servicios --}}
        <section class="seccion-formulario">
            <div class="seccion-titulo">
                <span class="seccion-icono">
                    <i class="bi bi-list-check"></i>
                </span>

                <div>
                    <h2>Servicios del paquete</h2>

                    <p>
                        Especifica qué incluye y qué no incluye
                        el precio publicado.
                    </p>
                </div>
            </div>

            <div class="listas-grid">
                <div class="lista-panel">
                    <div class="lista-encabezado">
                        <div>
                            <h3>
                                <i class="bi bi-check-circle"></i>
                                Incluye
                            </h3>

                            <p>Agrega al menos un servicio.</p>
                        </div>

                        <button
                            type="button"
                            class="btn-agregar"
                            id="agregarIncluye"
                        >
                            <i class="bi bi-plus-lg"></i>
                            Agregar
                        </button>
                    </div>

                    <div id="listaIncluye">
                        @foreach($serviciosIncluidos as $servicio)
                            <div class="item-lista">
                                <input
                                    type="text"
                                    name="incluye[]"
                                    class="form-control campo-incluye"
                                    value="{{ $servicio }}"
                                    placeholder="Ej. Boleto aéreo de ida y regreso"
                                    maxlength="255"
                                >

                                <button
                                    type="button"
                                    class="btn-eliminar-item"
                                    aria-label="Eliminar servicio"
                                >
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        @endforeach
                    </div>

                    <small
                        id="errorIncluye"
                        class="mensaje-error"
                    ></small>
                </div>

                <div class="lista-panel">
                    <div class="lista-encabezado">
                        <div>
                            <h3>
                                <i class="bi bi-x-circle"></i>
                                No incluye
                            </h3>

                            <p>Registra gastos o servicios adicionales.</p>
                        </div>

                        <button
                            type="button"
                            class="btn-agregar"
                            id="agregarNoIncluye"
                        >
                            <i class="bi bi-plus-lg"></i>
                            Agregar
                        </button>
                    </div>

                    <div id="listaNoIncluye">
                        @foreach($serviciosNoIncluidos as $servicio)
                            <div class="item-lista">
                                <input
                                    type="text"
                                    name="no_incluye[]"
                                    class="form-control"
                                    value="{{ $servicio }}"
                                    placeholder="Ej. Gastos personales"
                                    maxlength="255"
                                >

                                <button
                                    type="button"
                                    class="btn-eliminar-item"
                                    aria-label="Eliminar servicio"
                                >
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        {{-- Itinerario --}}
        <section class="seccion-formulario">
            <div class="seccion-titulo seccion-titulo-accion">
                <span class="seccion-icono">
                    <i class="bi bi-calendar2-week"></i>
                </span>

                <div>
                    <h2>Itinerario</h2>

                    <p>
                        Organiza las actividades que se realizarán
                        durante cada día.
                    </p>
                </div>

                <button
                    type="button"
                    class="btn-agregar"
                    id="agregarDia"
                >
                    <i class="bi bi-plus-lg"></i>
                    Agregar día
                </button>
            </div>

            <div id="listaItinerario">
                @foreach($diasItinerario as $indice => $dia)
                    <div class="dia-itinerario">
                        <div class="dia-cabecera">
                            <strong>
                                Día
                                <span class="numero-dia">
                                    {{ $dia['dia'] ?? $indice + 1 }}
                                </span>
                            </strong>

                            <button
                                type="button"
                                class="btn-eliminar-dia"
                            >
                                <i class="bi bi-trash"></i>
                                Eliminar
                            </button>
                        </div>

                        <div class="campos-grid">
                            <input
                                type="hidden"
                                name="itinerario[{{ $indice }}][dia]"
                                class="campo-dia"
                                value="{{ $dia['dia'] ?? $indice + 1 }}"
                            >

                            <div class="campo">
                                <label>
                                    Título del día
                                    <span>*</span>
                                </label>

                                <input
                                    type="text"
                                    name="itinerario[{{ $indice }}][titulo]"
                                    class="form-control titulo-dia"
                                    value="{{ $dia['titulo'] ?? '' }}"
                                    placeholder="Ej. Llegada y traslado al hotel"
                                    maxlength="150"
                                >
                            </div>

                            <div class="campo campo-completo">
                                <label>
                                    Descripción general del día
                                    <span>*</span>
                                </label>

                                <textarea
                                    name="itinerario[{{ $indice }}][descripcion]"
                                    class="form-control descripcion-dia"
                                    rows="3"
                                    placeholder="Describe el objetivo y la planificación general del día"
                                >{{ $dia['descripcion'] ?? '' }}</textarea>
                            </div>
                        </div>

                        <div class="actividades-dia">
                            <div class="actividades-cabecera">
                                <div>
                                    <h4>Actividades del día</h4>

                                    <p>
                                        Los horarios son opcionales. Marca
                                        únicamente las actividades que requieran
                                        preparación o coordinación de la agencia.
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    class="btn-agregar btn-agregar-actividad"
                                >
                                    <i class="bi bi-plus-lg"></i>
                                    Agregar actividad
                                </button>
                            </div>

                            <div class="lista-actividades">
                                @foreach(($dia['actividades'] ?? []) as $actividadIndice => $actividad)
                                    @php
                                        $requiereGestion = filter_var(
                                            $actividad['requiere_gestion'] ?? false,
                                            FILTER_VALIDATE_BOOLEAN
                                        );

                                        $uuidActividad = $actividad['uuid']
                                            ?? (string) \Illuminate\Support\Str::uuid();
                                    @endphp

                                    <div class="actividad-itinerario">
                                        <div class="actividad-cabecera">
                                            <strong>
                                                Actividad
                                                <span class="numero-actividad">
                                                    {{ $actividadIndice + 1 }}
                                                </span>
                                            </strong>

                                            <button
                                                type="button"
                                                class="btn-eliminar-actividad"
                                            >
                                                <i class="bi bi-trash"></i>
                                                Eliminar
                                            </button>
                                        </div>

                                        <input
                                            type="hidden"
                                            name="itinerario[{{ $indice }}][actividades][{{ $actividadIndice }}][uuid]"
                                            class="actividad-uuid"
                                            value="{{ $uuidActividad }}"
                                        >

                                        <div class="campos-grid">
                                            <div class="campo campo-completo">
                                                <label>
                                                    Nombre de la actividad
                                                    <span>*</span>
                                                </label>

                                                <input
                                                    type="text"
                                                    name="itinerario[{{ $indice }}][actividades][{{ $actividadIndice }}][nombre]"
                                                    class="form-control actividad-nombre"
                                                    value="{{ $actividad['nombre'] ?? '' }}"
                                                    placeholder="Ej. Traslado del aeropuerto al hotel"
                                                    maxlength="150"
                                                >

                                                <small class="mensaje-error"></small>
                                            </div>

                                            <div class="campo campo-completo">
                                                <label>Descripción</label>

                                                <textarea
                                                    name="itinerario[{{ $indice }}][actividades][{{ $actividadIndice }}][descripcion]"
                                                    class="form-control actividad-descripcion"
                                                    rows="2"
                                                    maxlength="1000"
                                                    placeholder="Información adicional de la actividad"
                                                >{{ $actividad['descripcion'] ?? '' }}</textarea>
                                            </div>

                                            <div class="campo">
                                                <label>Hora de inicio</label>

                                                <input
                                                    type="time"
                                                    name="itinerario[{{ $indice }}][actividades][{{ $actividadIndice }}][hora_inicio]"
                                                    class="form-control actividad-hora-inicio"
                                                    value="{{ $actividad['hora_inicio'] ?? '' }}"
                                                >

                                                <small class="mensaje-error"></small>
                                            </div>

                                            <div class="campo">
                                                <label>Hora de finalización</label>

                                                <input
                                                    type="time"
                                                    name="itinerario[{{ $indice }}][actividades][{{ $actividadIndice }}][hora_fin]"
                                                    class="form-control actividad-hora-fin"
                                                    value="{{ $actividad['hora_fin'] ?? '' }}"
                                                >

                                                <small class="mensaje-error"></small>
                                            </div>

                                            <div class="campo campo-completo">
                                                <label>Ubicación</label>

                                                <input
                                                    type="text"
                                                    name="itinerario[{{ $indice }}][actividades][{{ $actividadIndice }}][ubicacion]"
                                                    class="form-control actividad-ubicacion"
                                                    value="{{ $actividad['ubicacion'] ?? '' }}"
                                                    placeholder="Ej. Aeropuerto Internacional Mariscal Sucre"
                                                    maxlength="180"
                                                >
                                            </div>

                                            <div class="campo campo-completo campo-gestion">
                                                <input
                                                    type="hidden"
                                                    name="itinerario[{{ $indice }}][actividades][{{ $actividadIndice }}][requiere_gestion]"
                                                    class="actividad-requiere-gestion-oculto"
                                                    value="0"
                                                >

                                                <label class="opcion-check">
                                                    <input
                                                        type="checkbox"
                                                        name="itinerario[{{ $indice }}][actividades][{{ $actividadIndice }}][requiere_gestion]"
                                                        class="actividad-requiere-gestion"
                                                        value="1"
                                                        @checked($requiereGestion)
                                                    >

                                                    <span>
                                                        Esta actividad requiere gestión
                                                        o preparación de la agencia
                                                    </span>
                                                </label>
                                            </div>

                                            <div
                                                class="campo campo-completo contenedor-tipo-gestion"
                                                @if(!$requiereGestion) hidden @endif
                                            >
                                                <label>
                                                    Tipo de gestión
                                                    <span>*</span>
                                                </label>

                                                <select
                                                    name="itinerario[{{ $indice }}][actividades][{{ $actividadIndice }}][tipo_gestion]"
                                                    class="form-select actividad-tipo-gestion"
                                                    @disabled(!$requiereGestion)
                                                >
                                                    <option value="">
                                                        Selecciona el tipo de gestión
                                                    </option>

                                                    @foreach([
                                                        'reserva' => 'Reserva',
                                                        'entrada' => 'Entrada',
                                                        'guia' => 'Guía',
                                                        'alimentacion' => 'Alimentación',
                                                        'alojamiento' => 'Alojamiento',
                                                        'actividad' => 'Actividad',
                                                        'otro' => 'Otro',
                                                    ] as $tipoValor => $tipoTexto)
                                                        <option
                                                            value="{{ $tipoValor }}"
                                                            @selected(
                                                                ($actividad['tipo_gestion'] ?? '')
                                                                === $tipoValor
                                                            )
                                                        >
                                                            {{ $tipoTexto }}
                                                        </option>
                                                    @endforeach
                                                </select>

                                                <small class="mensaje-error"></small>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <small
                id="errorItinerario"
                class="mensaje-error"
            ></small>
        </section>

        {{-- Imagen y publicación --}}
        <section class="seccion-formulario">
            <div class="seccion-titulo">
                <span class="seccion-icono">
                    <i class="bi bi-image"></i>
                </span>

                <div>
                    <h2>Imagen y publicación</h2>

                    <p>
                        Configura la presentación y visibilidad
                        del paquete.
                    </p>
                </div>
            </div>

            <div class="campos-grid">
                <div class="campo">
                    <label for="imagen">
                        Imagen principal
                        @if(!$esEdicion)
                            <span>*</span>
                        @endif
                    </label>

                    <input
                        type="file"
                        name="imagen"
                        id="imagen"
                        class="form-control"
                        accept=".jpg,.jpeg,.png,.webp"
                    >

                    <small>
                        Formatos permitidos: JPG, PNG o WEBP.
                        Tamaño máximo: 5 MB.
                    </small>

                    <small class="mensaje-error"></small>
                </div>

                <div class="vista-imagen">
                    <div
                        id="contenedorVistaImagen"
                        class="{{ $destino?->imagen
                            ? 'con-imagen'
                            : '' }}"
                    >
                        @if($destino?->imagen)
                            <img
                                id="vistaImagen"
                                src="{{ asset(
                                    'storage/' . $destino->imagen
                                ) }}"
                                alt="Imagen actual del paquete"
                            >
                        @else
                            <img
                                id="vistaImagen"
                                src=""
                                alt="Vista previa"
                                hidden
                            >

                            <div id="imagenVacia">
                                <i class="bi bi-image"></i>
                                <span>Vista previa de la imagen</span>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="campo">
                    <label for="estado_publicacion">
                        Estado de publicación
                        <span>*</span>
                    </label>

                    <select
                        name="estado_publicacion"
                        id="estado_publicacion"
                        class="form-select"
                    >
                        <option
                            value="borrador"
                            @selected(
                                old(
                                    'estado_publicacion',
                                    $destino?->estado_publicacion
                                    ?? 'borrador'
                                ) === 'borrador'
                            )
                        >
                            Borrador
                        </option>

                        <option
                            value="publicado"
                            @selected(
                                old(
                                    'estado_publicacion',
                                    $destino?->estado_publicacion
                                ) === 'publicado'
                            )
                        >
                            Publicado
                        </option>

                        <option
                            value="no_disponible"
                            @selected(
                                old(
                                    'estado_publicacion',
                                    $destino?->estado_publicacion
                                ) === 'no_disponible'
                            )
                        >
                            No disponible
                        </option>
                    </select>
                </div>

                <div class="campo campo-completo">
                    <label for="condiciones">
                        Condiciones y observaciones
                    </label>

                    <textarea
                        name="condiciones"
                        id="condiciones"
                        class="form-control"
                        rows="5"
                        placeholder="Detalla restricciones, políticas y condiciones del paquete"
                    >{{ old(
                        'condiciones',
                        $destino?->condiciones
                    ) }}</textarea>
                </div>

                <div class="campo campo-completo">
                    <label class="opcion-destacada">
                        <input
                            type="checkbox"
                            name="destacado"
                            value="1"
                            @checked(
                                old(
                                    'destacado',
                                    $destino?->destacado
                                )
                            )
                        >

                        <span class="opcion-control"></span>

                        <span>
                            <strong>
                                Mostrar como paquete destacado
                            </strong>

                            <small>
                                Aparecerá en una sección principal
                                de la página pública.
                            </small>
                        </span>
                    </label>
                </div>
            </div>
        </section>

        <div class="acciones-finales">
            <a
                href="{{ route('destinos') }}"
                class="btn-cancelar"
            >
                Cancelar
            </a>

            <button
                type="submit"
                class="btn-guardar"
                data-texto-carga="Guardando paquete..."
            >
                <i class="bi bi-check2-circle"></i>

                <span class="texto-boton">
                    {{ $esEdicion
                        ? 'Guardar cambios'
                        : 'Registrar paquete' }}
                </span>
            </button>
        </div>
    </div>
</form>