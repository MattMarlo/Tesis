@extends('layouts.main')

@section('titulo', $titulo)

@section('content')
<main id="main" class="main">

  <div class="pagetitle">
    <h1>Destinos</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">Inicio</a></li>
        <li class="breadcrumb-item active">Destinos</li>
      </ol>
    </nav>
  </div>

  <section class="section">
    <div class="card p-4">

      <!-- HEADER -->
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h5 class="mb-1">Explorar Destinos</h5>
          <p class="text-muted mb-0">Gestiona los destinos turísticos</p>
        </div>

        <a href="{{ route('destinos.create') }}" class="btn btn-primary">
          <i class="fa-solid fa-circle-plus me-2"></i> Nuevo Destino
        </a>
      </div>

      <!-- BUSCADOR -->
      <div class="mb-4">
        <input type="text" class="form-control form-control-lg" placeholder="Buscar destinos...">
      </div>

      <!-- GRID DE CARDS -->
      <div class="row">

        @foreach ($destinos as $destino)
        <div class="col-lg-4 col-md-6 mb-4">
          <div class="card shadow-sm border-0 h-100">

            <!-- IMAGEN -->
            <div class="position-relative">
              <img src="{{ $destino->imagen ? asset('storage/' .$destino->imagen): 'https://via.placeholder.com/400x250' }}" 
                   class="card-img-top" 
                   style="height: 220px; object-fit: cover; border-radius: 10px 10px 0 0;">

              <span class="badge bg-light text-dark position-absolute top-0 end-0 m-2">
                {{ $destino->capacidad }} cupos
              </span>
            </div>

            <!-- CONTENIDO -->
            <div class="card-body d-flex flex-column">

              <div class="d-flex justify-content-between align-items-start">
                <h5 class="fw-bold mb-1">
                   {{ $destino->pais }}
                </h5>

                <a href="{{ route('destinos.edit', $destino->id) }}" class="text-dark">
                  <i class="bi bi-pencil-square"></i>
                </a>
              </div>

              <p class="text-muted mb-2">
                <i class="bi bi-geo-alt"></i> {{ $destino->etiqueta }}
              </p>

              <p class="text-muted small">
                {{ Str::limit($destino->descripcion, 100) }}
              </p>

              <!-- INFO -->
              <div class="d-flex justify-content-between align-items-center mt-auto mb-3">

                <span class="text-success fw-bold">
                  ${{ $destino->precio }}
                </span>

                <span>
                  <i class="bi bi-clock text-primary"></i> 
                  {{ $destino->dias }}d
                </span>

                <span>
                  <i class="bi bi-people text-purple"></i> 
                  {{ $destino->capacidad }}
                </span>

              </div>

              <!-- BOTONES -->
              <div class="d-flex gap-2">
                <a href="#" class="btn btn-primary w-100">
                  Ver detalles
                </a>

                <form action="{{ route('destinos.destroy', $destino->id) }}" method="POST" class="form-eliminar-destino">
                  @csrf
                  @method('DELETE')
                  <button type="button" class="btn btn-outline-danger btn-eliminar-destino">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
              </div>

            </div>
          </div>
        </div>
        @endforeach

      </div>

    </div>
  </section>

</main>

<script>
  document.addEventListener('DOMContentLoaded', function() {
      // Seleccionar todos los botones de eliminar
      const botonesEliminar = document.querySelectorAll('.btn-eliminar-destino');
      
      botonesEliminar.forEach(boton => {
          boton.addEventListener('click', function(e) {
              e.preventDefault(); // Evita el envío inmediato
              
              const form = this.closest('form'); // Obtener el formulario padre
              
              Swal.fire({
                  title: '¿Estás seguro?',
                  text: "Esta acción eliminará el destino y no se podrá deshacer.",
                  icon: 'warning',
                  showCancelButton: true,
                  confirmButtonColor: '#d33',
                  cancelButtonColor: '#6c757d',
                  confirmButtonText: 'Sí, eliminar',
                  cancelButtonText: 'Cancelar'
              }).then((result) => {
                  if (result.isConfirmed) {
                      form.submit(); // Envía el formulario
                  }
              });
          });
      });
  });
</script>

@endsection