<div
    class="modal fade"
    id="modalBoleto"
    tabindex="-1"
    aria-hidden="true"
>
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form
            id="formularioBoleto"
            method="POST"
            action=""
            enctype="multipart/form-data"
            class="modal-content modal-expediente"
            novalidate
        >
            @csrf

            <input
                type="hidden"
                name="cliente_id"
                id="boletoClienteId"
            >
            <input type="hidden" name="viajero_reserva_id" id="boletoViajeroReservaId">

            <div class="modal-header">
                <div>
                    <span>Documento de viaje</span>

                    <h2 id="tituloModalBoleto">
                        Asignar boleto
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
                <div class="viajero-boleto-seleccionado">
                    <i class="bi bi-person-check"></i>

                    <div>
                        <span>Viajero</span>
                        <strong id="boletoNombreViajero">
                            —
                        </strong>
                    </div>
                </div>

                <div class="formulario-expediente-grid">
                    <div class="campo-expediente">
                        <label for="boletoNumero">
                            Número de boleto
                        </label>

                        <input
                            id="boletoNumero"
                            name="numero_boleto"
                            type="text"
                            minlength="3"
                            maxlength="30"
                            placeholder="Ejemplo: 462-1234567890"
                        >
                    </div>

                    <div class="campo-expediente">
                        <label for="boletoAsiento">
                            Asiento
                        </label>

                        <input
                            id="boletoAsiento"
                            name="asiento"
                            type="text"
                            maxlength="4"
                            placeholder="Ejemplo: 14A"
                        >
                    </div>

                    <div class="campo-expediente">
                        <label for="boletoClase">
                            Clase
                        </label>

                        <input
                            id="boletoClase"
                            name="clase"
                            type="text"
                            minlength="2"
                            maxlength="50"
                            placeholder="Ejemplo: Económica"
                        >
                    </div>

                    <div class="campo-expediente">
                        <label for="boletoEstado">
                            Estado de emisión
                            <strong>*</strong>
                        </label>

                        <select
                            id="boletoEstado"
                            name="estado_emision"
                            required
                        >
                            <option value="pendiente">
                                Pendiente
                            </option>

                            <option value="emitido">
                                Emitido
                            </option>

                            <option value="cancelado">
                                Cancelado
                            </option>
                        </select>
                    </div>

                    <div class="campo-expediente campo-completo">
                        <label for="boletoArchivo">
                            Archivo del boleto
                        </label>

                        <input
                            id="boletoArchivo"
                            name="archivo_boleto"
                            type="file"
                            accept=".pdf,.jpg,.jpeg,.png"
                        >

                        <small>
                            Formatos permitidos: PDF, JPG, JPEG o PNG.
                            Tamaño máximo: 5 MB.
                        </small>

                        <div
                            id="boletoArchivoActual"
                            class="archivo-actual-boleto oculto"
                        ></div>
                    </div>

                    <div class="campo-expediente campo-completo">
                        <label for="boletoObservaciones">
                            Observaciones
                        </label>

                        <textarea
                            id="boletoObservaciones"
                            name="observaciones"
                            rows="3"
                            minlength="3"
                            maxlength="1000"
                        ></textarea>
                    </div>
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
                    <span>Guardar boleto</span>
                    <i class="bi bi-check-lg"></i>
                </button>
            </div>
        </form>
    </div>
</div>
