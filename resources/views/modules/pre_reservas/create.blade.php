@extends('layouts.main')

@section('header','Crear Pre-reserva')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Agregar Pre-reserva</h2>
        <a href="{{ route('prereservas.index') }}" class="btn btn-outline-secondary">Volver</a>
    </div>

    <div class="card shadow-sm p-4">
        <form id="preReservaForm" action="{{ route('prereservas.store') }}" method="POST">
            @csrf
            <div class="row gy-3">
                <div class="col-md-6">
                    <label class="form-label">Nombre completo del cliente</label>
                    <input type="text" name="cliente_nombre" id="clienteNombre" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Cédula</label>
                    <input type="text" name="cedula" id="cedula" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Destino (etiqueta)</label>
                    <input type="text" name="destino" id="destino" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Fecha de viaje</label>
                    <input type="date" name="fecha_viaje" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="telefono" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control">
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="crearReservaCheckbox" name="crear_reserva">
                        <label class="form-check-label" for="crearReservaCheckbox">Crear reserva automáticamente después de guardar la pre-reserva</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Monto depositado (opcional)</label>
                    <input type="number" name="monto_depositado" step="0.01" class="form-control" placeholder="0.00">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Guardar Pre-reserva</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Cliente -->
<div class="modal fade" id="clienteModal" tabindex="-1" aria-labelledby="clienteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="clienteModalLabel">Cliente no encontrado</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p>El cliente ingresado no existe. Completa los datos para crearlo.</p>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Apellidos</label>
                        <input type="text" id="modalClienteApellidos" class="form-control" placeholder="Apellidos" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Teléfono</label>
                        <input type="text" id="modalClienteTelefono" class="form-control" placeholder="Teléfono" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" id="modalClienteEmail" class="form-control" placeholder="Email" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="saveClientBtn" class="btn btn-primary">Guardar Cliente</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Destino -->
<div class="modal fade" id="destinoModal" tabindex="-1" aria-labelledby="destinoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="destinoModalLabel">Destino no encontrado</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p>El destino ingresado no existe. Completa los datos para crearlo.</p>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">País</label>
                        <input type="text" id="modalDestinoPais" class="form-control" placeholder="País" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Precio</label>
                        <input type="number" id="modalDestinoPrecio" class="form-control" placeholder="Precio" step="0.01" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Días</label>
                        <input type="number" id="modalDestinoDias" class="form-control" placeholder="Días" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Capacidad</label>
                        <input type="number" id="modalDestinoCapacidad" class="form-control" placeholder="Capacidad" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="saveDestinoBtn" class="btn btn-primary">Guardar Destino</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    const checkUrl = '{{ route('prereservas.check') }}';
    const preReservaForm = document.getElementById('preReservaForm');
    const clienteModal = new bootstrap.Modal(document.getElementById('clienteModal'));
    const destinoModal = new bootstrap.Modal(document.getElementById('destinoModal'));
    let clienteNeedsCreate = false;
    let destinoNeedsCreate = false;

    preReservaForm.addEventListener('submit', async function(event) {
        event.preventDefault();

        const cedula = document.getElementById('cedula').value.trim();
        const destino = document.getElementById('destino').value.trim();

        if (!cedula || !destino) {
            preReservaForm.submit();
            return;
        }

        const response = await fetch(checkUrl + '?cedula=' + encodeURIComponent(cedula) + '&destino=' + encodeURIComponent(destino), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        if (!response.ok) {
            preReservaForm.submit();
            return;
        }

        const data = await response.json();
        clienteNeedsCreate = !data.cliente.exists;
        destinoNeedsCreate = !data.destino.exists;

        if (clienteNeedsCreate) {
            clienteModal.show();
            return;
        }

        if (destinoNeedsCreate) {
            destinoModal.show();
            return;
        }

        preReservaForm.submit();
    });

    document.getElementById('saveClientBtn').addEventListener('click', function() {
        const email = document.getElementById('modalClienteEmail').value.trim();
        const telefono = document.getElementById('modalClienteTelefono').value.trim();
        const apellidos = document.getElementById('modalClienteApellidos').value.trim();

        if (!email || !telefono || !apellidos) {
            alert('Completa todos los datos del cliente antes de continuar.');
            return;
        }

        addOrUpdateHiddenInput('email', email);
        addOrUpdateHiddenInput('telefono', telefono);
        addOrUpdateHiddenInput('apellidos', apellidos);

        clienteNeedsCreate = false;
        clienteModal.hide();

        if (destinoNeedsCreate) {
            destinoModal.show();
            return;
        }

        preReservaForm.submit();
    });

    document.getElementById('saveDestinoBtn').addEventListener('click', function() {
        const pais = document.getElementById('modalDestinoPais').value.trim();
        const precio = document.getElementById('modalDestinoPrecio').value.trim();
        const dias = document.getElementById('modalDestinoDias').value.trim();
        const capacidad = document.getElementById('modalDestinoCapacidad').value.trim();

        if (!pais || !precio || !dias || !capacidad) {
            alert('Completa todos los datos del destino antes de continuar.');
            return;
        }

        addOrUpdateHiddenInput('pais', pais);
        addOrUpdateHiddenInput('precio', precio);
        addOrUpdateHiddenInput('dias', dias);
        addOrUpdateHiddenInput('capacidad', capacidad);

        destinoNeedsCreate = false;
        destinoModal.hide();
        preReservaForm.submit();
    });

    function addOrUpdateHiddenInput(name, value) {
        let input = preReservaForm.querySelector('input[name="' + name + '"]');
        if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            preReservaForm.appendChild(input);
        }
        input.value = value;
    }
</script>
@endsection
