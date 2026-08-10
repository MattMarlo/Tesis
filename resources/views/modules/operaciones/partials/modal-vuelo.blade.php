<div
    class="modal fade"
    id="modalVuelo"
    tabindex="-1"
    aria-labelledby="tituloModalVuelo"
    aria-hidden="true"
>
    <div
        class="
            modal-dialog
            modal-xl
            modal-dialog-centered
            modal-dialog-scrollable
        "
    >
        <form
            id="formularioVuelo"
            method="POST"
            action="{{ route(
                'operaciones.vuelos.store',
                $operacion->id
            ) }}"
            class="modal-content modal-expediente"
            novalidate
        >
            @csrf

            <input
                type="hidden"
                name="_method"
                id="vueloMetodo"
                value="PUT"
                disabled
            >

            {{--
                Cuando el modal se abre desde una tarea, este campo
                permite crear y vincular el vuelo automáticamente.
            --}}
            <input
                type="hidden"
                name="tarea_id"
                id="vueloTareaId"
                value=""
            >

            <div class="modal-header">
                <div>
                    <span>Transporte aéreo</span>

                    <h2 id="tituloModalVuelo">
                        Agregar vuelo
                    </h2>
                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Cerrar"
                ></button>
            </div>

            <div class="modal-body">
                {{--
                    Este bloque permanecerá oculto cuando el vuelo se
                    cree desde el módulo general. Se mostrará cuando
                    el modal se abra desde una tarea del itinerario.
                --}}
                <div
                    id="vueloContextoTarea"
                    class="contexto-tarea-modal"
                    hidden
                >
                    <div class="contexto-tarea-modal-icono">
                        <i class="bi bi-airplane"></i>
                    </div>

                    <div class="contexto-tarea-modal-contenido">
                        <span>
                            Tarea del itinerario
                        </span>

                        <strong id="vueloContextoNombre">
                            Vuelo
                        </strong>

                        <small id="vueloContextoProgramacion">
                        </small>

                        <p>
                            El vuelo creado quedará vinculado con esta
                            tarea. Su estado se actualizará según la
                            confirmación del vuelo y los boletos
                            emitidos para los viajeros.
                        </p>
                    </div>
                </div>

                <div class="formulario-expediente-grid">
                    <div class="campo-expediente">
                        <label for="vueloTipoTramo">
                            Tipo de tramo <strong>*</strong>
                        </label>

                        <select
                            id="vueloTipoTramo"
                            name="tipo_tramo"
                            required
                        >
                            <option value="">
                                Selecciona una opción
                            </option>

                            <option value="ida">
                                Ida
                            </option>

                            <option value="regreso">
                                Regreso
                            </option>

                            <option value="conexion">
                                Conexión
                            </option>
                        </select>
                    </div>

                    <div class="campo-expediente">
                        <label for="vueloAerolinea">
                            Aerolínea <strong>*</strong>
                        </label>

                        <input
                            id="vueloAerolinea"
                            name="aerolinea"
                            type="text"
                            minlength="2"
                            maxlength="120"
                            autocomplete="organization"
                            required
                            aria-describedby="vueloAerolineaError"
                        >

                        <small
                            id="vueloAerolineaError"
                            class="mensaje-validacion-vuelo"
                            role="alert"
                            hidden
                        >
                            La aerolínea debe tener al menos 2 caracteres.
                        </small>
                    </div>

                    <div class="campo-expediente">
                        <label for="vueloNumero">
                            Número de vuelo
                        </label>

                        <input
                            id="vueloNumero"
                            name="numero_vuelo"
                            type="text"
                            maxlength="30"
                            placeholder="Ejemplo: LA 1447"
                        >
                    </div>

                    <div class="campo-expediente">
                        <label for="vueloEstado">
                            Estado <strong>*</strong>
                        </label>

                        <select
                            id="vueloEstado"
                            name="estado"
                            required
                        >
                            <option value="confirmado">
                                Confirmado
                            </option>

                            <option value="pendiente">
                                Pendiente
                            </option>

                            <option value="cancelado">
                                Cancelado
                            </option>
                        </select>
                    </div>

                    <div class="campo-expediente">
                        <label for="vueloCiudadOrigen">
                            Ciudad de origen <strong>*</strong>
                        </label>

                        <input
                            id="vueloCiudadOrigen"
                            name="ciudad_origen"
                            type="text"
                            maxlength="120"
                            required
                        >
                    </div>

                    <div class="campo-expediente">
                        <label for="vueloAeropuertoOrigen">
                            Aeropuerto de origen
                        </label>

                        <input
                            id="vueloAeropuertoOrigen"
                            name="aeropuerto_origen"
                            type="text"
                            maxlength="150"
                        >
                    </div>

                    <div class="campo-expediente">
                        <label for="vueloCiudadDestino">
                            Ciudad de destino <strong>*</strong>
                        </label>

                        <input
                            id="vueloCiudadDestino"
                            name="ciudad_destino"
                            type="text"
                            maxlength="120"
                            required
                        >
                    </div>

                    <div class="campo-expediente">
                        <label for="vueloAeropuertoDestino">
                            Aeropuerto de destino
                        </label>

                        <input
                            id="vueloAeropuertoDestino"
                            name="aeropuerto_destino"
                            type="text"
                            maxlength="150"
                        >
                    </div>

                    <div class="campo-expediente">
                        <label for="vueloSalida">
                            Fecha y hora de salida
                            <strong>*</strong>
                        </label>

                        <input
                            id="vueloSalida"
                            name="fecha_hora_salida"
                            type="datetime-local"
                            required
                            aria-describedby="vueloFechasError"
                        >
                    </div>

                    <div class="campo-expediente">
                        <label for="vueloLlegada">
                            Fecha y hora de llegada
                            <strong>*</strong>
                        </label>

                        <input
                            id="vueloLlegada"
                            name="fecha_hora_llegada"
                            type="datetime-local"
                            required
                            aria-describedby="vueloFechasError"
                        >

                        <small
                            id="vueloFechasError"
                            class="mensaje-validacion-vuelo"
                            role="alert"
                            hidden
                        >
                            La fecha y hora de llegada debe ser posterior a la salida.
                        </small>
                    </div>

                    <div class="campo-expediente">
                        <label for="vueloTerminalSalida">
                            Terminal de salida
                        </label>

                        <input
                            id="vueloTerminalSalida"
                            name="terminal_salida"
                            type="text"
                            maxlength="50"
                        >
                    </div>

                    <div class="campo-expediente">
                        <label for="vueloTerminalLlegada">
                            Terminal de llegada
                        </label>

                        <input
                            id="vueloTerminalLlegada"
                            name="terminal_llegada"
                            type="text"
                            maxlength="50"
                        >
                    </div>

                    <div class="campo-expediente">
                        <label for="vueloLocalizador">
                            Localizador de reserva
                        </label>

                        <input
                            id="vueloLocalizador"
                            name="localizador_reserva"
                            type="text"
                            maxlength="80"
                        >
                    </div>

                    <div class="campo-expediente">
                        <label for="vueloEquipaje">
                            Equipaje incluido
                        </label>

                        <input
                            id="vueloEquipaje"
                            name="equipaje_incluido"
                            type="text"
                            maxlength="150"
                            placeholder="Ejemplo: maleta de 23 kg"
                        >
                    </div>

                    <div class="campo-expediente">
                        <label for="vueloProveedor">
                            Proveedor
                        </label>

                        <input
                            id="vueloProveedor"
                            name="proveedor"
                            type="text"
                            maxlength="150"
                        >
                    </div>

                    <div class="campo-expediente">
                        <label for="vueloFechaCompra">
                            Fecha de compra
                        </label>

                        <input
                            id="vueloFechaCompra"
                            name="fecha_compra"
                            type="date"
                            aria-describedby="vueloFechaCompraError"
                        >

                        <small
                            id="vueloFechaCompraError"
                            class="mensaje-validacion-vuelo"
                            role="alert"
                            hidden
                        ></small>
                    </div>

                    <div class="campo-expediente">
                        <label for="vueloCosto">
                            Costo total
                        </label>

                        <input
                            id="vueloCosto"
                            name="costo_total"
                            type="number"
                            min="0"
                            step="0.01"
                            aria-describedby="vueloCostoError"
                        >

                        <small
                            id="vueloCostoError"
                            class="mensaje-validacion-vuelo"
                            role="alert"
                            hidden
                        >
                            El costo debe ser un número igual o mayor que cero.
                        </small>
                    </div>

                    <div class="campo-expediente">
                        <label for="vueloMoneda">
                            Moneda <strong>*</strong>
                        </label>

                        <select
                            id="vueloMoneda"
                            name="moneda"
                            required
                        >
                            <option value="USD">
                                USD
                            </option>

                            <option value="EUR">
                                EUR
                            </option>

                            <option value="PEN">
                                PEN
                            </option>
                        </select>
                    </div>

                    <div
                        class="
                            campo-expediente
                            campo-completo
                        "
                    >
                        <label for="vueloObservaciones">
                            Observaciones
                        </label>

                        <textarea
                            id="vueloObservaciones"
                            name="observaciones"
                            rows="3"
                            maxlength="1000"
                            placeholder="Información adicional del vuelo..."
                        ></textarea>
                    </div>
                </div>

                <div class="nota-interna-expediente">
                    <i class="bi bi-info-circle"></i>

                    El proveedor y el costo son datos internos y no
                    se enviarán al cliente.
                </div>
            </div>

            <div class="modal-footer">
                <button
                    type="button"
                    class="btn-secundario-expediente"
                    data-bs-dismiss="modal"
                >
                    Cancelar
                </button>

                <button
                    type="submit"
                    class="btn-principal-expediente"
                >
                    <span>Guardar vuelo</span>
                    <i class="bi bi-check-lg"></i>
                </button>
            </div>
        </form>
    </div>
</div>
