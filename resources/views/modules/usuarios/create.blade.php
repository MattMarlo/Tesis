@extends('layouts.main')

@section('titulo', 'Nuevo Usuario')

@section('content')
<main id="main" class="main">

  <!-- HEADER -->
  <div class="pagetitle mb-4">
    <h1 class="fw-bold">Nuevo Usuario</h1>
    <nav>
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="/">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('usuarios') }}">Usuarios</a></li>
        <li class="breadcrumb-item active">Nuevo</li>
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
              Nuevo Usuario
            </h5>

            <form action="{{ route('usuarios.store') }}" id="form_disciplina" method="post">
              @csrf

              <div class="row g-3">

                <!-- NOMBRES -->
                <div class="col-12 col-sm-6">
                  <label for="nombres" class="form-label">Nombres</label>
                  <input required
                    class="form-control"
                    placeholder="Ingrese los nombre del usuario"
                    type="text" 
                    name="nombres"
                    id="nombres">
                </div>

                <!-- APELLIDOS -->
                <div class="col-12 col-sm-6">
                  <label for="apellidos" class="form-label">Apellidos</label>
                  <input required
                    class="form-control"
                    placeholder="Ingrese los apellidos del usuario"
                    type="text" 
                    name="apellidos"
                    id="apellidos">
                </div>

                <!-- EMAIL -->
                <div class="col-12">
                  <label for="email" class="form-label">Correo</label>
                  <input required
                    class="form-control"
                    placeholder="acostamarlon28@gmail.com"
                    type="email" 
                    name="email"
                    id="email">
                </div>

                <!-- TELEFONO -->
                <div class="col-12 col-sm-6">
                  <label for="telefono" class="form-label">Telefono</label>
                  <input required
                    class="form-control"
                    placeholder="Ingrese el telefono usuario"
                    type="text" 
                    name="telefono"
                    id="telefono">
                </div>

                <!-- DOCUMENTO -->
                <div class="col-12 col-sm-6">
                  <label for="documento" class="form-label">Número Documento</label>
                  <input required
                    class="form-control"
                    placeholder="Ingrese el documento del usuario"
                    type="text" 
                    name="documento"
                    id="documento">
                </div>

                <!-- ROL -->
                <div class="col-12 col-sm-6">
                  <label for="rol" class="form-label">Rol</label>
                  <select name="rol" id="rol" class="form-select" required>
                    <option value="" disabled selected hidden>Seleccione un rol</option>
                    <option value="admin">Administrador</option>
                    <option value="agente">Agente</option>
                  </select>
                </div>

                <!-- PASSWORD -->
                <div class="col-12 col-sm-6">
                  <label for="password" class="form-label">Contraseña</label>
                  <input required
                    class="form-control"
                    placeholder="ingrese la contraseña"
                    type="password" 
                    name="password"
                    id="password">
                </div>

              </div>

              <!-- BOTONES -->
              <div class="d-flex flex-column flex-sm-row gap-2 mt-4 justify-content-end">
                <a href="{{ route('usuarios') }}" class="btn btn-secondary">
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