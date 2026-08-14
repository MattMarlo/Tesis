<div class="contenedor contenedor-prerreserva-publica">
    <section
        class="prerreserva-publica"
        id="formularioPrerreserva"
        aria-labelledby="tituloPrerreserva"
    >
        <div class="prerreserva-presentacion">
            <span class="prerreserva-etiqueta">
                Prerreserva en línea
            </span>

            <h2 id="tituloPrerreserva">
                Da el primer paso para tu viaje
            </h2>

            <p>
                Déjanos los datos del titular y nuestro equipo confirmará
                contigo la disponibilidad y los siguientes pasos. No se
                realiza ningún cobro al enviar este formulario.
            </p>

            <div class="prerreserva-paquete-resumen">
                <span class="prerreserva-resumen-icono">
                    <i class="fa fa-suitcase"></i>
                </span>

                <div>
                    <small>Paquete seleccionado</small>
                    <strong>{{ $destino->nombre_paquete }}</strong>
                    <span>
                        <i class="fa fa-calendar"></i>
                        {{ $destino->fecha_salida
                            ? $destino->fecha_salida->format('d/m/Y')
                            : 'Fecha por confirmar' }}
                        · {{ $cuposDisponibles }}
                        {{ $cuposDisponibles === 1 ? 'cupo' : 'cupos' }}
                    </span>
                </div>
            </div>

            <ul class="prerreserva-beneficios">
                <li>
                    <i class="fa fa-check"></i>
                    Solo solicitamos los datos necesarios del titular.
                </li>
                <li>
                    <i class="fa fa-check"></i>
                    Los datos de los demás viajeros se completarán después.
                </li>
                <li>
                    <i class="fa fa-check"></i>
                    La solicitud queda registrada para seguimiento.
                </li>
            </ul>
        </div>

        <div class="prerreserva-formulario-contenedor">
            <form
                action="{{ route(
                    'paquetes.prerreserva.store',
                    ['destino' => $destino->slug]
                ) }}"
                method="POST"
                id="prerreservaPublicaForm"
                data-cupos-disponibles="{{ $cuposDisponibles }}"
                novalidate
            >
                @csrf

                <fieldset class="campo-prerreserva campo-tipo-reserva">
                    <legend>¿Cómo vas a viajar?</legend>

                    <div class="opciones-tipo-reserva">
                        <label class="opcion-tipo-reserva seleccionada">
                            <input
                                type="radio"
                                name="tipo_reserva"
                                value="individual"
                                checked
                            >

                            <span class="opcion-tipo-icono">
                                <i class="fa fa-user"></i>
                            </span>

                            <span>
                                <strong>Individual</strong>
                                <small>Viajo solo</small>
                            </span>
                        </label>

                        <label
                            class="opcion-tipo-reserva{{ $cuposDisponibles < 2 ? ' opcion-deshabilitada' : '' }}"
                        >
                            <input
                                type="radio"
                                name="tipo_reserva"
                                value="grupal"
                                @disabled($cuposDisponibles < 2)
                            >

                            <span class="opcion-tipo-icono">
                                <i class="fa fa-users"></i>
                            </span>

                            <span>
                                <strong>Grupal</strong>
                                <small>
                                    {{ $cuposDisponibles < 2
                                        ? 'Sin cupos suficientes'
                                        : 'Viajamos 2 o más' }}
                                </small>
                            </span>
                        </label>
                    </div>

                    <span
                        class="mensaje-error-campo"
                        data-error-for="tipo_reserva"
                    ></span>
                </fieldset>

                <div
                    class="campo-prerreserva campo-cantidad-grupo"
                    id="campoCantidadPersonas"
                    hidden
                >
                    <label for="cantidadPersonas">
                        Cantidad de viajeros
                    </label>

                    <div class="control-con-icono">
                        <i class="fa fa-users"></i>
                        <input
                            type="number"
                            id="cantidadPersonas"
                            name="cantidad_personas"
                            value="1"
                            min="1"
                            max="{{ min(100, $cuposDisponibles) }}"
                            inputmode="numeric"
                        >
                    </div>

                    <small class="ayuda-campo">
                        Máximo {{ min(100, $cuposDisponibles) }} viajeros
                        según la disponibilidad actual.
                    </small>

                    <span
                        class="mensaje-error-campo"
                        data-error-for="cantidad_personas"
                    ></span>
                </div>

                <div class="rejilla-campos-prerreserva">
                    <div class="campo-prerreserva campo-ancho-completo">
                        <label for="nombreCompleto">
                            Nombre completo del titular
                        </label>

                        <div class="control-con-icono">
                            <i class="fa fa-user"></i>
                            <input
                                type="text"
                                id="nombreCompleto"
                                name="nombre_completo"
                                maxlength="150"
                                autocomplete="name"
                                placeholder="Nombres y apellidos"
                                required
                            >
                        </div>

                        <span
                            class="mensaje-error-campo"
                            data-error-for="nombre_completo"
                        ></span>
                    </div>

                    <div class="campo-prerreserva">
                        <label for="cedulaPrerreserva">
                            Cédula
                        </label>

                        <div class="control-con-icono">
                            <i class="fa fa-id-card"></i>
                            <input
                                type="text"
                                id="cedulaPrerreserva"
                                name="cedula"
                                maxlength="10"
                                inputmode="numeric"
                                autocomplete="off"
                                placeholder="10 dígitos"
                                required
                            >
                        </div>

                        <span
                            class="mensaje-error-campo"
                            data-error-for="cedula"
                        ></span>
                    </div>

                    <div class="campo-prerreserva">
                        <label for="telefonoPrerreserva">
                            Celular
                        </label>

                        <div class="control-con-icono">
                            <i class="fa fa-phone"></i>
                            <input
                                type="tel"
                                id="telefonoPrerreserva"
                                name="telefono"
                                maxlength="13"
                                inputmode="tel"
                                autocomplete="tel"
                                placeholder="0991234567"
                                required
                            >
                        </div>

                        <span
                            class="mensaje-error-campo"
                            data-error-for="telefono"
                        ></span>
                    </div>

                    <div class="campo-prerreserva campo-ancho-completo">
                        <label for="correoPrerreserva">
                            Correo electrónico
                        </label>

                        <div class="control-con-icono">
                            <i class="fa fa-envelope"></i>
                            <input
                                type="email"
                                id="correoPrerreserva"
                                name="correo"
                                maxlength="150"
                                autocomplete="email"
                                placeholder="nombre@correo.com"
                                required
                            >
                        </div>

                        <span
                            class="mensaje-error-campo"
                            data-error-for="correo"
                        ></span>
                    </div>
                </div>

                <label class="aceptacion-prerreserva">
                    <input
                        type="checkbox"
                        name="acepta_condiciones"
                        value="1"
                        required
                    >

                    <span>
                        He revisado las condiciones del paquete y acepto la
                        <a
                            href="{{ route('politica.privacidad') }}"
                            target="_blank"
                            rel="noopener noreferrer"
                        >política de privacidad</a>.
                    </span>
                </label>

                <span
                    class="mensaje-error-campo error-aceptacion"
                    data-error-for="acepta_condiciones"
                ></span>

                <span
                    class="mensaje-error-campo error-general-prerreserva"
                    data-error-for="destino"
                ></span>

                <button
                    type="submit"
                    class="boton-enviar-prerreserva"
                    id="botonEnviarPrerreserva"
                >
                    <i class="fa fa-paper-plane"></i>

                    <span>
                        <strong>Enviar prerreserva</strong>
                        <small>Sin pagos ni cargos</small>
                    </span>
                </button>
            </form>

            <div
                class="prerreserva-confirmacion"
                id="confirmacionPrerreserva"
                role="status"
                hidden
            >
                <span>
                    <i class="fa fa-check"></i>
                </span>

                <h3>¡Solicitud recibida!</h3>

                <p id="mensajeConfirmacionPrerreserva">
                    Tu prerreserva fue registrada correctamente.
                </p>

                <small>
                    Conserva este número de seguimiento:
                    <strong id="numeroPrerreserva"></strong>
                </small>
            </div>
        </div>
    </section>
</div>
