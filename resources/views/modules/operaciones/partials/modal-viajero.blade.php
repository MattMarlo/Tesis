<div class="modal fade" id="modalViajero" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="formularioViajero" method="POST"
              action="{{ route('operaciones.viajeros.store', $operacion->id) }}"
              class="modal-content modal-expediente" novalidate>
            @csrf
            <input type="hidden" name="_method" id="viajeroMetodo" value="PUT" disabled>
            <div class="modal-header">
                <div><span>Integrante del viaje</span><h2 id="tituloModalViajero">Agregar acompañante</h2></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="formulario-expediente-grid">
                    <div class="campo-expediente"><label for="viajeroNombres">Nombres <strong>*</strong></label>
                        <input id="viajeroNombres" name="nombres" maxlength="120" required></div>
                    <div class="campo-expediente"><label for="viajeroApellidos">Apellidos <strong>*</strong></label>
                        <input id="viajeroApellidos" name="apellidos" maxlength="120" required></div>
                    <div class="campo-expediente"><label for="viajeroNacimiento">Fecha de nacimiento <strong>*</strong></label>
                        <input id="viajeroNacimiento" name="fecha_nacimiento" type="date" required></div>
                    <div class="campo-expediente"><label for="viajeroTipoDocumento">Tipo de documento</label>
                        <select id="viajeroTipoDocumento" name="tipo_documento">
                            <option value="">Pendiente</option><option value="cedula">Cédula</option><option value="pasaporte">Pasaporte</option>
                        </select></div>
                    <div class="campo-expediente"><label for="viajeroDocumento">Número de documento</label>
                        <input id="viajeroDocumento" name="documento" maxlength="50"></div>
                </div>
                <div class="nota-interna-expediente"><i class="bi bi-info-circle"></i>
                    La edad y categoría se calculan en el servidor usando la fecha del viaje.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secundario-expediente" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn-principal-expediente">Guardar viajero</button>
            </div>
        </form>
    </div>
</div>
