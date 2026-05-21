@extends('layouts.main')

@section('titulo', $titulo)

@section('content')
<main id="main" class="main">
  <div class="pagetitle">
    <h1>Grupos</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">Inicio</a></li>
        <li class="breadcrumb-item active">Grupos</li>
      </ol>
    </nav>
  </div><!-- End Page Title -->

  <section class="section">
    <div class="row">
      <div class="col-lg-12">
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4">
              <div>
                <h5 class="card-title mb-1">Administrar Grupos</h5>
                <p class="text-muted mb-0">Gestiona y administra todos los Grupos del sistema</p>
              </div>
              <a href="{{ route('grupos.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-circle-plus me-2"></i> Agregar Nuevo Grupo
              </a>
            </div>

            <table class="table datatable table-striped table-hover">
              <thead class="table-light">
                <tr>
                  <th class="text-center">ID</th>
                  <th class="text-start">Nombre del Grupo</th>
                  <th class="text-start">Descripción</th>
                  <th class="text-start">Fecha Registro</th>
                  <th class="text-center">Acciones</th>
                </tr>
              </thead>
              <tbody>
                  @foreach ($grupos as $grupo)
                  <tr>
                    <td class="text-center fw-semibold">{{ $grupo->id }}</td>
                    <td class="text-start">{{ $grupo->nombre_grupo }}</td>
                    <td class="text-start">{{ $grupo->descripcion }}</td>
                    <td class="text-start">{{ $grupo->created_at->format('d/m/Y') }}</td>
                    <td class="text-center">
                      <div class="btn-group" role="group">
                        <a href="{{ route('grupos.edit', $grupo->id) }}" class="btn btn-outline-warning btn-sm">
                          <i class="bi bi-pencil-square"></i>
                        </a>
                        <form action="{{ route('grupos.destroy', $grupo->id) }}" method="POST" class="d-inline">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('¿Estás seguro de eliminar este grupo?')">
                            <i class="bi bi-trash3-fill"></i>
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>
                  @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </section>
</main>
@endsection