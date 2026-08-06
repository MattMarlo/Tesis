@extends('layouts.main')

@section('titulo', 'Solicitar cancelación')

@section('content')
<link
    rel="stylesheet"
    href="{{ asset(
        'css/solicitudes-cancelacion.css'
    ) }}?v={{ filemtime(
        public_path(
            'css/solicitudes-cancelacion.css'
        )
    ) }}"
>

@php
    $moneda = strtoupper(
        $reserva->moneda ?: 'USD'
    );

    $total = (float)
        $reserva->precio_total_viaje;

    $pagado = (float)
        $reserva->total_pagado;

    $saldo = max(
        0,
        $total - $pagado
    );

    $nombreTitular = $reserva->esGrupal()
        ? (
            $reserva->grupo?->nombre_grupo
            ?: 'Grupo no disponible'
        )
        : (
            $reserva->cliente?->nombre_completo
            ?: 'Cliente no disponible'
        );

    $nombrePaquete =
        $reserva->destino?->nombre_paquete
        ?: 'Paquete no disponible';

    $rutaPaquete = collect([
        $reserva->destino?->ciudad_origen,
        $reserva->destino?->ciudad_destino,
        $reserva->destino?->pais,
    ])
        ->filter()
        ->implode(' · ');
@endphp

<main
    id="main"
    class="main pagina-solicitud-cancelacion"
