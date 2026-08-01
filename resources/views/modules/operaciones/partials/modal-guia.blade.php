<div
    class="modal fade"
    id="modalGuia"
    tabindex="-1"
    aria-hidden="true"
>
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <form
            id="formularioGuia"
            method="POST"
            action="{{ route(
                'operaciones.guias.store',
                $operacion->id
            ) }}"
            class="modal-content modal-expediente"
            novalidate
        >
            @csrf

            <input
                type="hidden"
                name="_method"
                id="guiaMetodo"
                value="PUT"
                disabled
            >

            <div class="modal-header">
                <div>
                    <span>Acompañamiento</span>

                    <h2 id="tituloModalGuia">
                        Agregar guía
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
                        <label for="guiaNombre">
                            Nombre completo
                            <strong>*</strong>
                        </label>

                        <input
                            id="guiaNombre"
                            name="nombre_completo"
                            type="text"
                            maxlength="180"
                            required
                        >
                    </div>

                    <div class="campo-expediente">
                        <label for="guiaEstado">
                            Estado <strong>*</strong>
                        </label>

                        <select
                            id="guiaEstado"
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
                        <label for="guiaEmpresa">
                            Empresa
                        </label>

                        <input
                            id="guiaEmpresa"
                            name="empresa"
                            type="text"
                            maxlength="150"
                        >
                    </div>

                    <div class="campo-expediente">
                        <label for="guiaCiudad">
                            Ciudad del servicio <strong>*</strong>
                        </label>

                        <input
                            id="guiaCiudad"
                            name="ciudad_servicio"
                            type="text"
                            maxlength="120"
                            required
                        >
                    </div>

                    <div class="campo-expediente">
                        <label for="guiaTelefono">
                            Teléfono <strong>*</strong>
                        </label>

                        <input
                            id="guiaTelefono"
                            name="telefono"
                            type="text"
                            maxlength="30"
                            required
                        >
                    </div>

                    <div class="campo-expediente">
                        <label for="guiaCorreo">
                            Correo electrónico
                        </label>

                        <input
                            id="guiaCorreo"
                            name="correo"
                            type="email"
                            maxlength="150"
                        >
                    </div>

                    <div class="campo-expediente">
                        <label for="guiaIdiomas">
                            Idiomas
                        </label>

                        <input
                            id="guiaIdiomas"
                            name="idiomas"
                            type="text"
                            maxlength="150"
                            placeholder="Ejemplo: español e inglés"
                        >
                    </div>

                    <div class="campo-expediente">
                        <label for="guiaContactoEmergencia">
                            Contacto de emergencia
                        </label>

                        <input
                            id="guiaContactoEmergencia"
                            name="contacto_emergencia"
                            type="text"
                            maxlength="150"
                        >
                    </div>

                    <div class="campo-expediente">
                        <label for="guiaFechaInicio">
                            Fecha de inicio <strong>*</strong>
                        </label>

                        <input
                            id="guiaFechaInicio"
                            name="fecha_inicio"
                            type="date"
                            required
                        >
                    </div>

                    <div class="campo-expediente">
                        <label for="guiaFechaFin">
                            Fecha de finalización <strong>*</strong>
                        </label>

                        <input
                            id="guiaFechaFin"
                            name="fecha_fin"
                            type="date"
                            required
                        >
                    </div>

                    <div class="campo-expediente campo-completo">
                        <label for="guiaPuntoEncuentro">
                            Punto de encuentro
                        </label>

                        <input
                            id="guiaPuntoEncuentro"
                            name="punto_encuentro"
                            type="text"
                            maxlength="255"
                        >
                    </div>

                    <div class="campo-expediente">
                        <label for="guiaHoraEncuentro">
                            Fecha y hora de encuentro
                        </label>

                        <input
                            id="guiaHoraEncuentro"
                            name="fecha_hora_encuentro"
                            type="datetime-local"
                        >
                    </div>

                    <div class="campo-expediente">
                        <label for="guiaCosto">
                            Costo total
                        </label>

                        <input
                            id="guiaCosto"
                            name="costo_total"
                            type="number"
                            min="0"
                            step="0.01"
                        >
                    </div>

                    <div class="campo-expediente">
                        <label for="guiaMoneda">
                            Moneda <strong>*</strong>
                        </label>

                        <input
                            id="guiaMoneda"
                            name="moneda"
                            type="text"
                            value="USD"
                            maxlength="3"
                            required
                        >
                    </div>

                    <div class="campo-expediente campo-completo">
                        <label for="guiaServicios">
                            Servicios incluidos
                        </label>

                        <textarea
                            id="guiaServicios"
                            name="servicios_incluidos"
                            rows="3"
                            maxlength="2000"
                            placeholder="Describe recorridos, traslados o actividades a cargo del guía."
                        ></textarea>
                    </div>

                    <div class="campo-expediente campo-completo">
                        <label for="guiaObservaciones">
                            Observaciones
                        </label>

                        <textarea
                            id="guiaObservaciones"
                            name="observaciones"
                            rows="3"
                            maxlength="1000"
                        ></textarea>
                    </div>
                </div>

                <div class="nota-interna-expediente">
                    <i class="bi bi-info-circle"></i>
                    El costo es un dato interno y no se enviará al cliente.
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
                    <span>Guardar guía</span>
                    <i class="bi bi-check-lg"></i>
                </button>
            </div>
        </form>
    </div>
</div>