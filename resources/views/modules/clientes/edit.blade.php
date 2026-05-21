@extends('layouts.main')

@section('titulo', 'Editar Cliente')

@section('content')
<main id="main" class="main">

  <!-- HEADER -->
  <div class="pagetitle mb-4">
    <h1 class="fw-bold">Editar Cliente</h1>
    <nav>
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="/">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('usuarios') }}">Clientes</a></li>
      </ol>
    </nav>
  </div>

  <!-- CONTENIDO -->
  <section class="section">
    <div class="row">
      <div class="col-12">

        <div class="card shadow-sm border-0">
          <div class="card-body p-4">

            <form action="{{ route('clientes.update',$clientes->id) }}" id="form_disciplina" method="post">
              @csrf
              @method('PUT')
              <div class="row g-3">

                <!-- NOMBRES -->
                <div class="col-12 col-sm-6">
                  <label for="nombres" class="form-label">Nombres</label>
                  <input required
                    class="form-control"
                    placeholder="Ingrese los nombre del cliente"
                    type="text" 
                    name="nombres"
                    id="nombres"
                    value="{{$clientes->nombres}}">
                </div>

                <!-- APELLIDOS -->
                <div class="col-12 col-sm-6">
                  <label for="apellidos" class="form-label">Apellidos</label>
                  <input required
                    class="form-control"
                    placeholder="Ingrese los apellidos del cliente"
                    type="text" 
                    name="apellidos"
                    id="apellidos"
                    value="{{$clientes->apellidos}}">
                </div>

                <!-- EMAIL -->
                <div class="col-12">
                  <label for="email" class="form-label">Correo</label>
                  <input required
                    class="form-control"
                    placeholder="ingrese el correo del cliente"
                    type="email" 
                    name="email"
                    id="email"
                    value="{{$clientes->email}}">
                </div>

                <!-- TELEFONO -->
                <div class="col-12 col-sm-6">
                  <label for="telefono" class="form-label">Telefono</label>
                  <input required
                    class="form-control"
                    placeholder="Ingrese el telefono del cliente"
                    type="text" 
                    name="telefono"
                    id="telefono"
                    value="{{$clientes->telefono}}">
                </div>

                <!-- DOCUMENTO -->
                <div class="col-12 col-sm-6">
                  <label for="documento" class="form-label">Número Documento</label>
                  <input required
                    class="form-control"
                    placeholder="Ingrese el documento del cliente"
                    type="text" 
                    name="documento"
                    id="documento"
                    value="{{$clientes->documento}}">
                </div>

                <!-- ESTADO -->
                <div class="col-12 col-sm-6">
                  <label for="estado" class="form-label">Estado</label>
                  <select name="estado" id="estado" class="form-select" required>
                    <option value="" disabled>Seleccione un estado</option>
                    <option value="activo" {{ $clientes->estado == 'activo' ? 'selected' : '' }}>Activo</option>
                    <option value="inactivo" {{ $clientes->estado == 'inactivo' ? 'selected' : '' }}>Inactivo</option>
                  </select>
                </div>

              </div>

              <!-- BOTONES -->
              <div class="d-flex flex-column flex-sm-row gap-2 mt-4 justify-content-end">
                <a href="{{ route('clientes') }}" class="btn btn-secondary">
                  Cancelar
                </a>

                <button type="submit" class="btn btn-success">
                  Guardar usuario
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