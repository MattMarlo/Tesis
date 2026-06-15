@extends('layouts.main')

@section('titulo', 'Nueva Reserva Individual')

@section('content')
<main id="main" class="main">

  <!-- HEADER -->
  <div class="pagetitle mb-4">
    <h1 class="fw-bold">Nueva Reserva Individual</h1>
    <nav>
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="/">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('reservas') }}">Reservas</a></li>
        <li class="breadcrumb-item active">Nueva Individual</li>
      </ol>
    </nav>
  </div>

  <!-- CONTENIDO -->
  <section class="section">
    <div class="row">
      <div class="col-12">

        <div class="card shadow-sm border-0">
          <div class="card-body p-4">

            <h5 class="card-title mb-4 fw-semibold">
              Nueva Reserva Individual
            </h5>

            <form action="{{ route('reservas_individual.store') }}" id="form_reserva_individual" method="post">
              @csrf
              <div id="errores-generales" class="alert alert-danger d-none"></div>
              <div class="row g-3">

                <!-- DATOS DEL TITULAR -->
                <div class="col-12">
                  <h6 class="text-primary fw-bold mb-3">Datos del Titular</h6>
                </div>

                <!-- CLIENTE -->
                <div class="col-12 col-md-6">
                  <label for="cliente_id" class="form-label">Cliente</label>
                  <select name="cliente_id" class="form-select" required>
                    <option value="">Seleccionar cliente...</option>
                    @foreach($clientes as $cliente)
                      <option value="{{ $cliente->id }}" {{ old('cliente_id') == $cliente->id ? 'selected' : '' }}>{{ $cliente->nombres }} {{ $cliente->apellidos }}</option>
                    @endforeach
                  </select>
                </div>

                <!-- DATOS DEL VIAJE -->
                <div class="col-12 mt-4">
                  <h6 class="text-primary fw-bold mb-3">Datos del Viaje</h6>
                </div>

                <!-- DESTINO -->
                <div class="col-12 col-md-6">
                  <label for="destino_id" class="form-label">Destino</label>
                  <select name="destino_id" class="form-select" required>
                    <option value="">Seleccionar destino...</option>
                    @foreach($destinos as $destino)
                      <option value="{{ $destino->id }}" {{ old('destino_id') == $destino->id ? 'selected' : '' }}>{{ $destino->pais }}</option>
                    @endforeach
                  </select>
                </div>

                <!-- FECHA RESERVA -->
                <div class="col-12 col-md-6">
                  <label for="fecha_reserva" class="form-label">Fecha Reserva</label>
                  <input type="date" name="fecha_reserva" class="form-control" value="{{ old('fecha_reserva', date('Y-m-d')) }}" required>
                </div>

                <!-- FECHA DE VIAJE -->
                <div class="col-12 col-md-6">
                  <label for="fecha_viaje" class="form-label">Fecha de Viaje</label>
                  <input type="date" name="fecha_viaje" class="form-control" value="{{ old('fecha_viaje') }}" required>
                </div>

                <!-- MONTO TOTAL -->
                <div class="col-12 col-md-6">
                  <label for="precio_total_viaje" class="form-label">Precio del viaje</label>
                  <input type="number" step="0.01" name="precio_total_viaje" class="form-control" placeholder="0.00" value="{{ old('precio_total_viaje') }}" required>
                </div>
                
                <!--Estado reserva
                <div class="col-12 col-md-6">
                    <label for="estado" class="form-label">Estado Reserva</label>
                    <select name="estado" class="form-select" required>
                        <option value="">Seleccione estado</option>
                        <option value="confirmada">Confirmada</option>
                        <option value="pendiente">Pendiente</option>
                        <option value="cancelada">Cancelada</option>
                    </select>
                </div> -->

                <!-- PRIMER PAGO (OPCIONAL) -->
                <div class="col-12 mt-4">
                  <h6 class="text-primary fw-bold mb-3">Primer Pago (Opcional)</h6>
                </div>

                <!-- MONTO A COBRAR -->
                <div class="col-12 col-md-4">
                  <label for="monto_depositado" class="form-label">Monto a Cobrar</label>
                  <input type="number" step="0.01" name="monto_depositado" class="form-control" placeholder="0.00" value="{{ old('monto_depositado') }}">
                </div>

                <!-- MÉTODO -->
                <div class="col-12 col-md-4">
                  <label for="metodo_pago" class="form-label">Método</label>
                  <select name="metodo_pago" class="form-select">
                    <option value="efectivo" {{ old('metodo_pago') == 'efectivo' ? 'selected' : '' }}>Efectivo</option>
                    <option value="transferencia" {{ old('metodo_pago') == 'transferencia' ? 'selected' : '' }}>Transferencia</option>
                    <option value="tarjeta" {{ old('metodo_pago') == 'tarjeta' ? 'selected' : '' }}>Tarjeta</option>
                  </select>
                </div>

                <!-- FECHA PAGO -->
                <div class="col-12 col-md-4">
                  <label for="fecha_pago" class="form-label">Fecha Pago</label>
                  <input type="date" name="fecha_pago" class="form-control" value="{{ old('fecha_pago', date('Y-m-d')) }}">
                </div>

              </div>

              <!-- BOTONES -->
              <div class="d-flex justify-content-end mt-4">
                <a href="{{ route('reservas') }}" class="btn btn-secondary me-2">
                  <i class="bi bi-arrow-left me-1"></i> Cancelar
                </a>
                <button type="button" class="btn btn-outline-secondary me-2">
                  <i class="bi bi-save me-1"></i> Guardar Borrador
                </button>
                <button type="submit" class="btn btn-primary">
                  <i class="bi bi-check-circle me-1"></i> Crear Reserva
                </button>
              </div>

            </form>

          </div>
        </div>

      </div>
    </div>
  </section>

</main>
<script>
document.getElementById('form_reserva_individual').addEventListener('submit', async function (e) {
  e.preventDefault();
  const form = e.target;
  const formData = new FormData(form);
  const submitBtn = form.querySelector('button[type="submit"]');
  const originalText = submitBtn.innerHTML;

  submitBtn.disabled = true;
  submitBtn.innerHTML = 'Guardando...';

  try {
    const response = await fetch(form.action, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        'Accept': 'application/json'
      },
      body: formData
    });

    const data = await response.json();

    if (response.ok && data.success) {
      window.location.href = data.redirect;
    }
    else if (response.status === 422 && data.errors) {
      document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
      document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
      
      for (const [key, messages] of Object.entries(data.errors)) {
        let input = form.querySelector(`[name="${key}"]`);
        if (input) {
          input.classList.add('is-invalid');
          let feedback = document.createElement('div');
          feedback.className = 'invalid-feedback';
          feedback.innerText = messages[0];
          input.parentNode.appendChild(feedback);
        } else {
          let errorDiv = document.getElementById('errores-generales');
          errorDiv.innerHTML = messages[0];
          errorDiv.classList.remove('d-none');
        }
      }
    }
    else {
      let errorDiv = document.getElementById('errores-generales');
      errorDiv.innerHTML = data.message || 'Error inesperado. Intente de nuevo.';
      errorDiv.classList.remove('d-none');
    }
  } catch (error) {
    console.error(error);
    let errorDiv = document.getElementById('errores-generales');
    errorDiv.innerHTML = 'Error de conexión. Revisa tu internet.';
    errorDiv.classList.remove('d-none');
  } finally {
    submitBtn.disabled = false;
    submitBtn.innerHTML = originalText;
  }
});
</script>
@endsection