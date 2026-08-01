<div
    class="modal fade"
    id="modalVuelo"
    tabindex="-1"
    aria-hidden="true"
>
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
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
                            <option value="ida">Ida</option>
                            <option value="regreso">Regreso</option>
                            <option value="conexion">Conexión</option>
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
                            maxlength="120"
                            required
                        >
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
                            placeholder="Ejemplo: AV 1632"
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
                        >
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
                        >
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
                        >
                    </div>

                    <div class="campo-expediente">
                        <label for="vueloMoneda">
                            Moneda <strong>*</strong>
                        </label>

                        <input
                            id="vueloMoneda"
                            name="moneda"
                            type="text"
                            value="USD"
                            maxlength="3"
                            required
                        >
                    </div>

                    <div class="campo-expediente campo-completo">
                        <label for="vueloObservaciones">
                            Observaciones
                        </label>

                        <textarea
                            id="vueloObservaciones"
                            name="observaciones"
                            rows="3"
                            maxlength="1000"
                        ></textarea>
                    </div>
                </div>

                <div class="nota-interna-expediente">
                    <i class="bi bi-info-circle"></i>
                    El proveedor y costo son datos internos y no se
                    enviarán al cliente.
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