>
    <div class="cancelacion-contenedor">
        <header class="cancelacion-encabezado">
            <div class="cancelacion-encabezado-contenido">
                <span class="cancelacion-modulo">
                    Cancelaciones y reembolsos
                </span>

                <h1>Solicitar cancelación</h1>

                <p>
                    Crea una solicitud para que los pagos,
                    documentos y posibles gastos sean
                    revisados antes de cancelar la reserva.
                </p>
            </div>

            <div class="cancelacion-encabezado-acciones">
                <a
                    href="{{ route('reservas') }}"
                    class="btn-cancelacion secundario"
                >
                    <i class="bi bi-arrow-left"></i>
                    Volver a reservas
                </a>

                <a
                    href="{{ route(
                        'cancelaciones.solicitudes.index'
                    ) }}"
                    class="
                        btn-cancelacion
                        outline-primary
                    "
                >
                    <i class="bi bi-clipboard-check"></i>
                    Ver solicitudes
                </a>
            </div>
        </header>

        @if ($errors->any())
            <div class="cancelacion-alerta error">
                <i class="bi bi-exclamation-circle"></i>

                <div>
                    <strong>
                        Revisa los datos del formulario
                    </strong>

                    <ul class="mb-0 mt-2">
                        @foreach (
                            $errors->all() as $error
                        )
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="cancelacion-alerta error">
                <i class="bi bi-exclamation-circle"></i>

                <p>{{ session('error') }}</p>
            </div>
        @endif

        <div class="expediente-progreso">
            <div class="expediente-paso activo">
                <span>1</span>
                <strong>Crear solicitud</strong>
            </div>

            <div class="expediente-paso">
                <span>2</span>
                <strong>Revisar documentos</strong>
            </div>

            <div class="expediente-paso">
                <span>3</span>
                <strong>Liquidar gastos</strong>
            </div>

            <div class="expediente-paso">
                <span>4</span>
                <strong>Decisión final</strong>
            </div>
        </div>

        <div class="cancelacion-alerta informativa">
            <i class="bi bi-shield-check"></i>

            <div>
                <strong>
                    La reserva todavía no será cancelada
                </strong>

                <p>
                    Al enviar este formulario se creará una
                    solicitud pendiente. La cancelación
                    solamente se realizará cuando un
                    administrador revise y apruebe el
                    expediente.
                </p>
            </div>
        </div>

        <section class="cancelacion-card">
            <div class="cancelacion-card-header">
                <div>
                    <h2>Resumen de la reserva</h2>

                    <p>
                        Comprueba que estás solicitando la
                        cancelación de la reserva correcta.
                    </p>
                </div>

                <span
                    class="
                        estado-solicitud
                        pendiente
                    "
                >
                    <i class="bi bi-hourglass-split"></i>
                    Activa
                </span>
            </div>

            <div class="cancelacion-card-body">
                <div class="resumen-reserva-grid">
                    <div
                        class="
                            resumen-reserva-item
                            destacado
                        "
                    >
                        <span>Código</span>

                        <strong>
                            {{ $reserva->codigo_reserva }}
                        </strong>

                        <small>
                            {{ $reserva->esGrupal()
                                ? 'Reserva grupal'
                                : 'Reserva individual' }}
                        </small>
                    </div>

                    <div class="resumen-reserva-item">
                        <span>Cliente o grupo</span>

                        <strong>
                            {{ $nombreTitular }}
                        </strong>

                        @if ($reserva->cliente?->documento)
                            <small>
                                Documento:
                                {{
                                    $reserva->cliente
                                        ->documento
                                }}
                            </small>
                        @endif
                    </div>

                    <div class="resumen-reserva-item">
                        <span>Paquete</span>

                        <strong>
                            {{ $nombrePaquete }}
                        </strong>

                        <small>
                            {{ $rutaPaquete
                                ?: 'Ruta no especificada' }}
                        </small>
                    </div>

                    <div class="resumen-reserva-item">
                        <span>Fecha del viaje</span>

                        <strong>
                            {{
                                $reserva->fecha_viaje
                                    ?->format('d/m/Y')
                                ?: 'Sin fecha'
                            }}
                        </strong>

                        @if (
                            $reserva->fecha_vencimiento_saldo
                        )
                            <small>
                                Pago final:
                                {{
                                    $reserva
                                        ->fecha_vencimiento_saldo
                                        ->format('d/m/Y')
                                }}
                            </small>
                        @endif
                    </div>
                </div>

                <hr class="cancelacion-separador">

                <div class="resumen-financiero">
                    <div class="resumen-financiero-item">
                        <span>Precio total</span>

                        <strong>
                            {{ $moneda }}
                            {{ number_format($total, 2) }}
                        </strong>
                    </div>

                    <div class="resumen-financiero-item">
                        <span>Total pagado</span>

                        <strong>
                            {{ $moneda }}
                            {{ number_format($pagado, 2) }}
                        </strong>
                    </div>

                    <div
                        class="
                            resumen-financiero-item
                            gastos
                        "
                    >
                        <span>Saldo pendiente</span>

                        <strong>
                            {{ $moneda }}
                            {{ number_format($saldo, 2) }}
                        </strong>
                    </div>
                </div>
            </div>
        </section>

        <form
            id="formularioSolicitudCancelacion"
            action="{{ route(
                'cancelaciones.solicitudes.store',
                ['reserva' => $reserva->id]
            ) }}"
            method="POST"
            enctype="multipart/form-data"
            novalidate
        >
            @csrf

            <section class="cancelacion-card">
                <div class="cancelacion-card-header">
                    <div>
                        <span class="cancelacion-modulo">
                            Paso 1 de 2
                        </span>

                        <h2>
                            Información de la solicitud
                        </h2>

                        <p>
                            Registra el origen, motivo y canal
                            por el que se solicitó la
                            cancelación.
                        </p>
                    </div>

                    <span
                        class="
                            estado-solicitud
                            pendiente
                        "
                    >
                        <i class="bi bi-clock"></i>
                        Pendiente de revisión
                    </span>
                </div>

                <div class="cancelacion-card-body">
                    <div class="cancelacion-form-grid">
                        <div class="cancelacion-campo">
                            <label for="solicitante">
                                <span>
                                    ¿Quién solicita la
                                    cancelación? *
                                </span>
                            </label>

                            <select
                                id="solicitante"
                                name="solicitante"
                                @class([
                                    'is-invalid' =>
                                        $errors->has(
                                            'solicitante'
                                        ),
                                ])
                                required
                            >
                                <option value="">
                                    Selecciona una opción
                                </option>

                                <option
                                    value="cliente"
                                    @selected(
                                        old('solicitante') ===
                                        'cliente'
                                    )
                                >
                                    Cliente
                                </option>

                                <option
                                    value="agencia"
                                    @selected(
                                        old('solicitante') ===
                                        'agencia'
                                    )
                                >
                                    Agencia
                                </option>

                                <option
                                    value="proveedor"
                                    @selected(
                                        old('solicitante') ===
                                        'proveedor'
                                    )
                                >
                                    Proveedor
                                </option>

                                <option
                                    value="sistema"
                                    @selected(
                                        old('solicitante') ===
                                        'sistema'
                                    )
                                >
                                    Sistema
                                </option>
                            </select>

                            <small class="cancelacion-ayuda">
                                Identifica quién originó la
                                solicitud.
                            </small>

                            @error('solicitante')
                                <span
                                    class="cancelacion-error"
                                >
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>

                        <div class="cancelacion-campo">
                            <label for="tipo_cancelacion">
                                <span>
                                    Tipo de cancelación *
                                </span>
                            </label>

                            <select
                                id="tipo_cancelacion"
                                name="tipo_cancelacion"
                                @class([
                                    'is-invalid' =>
                                        $errors->has(
                                            'tipo_cancelacion'
                                        ),
                                ])
                                required
                            >
                                <option value="">
                                    Selecciona una opción
                                </option>

                                <option
                                    value="decision_cliente"
                                    @selected(
                                        old(
                                            'tipo_cancelacion'
                                        ) ===
                                        'decision_cliente'
                                    )
                                >
                                    Decisión del cliente
                                </option>

                                <option
                                    value="fuerza_mayor"
                                    @selected(
                                        old(
                                            'tipo_cancelacion'
                                        ) ===
                                        'fuerza_mayor'
                                    )
                                >
                                    Fuerza mayor
                                </option>

                                <option
                                    value="responsabilidad_agencia"
                                    @selected(
                                        old(
                                            'tipo_cancelacion'
                                        ) ===
                                        'responsabilidad_agencia'
                                    )
                                >
                                    Responsabilidad de la
                                    agencia
                                </option>

                                <option
                                    value="problema_proveedor"
                                    @selected(
                                        old(
                                            'tipo_cancelacion'
                                        ) ===
                                        'problema_proveedor'
                                    )
                                >
                                    Problema con proveedor
                                </option>

                                <option
                                    value="cambio_viaje"
                                    @selected(
                                        old(
                                            'tipo_cancelacion'
                                        ) ===
                                        'cambio_viaje'
                                    )
                                >
                                    Cambio o reprogramación
                                </option>

                                <option
                                    value="otro"
                                    @selected(
                                        old(
                                            'tipo_cancelacion'
                                        ) ===
                                        'otro'
                                    )
                                >
                                    Otro motivo
                                </option>
                            </select>

                            <small class="cancelacion-ayuda">
                                Ayuda a determinar la
                                responsabilidad de la
                                cancelación.
                            </small>

                            @error('tipo_cancelacion')
                                <span
                                    class="cancelacion-error"
                                >
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>

                        <div
                            class="
                                cancelacion-campo
                                ancho-completo
                            "
                        >
                            <label for="motivo">
                                <span>
                                    Motivo detallado *
                                </span>

                                <span
                                    id="contadorMotivo"
                                    class="opcional"
                                >
                                    0 / 1000
                                </span>
                            </label>

                            <textarea
                                id="motivo"
                                name="motivo"
                                maxlength="1000"
                                minlength="10"
                                placeholder="Describe claramente lo ocurrido y por qué se solicita la cancelación."
                                @class([
                                    'is-invalid' =>
                                        $errors->has('motivo'),
                                ])
                                required
                            >{{ old('motivo') }}</textarea>

                            <small class="cancelacion-ayuda">
                                Mínimo 10 y máximo 1000
                                caracteres.
                            </small>

                            @error('motivo')
                                <span
                                    class="cancelacion-error"
                                >
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>

                        {{--
                            El controlador espera el nombre
                            canal_solicitud, no canal.
                        --}}
                        <div class="cancelacion-campo">
                            <label for="canal_solicitud">
                                <span>
                                    Canal de la solicitud *
                                </span>
                            </label>

                            <select
                                id="canal_solicitud"
                                name="canal_solicitud"
                                @class([
                                    'is-invalid' =>
                                        $errors->has(
                                            'canal_solicitud'
                                        ),
                                ])
                                required
                            >
                                <option value="">
                                    Selecciona una opción
                                </option>

                                <option
                                    value="presencial"
                                    @selected(
                                        old(
                                            'canal_solicitud'
                                        ) ===
                                        'presencial'
                                    )
                                >
                                    Presencial
                                </option>

                                <option
                                    value="llamada"
                                    @selected(
                                        old(
                                            'canal_solicitud'
                                        ) ===
                                        'llamada'
                                    )
                                >
                                    Llamada telefónica
                                </option>

                                <option
                                    value="whatsapp"
                                    @selected(
                                        old(
                                            'canal_solicitud'
                                        ) ===
                                        'whatsapp'
                                    )
                                >
                                    WhatsApp
                                </option>

                                <option
                                    value="correo"
                                    @selected(
                                        old(
                                            'canal_solicitud'
                                        ) ===
                                        'correo'
                                    )
                                >
                                    Correo electrónico
                                </option>

                                <option
                                    value="otro"
                                    @selected(
                                        old(
                                            'canal_solicitud'
                                        ) ===
                                        'otro'
                                    )
                                >
                                    Otro
                                </option>
                            </select>

                            <small class="cancelacion-ayuda">
                                Indica cómo se recibió la
                                solicitud.
                            </small>

                            @error('canal_solicitud')
                                <span
                                    class="cancelacion-error"
                                >
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>

                        <div class="cancelacion-campo">
                            <label
                                for="referencia_solicitud"
                            >
                                <span>
                                    Referencia de comunicación
                                </span>

                                <span class="opcional">
                                    Opcional
                                </span>
                            </label>

                            <input
                                id="referencia_solicitud"
                                type="text"
                                name="referencia_solicitud"
                                value="{{ old(
                                    'referencia_solicitud'
                                ) }}"
                                maxlength="255"
                                placeholder="Ej.: WhatsApp del 06/08/2026"
                                @class([
                                    'is-invalid' =>
                                        $errors->has(
                                            'referencia_solicitud'
                                        ),
                                ])
                            >

                            <small class="cancelacion-ayuda">
                                Puede ser un correo, número de
                                caso o referencia del contacto.
                            </small>

                            @error('referencia_solicitud')
                                <span
                                    class="cancelacion-error"
                                >
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>
                    </div>
                </div>
            </section>

            <section class="cancelacion-card">
                <div class="cancelacion-card-header">
                    <div>
                        <span class="cancelacion-modulo">
                            Paso 2 de 2
                        </span>

                        <h2>
                            Evidencia y observaciones
                        </h2>

                        <p>
                            Adjunta el documento que respalde
                            el motivo. Los comprobantes de
                            gastos se registrarán después
                            dentro del expediente.
                        </p>
                    </div>

                    <i
                        class="
                            bi
                            bi-file-earmark-lock
                            fs-3
                            text-primary
                        "
                    ></i>
                </div>

                <div class="cancelacion-card-body">
                    <div class="cancelacion-form-grid">
                        <div
                            class="
                                cancelacion-campo
                                ancho-completo
                            "
                        >
                            <label for="evidencia">
                                <span>
                                    Evidencia del motivo *
                                </span>
                            </label>

                            <div class="cancelacion-archivo">
                                <input
                                    id="evidencia"
                                    type="file"
                                    name="evidencia"
                                    accept=".pdf,.jpg,.jpeg,.png,.webp"
                                    @class([
                                        'is-invalid' =>
                                            $errors->has(
                                                'evidencia'
                                            ),
                                    ])
                                    required
                                >

                                <small
                                    class="cancelacion-ayuda"
                                >
                                    PDF, JPG, JPEG, PNG o
                                    WEBP. Tamaño máximo:
                                    10 MB.
                                </small>
                            </div>

                            @error('evidencia')
                                <span
                                    class="cancelacion-error"
                                >
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>

                        <div
                            class="
                                cancelacion-campo
                                ancho-completo
                            "
                        >
                            <label
                                for="observaciones_internas"
                            >
                                <span>
                                    Observaciones internas
                                </span>

                                <span class="opcional">
                                    Opcional
                                </span>
                            </label>

                            <textarea
                                id="observaciones_internas"
                                name="observaciones_internas"
                                maxlength="1000"
                                placeholder="Información adicional para el administrador que revisará la solicitud."
                                @class([
                                    'is-invalid' =>
                                        $errors->has(
                                            'observaciones_internas'
                                        ),
                                ])
                            >{{ old(
                                'observaciones_internas'
                            ) }}</textarea>

                            <small class="cancelacion-ayuda">
                                Esta información es de uso
                                interno y no sustituye la
                                evidencia.
                            </small>

                            @error('observaciones_internas')
                                <span
                                    class="cancelacion-error"
                                >
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>
                    </div>

                    <div
                        class="
                            cancelacion-alerta
                            advertencia
                        "
                    >
                        <i
                            class="
                                bi
                                bi-exclamation-triangle
                            "
                        ></i>

                        <div>
                            <strong>
                                Los gastos se documentan
                                después
                            </strong>

                            <p>
                                No escribas aquí valores de
                                aerolínea, hotel u otros
                                proveedores. Los gastos no
                                recuperables deben registrarse
                                con sus comprobantes dentro del
                                expediente.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="cancelacion-card-footer">
                    <a
                        href="{{ route('reservas') }}"
                        class="btn-cancelacion secundario"
                    >
                        <i class="bi bi-arrow-left"></i>
                        Cancelar y volver
                    </a>

                    <button
                        id="botonEnviarSolicitud"
                        type="submit"
                        class="btn-cancelacion primario"
                    >
                        <i class="bi bi-send"></i>
                        Enviar para revisión
                    </button>
                </div>
            </section>
        </form>
    </div>
