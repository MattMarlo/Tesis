@extends('layouts.main')

@section('titulo', $titulo)

@section('content')
<main id="main" class="main">
  <div class="pagetitle">
    <h1>Usarios</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">Inicio</a></li>
        <li class="breadcrumb-item active">Usuarios</li>
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
                <h5 class="card-title mb-1">Administrar Usuarios</h5>
                <p class="text-muted mb-0">Gestiona y administra todos los usuarios del sistema</p>
              </div>
              <a href="{{route('usuarios.create')}}" class="btn btn-primary">
                <i class="fa-solid fa-circle-plus me-2"></i> Agregar Nueva Usuario
              </a>
            </div>

            <table class="table datatable table-striped table-hover">
              <thead class="table-light">
                <tr>
                  <th class="text-center">#</th>
                  <th class="text-start">Nombres</th>
                  <th class="text-start">Apellidos</th>
                  <th class="text-start">Email</th>
                  <th class="text-start">Telefono</th>
                  <th class="text-start">Documento</th>
                  <th class="text-start">Rol</th>
                  <th class="text-center">Acciones </th>
                </tr>
              </thead>
              <tbody>
                  @foreach ($usuarios as $usuario)
                  <tr>
                    <td class="text-center fw-semibold">{{ $usuario->id }}</td>
                    <td class="text-start">{{ $usuario->nombres }}</td>
                    <td class="text-start">{{ $usuario->apellidos }}</td>
                    <td class="text-start">{{ $usuario->email }}</td>
                    <td class="text-start">{{ $usuario->telefono }}</td>
                    <td class="text-start">{{ $usuario->documento }}</td>
                    <td class="text-start">{{ $usuario->rol }}</td>
                    <td class="text-center">
                      <div class="btn-group" role="group">
                        <a href="{{ route('usuarios.edit', $usuario->id) }}" class="btn btn-outline-warning btn-sm">
                          <i class="bi bi-pencil-square"></i>
                        </a>
                        <form action="{{ route('usuarios.destroy', $usuario->id) }}" method="POST" class="d-inline">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('¿Estás seguro de eliminar este país?')">
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