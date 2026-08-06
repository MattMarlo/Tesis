@extends('layouts.main')

@section('titulo', 'Nueva reserva individual')

@section('content')
<link
    rel="stylesheet"
    href="{{ asset('css/reserva-individual.css') }}"
>

<main id="main" class="main pagina-reserva-individual">
    <div class="reserva-encabezado">
        <div>
            <span class="reserva-modulo">
                Reservas
            </span>

            <h1>Nueva reserva individual</h1>

            <p>
                Selecciona el cliente y el paquete. El sistema calculará
                automáticamente la tarifa según la edad del viajero.
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
        id="formularioReservaIndividual"
        class="formulario-reserva"
        action="{{ route('reservas_individual.store') }}"
        method="POST"
        novalidate
    >
        @csrf

        @if ($preReservaId)
            <input
                type="hidden"
                name="prereserva_id"
                value="{{ $preReservaId }}"
            >
        @endif

        <section class="reserva-seccion">
            <div class="seccion-titulo">
                <h2>1. Selecciona el viajero</h2>

                <p>
                    Solo se muestran clientes con estado activo.
                </p>
            </div>

            <div class="campo-reserva">
                <label for="cliente_id">
                    Cliente <span>*</span>
                </label>

                <select
                    id="cliente_id"
                    name="cliente_id"
                    class="control-reserva"
                    required
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

                            $seleccionado = old(
                                'cliente_id',
                                $clienteSeleccionado
                            );
                        @endphp

                        <option
                            value="{{ $cliente->id }}"
                            data-nombre="{{ $cliente->nombre_completo }}"
                            data-documento="{{ $cliente->documento }}"
                            data-tipo-documento="{{ $cliente->tipo_documento }}"
                            data-fecha-nacimiento="{{ $cliente->fecha_nacimiento?->format('Y-m-d') }}"
                            data-nacionalidad="{{ $cliente->nacionalidad }}"
                            data-completo="{{ $clienteCompleto ? '1' : '0' }}"
                            data-editar-url="{{ route('clientes.edit', $cliente->id) }}"
                            @selected((int) $seleccionado === $cliente->id)
                        >
                            {{ $cliente->nombre_completo }}
                            — {{ $cliente->documento }}
                            @if (!$clienteCompleto)
                                (información incompleta)
                            @endif
                        </option>
                    @endforeach
                </select>

                <small
                    id="cliente_idError"
                    class="mensaje-error"
                ></small>

                <div class="acciones-cliente-reserva">
                    <a
                        href="{{ route('clientes.create') }}"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <i class="bi bi-person-plus"></i>
                        Registrar otro cliente
                    </a>

                    <span>
                        Si registras uno nuevo, actualiza esta página
                        para seleccionarlo.
                    </span>
                </div>
            </div>

            <div
                id="resumenCliente"
                class="resumen-seleccion oculto"
            >
                <div class="resumen-icono">
                    <i class="bi bi-person-check"></i>
                </div>

                <div class="resumen-informacion">
                    <span>Viajero seleccionado</span>
                    <strong id="resumenClienteNombre">—</strong>

                    <small id="resumenClienteDocumento">
                        —
                    </small>
                </div>

                <div
                    id="avisoClienteIncompleto"
                    class="aviso-incompleto oculto"
                >
                    <i class="bi bi-exclamation-triangle"></i>

                    <div>
                        <strong>
                            Información incompleta
                        </strong>

                        <span>
                            Completa la fecha de nacimiento, nacionalidad
                            y tipo de documento antes de reservar.
                        </span>

                        <a
                            id="editarClienteSeleccionado"
                            href="#"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            Completar información
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <section class="reserva-seccion">
            <div class="seccion-titulo">
                <h2>2. Selecciona el paquete</h2>

                <p>
                    Se muestran únicamente paquetes publicados con
                    fecha de salida vigente.
                </p>
            </div>

            <div class="campo-reserva">
                <label for="destino_id">
                    Paquete turístico <span>*</span>
                </label>

                <select
                    id="destino_id"
                    name="destino_id"
                    class="control-reserva"
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

                            $seleccionado = old(
                                'destino_id',
                                $destinoSeleccionado
                            );
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
                            @selected((int) $seleccionado === $destino->id)
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

            <div
                id="resumenPaquete"
                class="resumen-paquete oculto"
            >
                <div class="dato-paquete">
                    <span>Ruta</span>
                    <strong id="paqueteRuta">—</strong>
                </div>

                <div class="dato-paquete">
                    <span>Fecha de salida</span>
                    <strong id="paqueteSalida">—</strong>
                </div>

                <div class="dato-paquete">
                    <span>Fecha de regreso</span>
                    <strong id="paqueteRegreso">—</strong>
                </div>

                <div class="dato-paquete">
                    <span>Precio por persona</span>
                    <strong id="paquetePrecio">—</strong>
                </div>

                <div class="dato-paquete">
                    <span>Capacidad total</span>
                    <strong id="paqueteCapacidad">—</strong>
                </div>
            </div>
        </section>

        <section
            id="seccionCalculo"
            class="reserva-seccion oculto"
        >
            <div class="seccion-titulo">
                <h2>3. Tarifa calculada</h2>

                <p>
                    El cálculo utiliza la edad que tendrá el cliente
                    en la fecha de salida.
                </p>
            </div>

            <div class="calculo-tarifa">
                <div>
                    <span>Edad en el viaje</span>
                    <strong id="tarifaEdad">—</strong>
                </div>

                <div>
                    <span>Categoría</span>
                    <strong id="tarifaCategoria">—</strong>
                </div>

                <div>
                    <span>Porcentaje aplicado</span>
                    <strong id="tarifaPorcentaje">—</strong>
                </div>

                <div class="tarifa-total">
                    <span>Total de la reserva</span>
                    <strong id="tarifaTotal">—</strong>
                </div>
            </div>

            <div class="nota-pago">
                <i class="bi bi-info-circle"></i>

                <span>
                    La reserva se crea de forma provisional y te llevará
                    directamente al cobro del anticipo obligatorio.
                </span>
            </div>
        </section>

        @include(
            'modules.reservas.partials.politica-pagos',
            [
                'claseSeccion' => 'reserva-seccion',
                'claseCampo' => 'campo-reserva',
            ]
        )

        <div class="acciones-reserva">
            <a
                href="{{ route('reservas') }}"
                class="btn-cancelar-reserva"
            >
                Cancelar
            </a>

            <button
                type="submit"
                class="btn-guardar-reserva"
            >
                <span>Registrar reserva</span>
                <i class="bi bi-check-lg"></i>
            </button>
        </div>
    </form>
</main>

<script>
    window.configuracionReservaIndividual = {
        errores: @json($errors->toArray()),
        mensajeError: @json(session('error'))
    };
</script>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="{{ asset('js/reserva-individual.js') }}"></script>
@endsection