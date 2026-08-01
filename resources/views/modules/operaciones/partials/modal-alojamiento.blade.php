<div
    class="modal fade"
    id="modalAlojamiento"
    tabindex="-1"
    aria-hidden="true"
>
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <form
            id="formularioAlojamiento"
            method="POST"
            action="{{ route(
                'operaciones.alojamientos.store',
                $operacion->id
            ) }}"
            class="modal-content modal-expediente"
            novalidate
        >
            @csrf

            <input
                type="hidden"
                name="_method"
                id="alojamientoMetodo"
                value="PUT"
                disabled
            >

            <div class="modal-header">
                <div>
                    <span>Hospedaje</span>

                    <h2 id="tituloModalAlojamiento">
                        Agregar alojamiento
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
                        <label for="alojamientoHotel">
                            Nombre del hotel
                            <strong>*</strong>
                        </label>

                        <input
                            id="alojamientoHotel"
                            name="nombre_hotel"
                            type="text"
                            maxlength="180"
                            required
                        >
                    </div>

                    <div class="campo-expediente">
                        <label for="alojamientoEstado">
                            Estado <strong>*</strong>
                        </label>

                        <select
                            id="alojamientoEstado"
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
                        <label for="alojamientoCiudad">
                            Ciudad <strong>*</strong>
                        </label>

                        <input
                            id="alojamientoCiudad"
                            name="ciudad"
                            type="text"
                            maxlength="120"
                            required
                        >
                    </div>

                    <div class="campo-expediente">
                        <label for="alojamientoPais">
                            País <strong>*</strong>
                        </label>

                        <input
                            id="alojamientoPais"
                            name="pais"
                            type="text"
                            maxlength="120"
                            required
                        >
                    </div>

                    <div class="campo-expediente campo-completo">
                        <label for="alojamientoDireccion">
                            Dirección
                        </label>

                        <input
                            id="alojamientoDireccion"
                            name="direccion"
                            type="text"
                            maxlength="255"
                        >
                    </div>

                    <div class="campo-expediente">
                        <label for="alojamientoEntrada">
                            Fecha y hora de entrada
                            <strong>*</strong>
                        </label>

                        <input
                            id="alojamientoEntrada"
                            name="fecha_hora_entrada"
                            type="datetime-local"
                            required
                        >
                    </div>

                    <div class="campo-expediente">
                        <label for="alojamientoSalida">
                            Fecha y hora de salida
                            <strong>*</strong>
                        </label>

                        <input
                            id="alojamientoSalida"
                            name="fecha_hora_salida"
                            type="datetime-local"
                            required
                        >
                    </div>

                    <div class="campo-expediente">
                        <label for="alojamientoConfirmacion">
                            Código de confirmación
                        </label>

                        <input
                            id="alojamientoConfirmacion"
                            name="codigo_confirmacion"
                            type="text"
                            maxlength="100"
                        >
                    </div>

                    <div class="campo-expediente">
                        <label for="alojamientoTipoHabitacion">
                            Tipo de habitación <strong>*</strong>
                        </label>

                        <input
                            id="alojamientoTipoHabitacion"
                            name="tipo_habitacion"
                            type="text"
                            maxlength="120"
                            placeholder="Ejemplo: doble"
                            required
                        >
                    </div>

                    <div class="campo-expediente">
                        <label for="alojamientoCantidad">
                            Cantidad de habitaciones
                            <strong>*</strong>
                        </label>

                        <input
                            id="alojamientoCantidad"
                            name="cantidad_habitaciones"
                            type="number"
                            min="1"
                            max="100"
                            value="1"
                            required
                        >
                    </div>

                    <div class="campo-expediente">
                        <label for="alojamientoAlimentacion">
                            Alimentación incluida
                        </label>

                        <input
                            id="alojamientoAlimentacion"
                            name="alimentacion_incluida"
                            type="text"
                            maxlength="120"
                            placeholder="Ejemplo: desayuno"
                        >
                    </div>

                    <div class="campo-expediente campo-completo">
                        <label for="alojamientoDistribucion">
                            Distribución de habitaciones
                        </label>

                        <textarea
                            id="alojamientoDistribucion"
                            name="distribucion_habitaciones"
                            rows="3"
                            maxlength="2000"
                            placeholder="Indica qué viajeros ocuparán cada habitación."
                        ></textarea>
                    </div>

                    <div class="campo-expediente">
                        <label for="alojamientoTelefono">
                            Teléfono del hotel
                        </label>

                        <input
                            id="alojamientoTelefono"
                            name="telefono_hotel"
                            type="text"
                            maxlength="30"
                        >
                    </div>

                    <div class="campo-expediente">
                        <label for="alojamientoCorreo">
                            Correo del hotel
                        </label>

                        <input
                            id="alojamientoCorreo"
                            name="correo_hotel"
                            type="email"
                            maxlength="150"
                        >
                    </div>

                    <div class="campo-expediente">
                        <label for="alojamientoProveedor">
                            Proveedor
                        </label>

                        <input
                            id="alojamientoProveedor"
                            name="proveedor"
                            type="text"
                            maxlength="150"
                        >
                    </div>

                    <div class="campo-expediente">
                        <label for="alojamientoFechaCompra">
                            Fecha de compra
                        </label>

                        <input
                            id="alojamientoFechaCompra"
                            name="fecha_compra"
                            type="date"
                        >
                    </div>

                    <div class="campo-expediente">
                        <label for="alojamientoCosto">
                            Costo total
                        </label>

                        <input
                            id="alojamientoCosto"
                            name="costo_total"
                            type="number"
                            min="0"
                            step="0.01"
                        >
                    </div>

                    <div class="campo-expediente">
                        <label for="alojamientoMoneda">
                            Moneda <strong>*</strong>
                        </label>

                        <input
                            id="alojamientoMoneda"
                            name="moneda"
                            type="text"
                            value="USD"
                            maxlength="3"
                            required
                        >
                    </div>

                    <div class="campo-expediente campo-completo">
                        <label for="alojamientoObservaciones">
                            Observaciones
                        </label>

                        <textarea
                            id="alojamientoObservaciones"
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
                    <span>Guardar alojamiento</span>
                    <i class="bi bi-check-lg"></i>
                </button>
            </div>
        </form>
    </div>
</div>