</main>

<script>
    document.addEventListener(
        'DOMContentLoaded',
        function () {
            const formulario =
                document.getElementById(
                    'formularioSolicitudCancelacion'
                );

            const motivo =
                document.getElementById(
                    'motivo'
                );

            const contador =
                document.getElementById(
                    'contadorMotivo'
                );

            const evidencia =
                document.getElementById(
                    'evidencia'
                );

            const boton =
                document.getElementById(
                    'botonEnviarSolicitud'
                );

            function actualizarContador() {
                if (!motivo || !contador) {
                    return;
                }

                contador.textContent =
                    motivo.value.length +
                    ' / 1000';
            }

            actualizarContador();

            motivo?.addEventListener(
                'input',
                actualizarContador
            );

            formulario?.addEventListener(
                'submit',
                function (evento) {
                    /*
                     * Primero dejamos que el navegador
                     * valide los campos requeridos.
                     */
                    if (
                        !formulario.checkValidity()
                    ) {
                        evento.preventDefault();

                        formulario.reportValidity();

                        return;
                    }

                    /*
                     * Validación visual adicional del
                     * archivo antes de enviar.
                     */
                    if (
                        !evidencia ||
                        !evidencia.files.length
                    ) {
                        evento.preventDefault();

                        evidencia?.focus();

                        return;
                    }

                    if (!boton) {
                        return;
                    }

                    boton.disabled = true;

                    boton.innerHTML =
                        '<span class="' +
                        'spinner-border ' +
                        'spinner-border-sm" ' +
                        'aria-hidden="true">' +
                        '</span>' +
                        ' Enviando solicitud...';
                }
            );
        }
    );
</script>
@endsection