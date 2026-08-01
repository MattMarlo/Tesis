@extends('layouts.main')

@section('titulo', $titulo)

@section('content')
<link
    rel="stylesheet"
    href="{{ asset('css/reserva-grupal.css') }}"
>

<main id="main" class="main pagina-reserva-grupal">
    <div class="grupal-encabezado">
        <div>
            <span class="grupal-modulo">Reservas</span>

            <h1>Editar reserva grupal</h1>

            <p>
                Corrige los datos de la reserva
                {{ $reserva->codigo_reserva }}. Las tarifas se calcularán nuevamente.
            </p>
        </div>

        <a
            href="{{ route('reservas') }}"
            class="volver-reservas"
        >
            <i class="bi bi-arrow-left"></i>
            Volver a reservas
        </a>
    </div>

    <form
        id="formularioReservaGrupal"
        class="formulario-grupal"
        action="{{ route(
            'reservas_grupal.update',
            $reserva->id
        ) }}"
        method="POST"
        novalidate
    >
        @csrf

        @method('PUT')

        <section class="grupal-seccion">
            <div class="seccion-titulo">
                <h2>1. Información del grupo</h2>

                <p>
                    Indica cómo se identificarán y administrarán los
                    pagos del grupo.
                </p>
            </div>

            <div class="grupal-grid">
                <div class="campo-grupal">
                    <label for="nombre_grupo">
                        Nombre del grupo <span>*</span>
                    </label>

                    <input
                        id="nombre_grupo"
                        name="nombre_grupo"
                        class="control-grupal"
                        type="text"
                        value="{{ old(
                            'nombre_grupo',
                            $grupo->nombre_grupo
                        ) }}"
                        placeholder="Ejemplo: Familia Pérez"
                        maxlength="150"
                        required
                    >

                    <small
                        id="nombre_grupoError"
                        class="mensaje-error"
                    ></small>
                </div>

                <div class="campo-grupal">
                    <label for="destino_id">
                        Paquete turístico <span>*</span>
                    </label>

                    <select
                        id="destino_id"
                        name="destino_id"
                        class="control-grupal"
                        required
                    >
                        <option value="">
                            Selecciona un paquete
                        </option>

                        @foreach ($destinos as $destino)
                            @php
                                $precioBase =
                                    (float) $destino->precio_promocional > 0
                                        ? (float) $destino->precio_promocional
                                        : (float) $destino->precio;
                            @endphp

                            <option
                                value="{{ $destino->id }}"
                                data-nombre="{{ $destino->nombre_paquete }}"
                                data-origen="{{ $destino->ciudad_salida }}"
                                data-destino="{{ $destino->ciudad_destino }}"
                                data-pais="{{ $destino->pais }}"
                                data-fecha-salida="{{ $destino->fecha_salida?->format('Y-m-d') }}"
                                data-fecha-regreso="{{ $destino->fecha_regreso?->format('Y-m-d') }}"
                                data-precio="{{ number_format($precioBase, 2, '.', '') }}"
                                data-moneda="{{ strtoupper($destino->moneda ?: 'USD') }}"
                                data-capacidad="{{ $destino->capacidad }}"
                                @selected(
                                    (int) old(
                                        'destino_id',
                                        $reserva->destino_id
                                    ) === $destino->id
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
                        class="mensaje-error"
                    ></small>
                </div>
            </div>

            <div class="tipo-grupo-contenedor">
                <span class="etiqueta-opciones">
                    Tipo de grupo <strong>*</strong>
                </span>

                <div class="opciones-tipo-grupo">
                    <label class="opcion-tipo">
                        <input
                            type="radio"
                            name="tipo_grupo"
                            value="familiar"
                            @checked(
                                old(
                                    'tipo_grupo',
                                    $grupo->tipo_grupo
                                ) === 'familiar'
                            )
                        >

                        <span>
                            <i class="bi bi-people"></i>

                            <strong>Grupo familiar</strong>

                            <small>
                                Una persona será responsable de pagar
                                el total del grupo.
                            </small>
                        </span>
                    </label>

                    <label class="opcion-tipo">
                        <input
                            type="radio"
                            name="tipo_grupo"
                            value="independiente"
                            @checked(
                                old(
                                    'tipo_grupo',
                                    $grupo->tipo_grupo
                                ) === 'independiente'
                            )
                        >

                        <span>
                            <i class="bi bi-person-lines-fill"></i>

                            <strong>Personas independientes</strong>

                            <small>
                                Cada integrante pagará su propio valor.
                            </small>
                        </span>
                    </label>
                </div>

                <small
                    id="tipo_grupoError"
                    class="mensaje-error"
                ></small>
            </div>

            <div
                id="resumenPaqueteGrupal"
                class="resumen-paquete-grupal oculto"
            >
                <div>
                    <span>Ruta</span>
                    <strong id="grupoRuta">—</strong>
                </div>

                <div>
                    <span>Salida</span>
                    <strong id="grupoSalida">—</strong>
                </div>

                <div>
                    <span>Regreso</span>
                    <strong id="grupoRegreso">—</strong>
                </div>

                <div>
                    <span>Precio base</span>
                    <strong id="grupoPrecio">—</strong>
                </div>

                <div>
                    <span>Capacidad total</span>
                    <strong id="grupoCapacidad">—</strong>
                </div>
            </div>
        </section>

        <section class="grupal-seccion">
            <div class="seccion-titulo seccion-con-accion">
                <div>
                    <h2>2. Integrantes</h2>

                    <p>
                        Agrega al menos dos clientes con información
                        completa.
                    </p>
                </div>

                <span id="contadorIntegrantes">
                    0 integrantes
                </span>
            </div>

            <div class="agregar-integrante">
                <div class="campo-grupal">
                    <label for="selectorCliente">
                        Cliente
                    </label>

                    <select
                        id="selectorCliente"
                        class="control-grupal"
                    >
                        <option value="">
                            Selecciona un cliente
                        </option>

                        @foreach ($clientes as $cliente)
                            @php
                                $clienteCompleto =
                                    $cliente->fecha_nacimiento &&
                                    $cliente->tipo_documento &&
                                    $cliente->nacionalidad;
                            @endphp

                            <option
                                value="{{ $cliente->id }}"
                                data-nombre="{{ $cliente->nombre_completo }}"
                                data-documento="{{ $cliente->documento }}"
                                data-fecha-nacimiento="{{ $cliente->fecha_nacimiento?->format('Y-m-d') }}"
                                data-completo="{{ $clienteCompleto ? '1' : '0' }}"
                                data-editar-url="{{ route('clientes.edit', $cliente->id) }}"
                            >
                                {{ $cliente->nombre_completo }}
                                — {{ $cliente->documento }}
                                @if (!$clienteCompleto)
                                    (información incompleta)
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <button
                    id="btnAgregarIntegrante"
                    type="button"
                    class="btn-agregar-integrante"
                >
                    <i class="bi bi-person-plus"></i>
                    Agregar
                </button>
            </div>

            <small
                id="integrantesError"
                class="mensaje-error"
            ></small>

            <div
                id="listaIntegrantes"
                class="lista-integrantes"
            >
                <div
                    id="sinIntegrantes"
                    class="sin-integrantes"
                >
                    <i class="bi bi-people"></i>

                    <strong>
                        Aún no agregas integrantes
                    </strong>

                    <span>
                        Selecciona un cliente y pulsa Agregar.
                    </span>
                </div>
            </div>
        </section>

        <section
            id="seccionResponsablePago"
            class="grupal-seccion oculto"
        >
            <div class="seccion-titulo">
                <h2>3. Responsable del pago</h2>

                <p>
                    Selecciona al integrante que realizará los pagos
                    del grupo familiar.
                </p>
            </div>

            <div class="campo-grupal campo-limitado">
                <label for="responsable_pago_id">
                    Responsable <span>*</span>
                </label>

                <select
                    id="responsable_pago_id"
                    name="responsable_pago_id"
                    class="control-grupal"
                >
                    <option value="">
                        Selecciona al responsable
                    </option>
                </select>

                <small
                    id="responsable_pago_idError"
                    class="mensaje-error"
                ></small>
            </div>
        </section>

        <section class="grupal-seccion">
            <div class="seccion-titulo">
                <h2>Resumen de la reserva</h2>

                <p>
                    Los valores se calculan automáticamente y serán
                    verificados nuevamente por el servidor.
                </p>
            </div>

            <div class="resumen-total-grupo">
                <div>
                    <span>Viajeros</span>
                    <strong id="resumenCantidad">0</strong>
                </div>

                <div>
                    <span>Precio sin descuentos</span>
                    <strong id="resumenPrecioBase">—</strong>
                </div>

                <div>
                    <span>Descuentos aplicados</span>
                    <strong id="resumenDescuento">—</strong>
                </div>

                <div class="total-grupo">
                    <span>Total del grupo</span>
                    <strong id="resumenTotal">—</strong>
                </div>
            </div>

            <div class="nota-grupal">
                <i class="bi bi-info-circle"></i>

                <span id="textoModalidadPago">
                    Selecciona el tipo de grupo para definir cómo se
                    registrarán los pagos.
                </span>
            </div>
        </section>

        <div class="acciones-grupal">
            <a
                href="{{ route('reservas') }}"
                class="btn-cancelar-grupal"
            >
                Cancelar
            </a>

            <button
                type="submit"
                class="btn-guardar-grupal"
            >
                <span>Guardar cambios</span>
                <i class="bi bi-check-lg"></i>
            </button>
        </div>
    </form>
</main>

<script>
    window.configuracionReservaGrupal = {
        modo: 'editar',
        errores: @json($errors->toArray()),
        mensajeError: @json(session('error')),
        integrantesAnteriores: @json(
            old(
                'integrantes',
                $integrantesActuales
            )
        ),
        responsableAnterior: @json(
            old(
                'responsable_pago_id',
                $grupo->responsable_pago_id
            )
        )
    };
</script>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="{{ asset('js/reserva-grupal.js') }}"></script>
@endsection