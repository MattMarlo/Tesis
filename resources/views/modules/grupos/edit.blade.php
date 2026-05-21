@extends('layouts.main')

@section('titulo', 'Editar Grupo')

@section('content')
<main id="main" class="main">

  <!-- HEADER -->
  <div class="pagetitle mb-4">
    <h1 class="fw-bold">Editar Grupo</h1>
    <nav>
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="/">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('grupos') }}">Grupos</a></li>
        <li class="breadcrumb-item active">Editar</li>
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
              Editar Grupo
            </h5>

            <form action="{{ route('grupos.update', $grupo->id) }}" id="form_grupo" method="post">
              @csrf
              @method('PUT')

              <div class="row g-3">

                <!-- NOMBRE DEL GRUPO -->
                <div class="col-12">
                  <label for="nombre_grupo" class="form-label">Nombre del Grupo</label>
                  <input required
                    class="form-control"
                    placeholder="Ingrese el nombre del grupo"
                    type="text"
                    name="nombre_grupo"
                    id="nombre_grupo"
                    value="{{ $grupo->nombre_grupo }}">
                </div>

                <!-- DESCRIPCION -->
                <div class="col-12">
                  <label for="descripcion" class="form-label">Descripción</label>
                  <textarea required
                    class="form-control"
                    placeholder="Ingrese la descripción del grupo"
                    name="descripcion"
                    id="descripcion"
                    rows="4">{{ $grupo->descripcion }}</textarea>
                </div>

              </div>

              <!-- BOTONES -->
              <div class="d-flex justify-content-end mt-4">
                <a href="{{ route('grupos') }}" class="btn btn-secondary me-2">
                  <i class="bi bi-arrow-left me-1"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                  <i class="bi bi-check-circle me-1"></i> Actualizar Grupo
                </button>
              </div>

            </form>

          </div>
        </div>

      </div>
    </div>
  </section>

</main>
@endsection