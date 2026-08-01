@extends('layouts.main')

@section('titulo', $titulo)

@section('content')

<link
    rel="stylesheet"
    href="{{ asset('css/prerreservas-formulario.css') }}"
>

<main
    id="main"
    class="main pagina-prerreserva-formulario"
>
    <header class="prerreserva-formulario-encabezado">
        <div>
            <span>Solicitud desde Telegram</span>

            <h1>Revisar prerreserva</h1>

            <p>
                Completa únicamente la información confirmada con
                el cliente antes de crear la reserva.
            </p>
        </div>

        <a
            href="{{ route('prereservas.index') }}"
            class="volver-prerreservas"
        >
            <i class="bi bi-arrow-left"></i>
            Volver al listado
        </a>
    </header>

    <section class="origen-prerreserva">
        <div>
            <span>Origen</span>

            <strong>
                <i class="bi bi-telegram"></i>
                Telegram
            </strong>

            <small>
                Registrada mediante n8n
            </small>
        </div>

        <div>
            <span>Fecha de recepción</span>

            <strong>
                {{
                    $preReserva->created_at
                        ?->format('d/m/Y H:i')
                }}
            </strong>

            <small>
                Identificador:
                {{ $preReserva->id }}
            </small>
        </div>

        <div>
            <span>Referencia externa</span>

            <strong>
                {{
                    $preReserva->referencia_externa
                    ?: 'No enviada'
                }}
            </strong>

            <small>
                Chat:
                {{
                    $preReserva->telegram_chat_id
                    ?: 'No enviado'
                }}
            </small>
        </div>
    </section>

    <form
        id="formularioPrerreserva"
        method="POST"
        action="{{ route(
            'prereservas.update',
            $preReserva->id
        ) }}"
        class="formulario-prerreserva"
        novalidate
    >
        @csrf
        @method('PATCH')

        <div class="formulario-prerreserva-grid">
            <div class="campo-prerreserva">
                <label for="cliente_nombre">
                    Nombre completo <span>*</span>
                </label>

                <input
                    id="cliente_nombre"
                    name="cliente_nombre"
                    type="text"
                    value="{{ old(
                        'cliente_nombre',
                        $preReserva->cliente_nombre
                    ) }}"
                    maxlength="150"
                    required
                >

                <small
                    id="cliente_nombreError"
                    class="mensaje-error-prerreserva"
                ></small>
            </div>

            <div class="campo-prerreserva">
                <label for="email">
                    Correo electrónico <span>*</span>
                </label>

                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old(
                        'email',
                        $preReserva->email
                    ) }}"
                    maxlength="150"
                    required
                >

                <small
                    id="emailError"
                    class="mensaje-error-prerreserva"
                ></small>
            </div>

            <div class="campo-prerreserva">
                <label for="telefono">
                    Teléfono <span>*</span>
                </label>

                <input
                    id="telefono"
                    name="telefono"
                    type="text"
                    value="{{ old(
                        'telefono',
                        $preReserva->telefono
                    ) }}"
                    maxlength="20"
                    required
                >

                <small
                    id="telefonoError"
                    class="mensaje-error-prerreserva"
                ></small>
            </div>

            <div class="campo-prerreserva">
                <label for="cedula">
                    Cédula
                </label>

                <input
                    id="cedula"
                    name="cedula"
                    type="text"
                    inputmode="numeric"
                    value="{{ old(
                        'cedula',
                        $preReserva->cedula
                    ) }}"
                    maxlength="10"
                    placeholder="Opcional en esta etapa"
                >

                <small
                    id="cedulaError"
                    class="mensaje-error-prerreserva"
                ></small>
            </div>

            <div class="campo-prerreserva">
                <label for="destino_id">
                    Paquete turístico <span>*</span>
                </label>

                <select
                    id="destino_id"
                    name="destino_id"
                    required
                >
                    <option value="">
                        Selecciona un paquete
                    </option>

                    @foreach ($destinos as $destino)
                        <option
                            value="{{ $destino->id }}"
                            @selected(
                                (int) old(
                                    'destino_id',
                                    $preReserva->destino_id
                                ) ===
                                $destino->id
                            )
                        >
                            {{ $destino->nombre_paquete }}
                            — {{ $destino->ciudad_destino }},
                            {{ $destino->pais }}
                        </option>
                    @endforeach
                </select>

                <small
                    id="destino_idError"
                    class="mensaje-error-prerreserva"
                ></small>
            </div>

            <div class="campo-prerreserva">
                <label for="fecha_viaje">
                    Fecha tentativa <span>*</span>
                </label>

                <input
                    id="fecha_viaje"
                    name="fecha_viaje"
                    type="date"
                    value="{{ old(
                        'fecha_viaje',
                        $preReserva->fecha_viaje
                            ?->format('Y-m-d')
                    ) }}"
                    min="{{ today()->format('Y-m-d') }}"
                    required
                >

                <small
                    id="fecha_viajeError"
                    class="mensaje-error-prerreserva"
                ></small>
            </div>

            <div class="campo-prerreserva">
                <label for="cantidad_personas">
                    Cantidad de personas <span>*</span>
                </label>

                <input
                    id="cantidad_personas"
                    name="cantidad_personas"
                    type="number"
                    value="{{ old(
                        'cantidad_personas',
                        $preReserva->cantidad_personas
                    ) }}"
                    min="1"
                    max="100"
                    required
                >

                <small
                    id="cantidad_personasError"
                    class="mensaje-error-prerreserva"
                ></small>
            </div>

            <div class="campo-prerreserva">
                <label for="estado">
                    Estado de seguimiento <span>*</span>
                </label>

                <select
                    id="estado"
                    name="estado"
                    required
                >
                    <option
                        value="pendiente_contacto"
                        @selected(
                            old(
                                'estado',
                                $preReserva->estado
                            ) ===
                            'pendiente_contacto'
                        )
                    >
                        Pendiente de contacto
                    </option>

                    <option
                        value="contactado"
                        @selected(
                            old(
                                'estado',
                                $preReserva->estado
                            ) ===
                            'contactado'
                        )
                    >
                        Cliente contactado
                    </option>

                    <option
                        value="perdida"
                        @selected(
                            old(
                                'estado',
                                $preReserva->estado
                            ) ===
                            'perdida'
                        )
                    >
                        Descartada
                    </option>
                </select>

                <small
                    id="estadoError"
                    class="mensaje-error-prerreserva"
                ></small>
            </div>

            <div class="campo-prerreserva campo-prerreserva-completo">
                <label for="observaciones">
                    Observaciones de seguimiento
                </label>

                <textarea
                    id="observaciones"
                    name="observaciones"
                    maxlength="2000"
                    placeholder="Ejemplo: cliente contactado, solicita confirmar disponibilidad..."
                >{{ old(
                    'observaciones',
                    $preReserva->observaciones
                ) }}</textarea>

                <small
                    id="observacionesError"
                    class="mensaje-error-prerreserva"
                ></small>
            </div>
        </div>

        <div class="acciones-formulario-prerreserva">
            <a
                href="{{ route('prereservas.index') }}"
                class="btn-cancelar-prerreserva"
            >
                Cancelar
            </a>

            <button
                type="submit"
                class="btn-guardar-prerreserva"
            >
                <span>Guardar cambios</span>
                <i class="bi bi-check-lg"></i>
            </button>
        </div>
    </form>
</main>

<script>
    window.configuracionFormularioPrerreserva = {
        errores: @json($errors->toArray()),
        mensajeError: @json(session('error'))
    };
</script>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script
    src="{{ asset('js/prerreservas-formulario.js') }}"
></script>

@endsection