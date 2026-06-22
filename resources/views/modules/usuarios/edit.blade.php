{{-- Editar usuario --}}
@extends('layouts.main')

@section('titulo', 'Editar Usuario')

@section('content')
<main id="main" class="main">

  <div class="pagetitle mb-4">
    <h1 class="fw-bold">Editar Usuario</h1>
    <nav>
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="/">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('usuarios') }}">Usuarios</a></li>
        <li class="breadcrumb-item active">Editar</li>
      </ol>
    </nav>
  </div>

  <section class="section">
    <div class="row">
      <div class="col-12">

        <div class="card shadow-sm border-0">
          <div class="card-body p-4">

            <h5 class="card-title mb-4 fw-semibold">
              Editar Usuario
            </h5>

            @if ($errors->any())
              <div class="alert alert-danger">
                <ul class="mb-0">
                  @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                  @endforeach
                </ul>
              </div>
            @endif

            <form action="{{ route('usuarios.update', $usuario->id) }}" method="post">
              @csrf
              @method('PUT')

              <div class="row g-3">

                <div class="col-12 col-sm-6">
                  <label for="nombres" class="form-label">Nombres</label>
                  <input required
                    class="form-control"
                    value="{{ old('nombres', $usuario->nombres) }}"
                    type="text" 
                    name="nombres"
                    id="nombres">
                </div>

                <div class="col-12 col-sm-6">
                  <label for="apellidos" class="form-label">Apellidos</label>
                  <input required
                    class="form-control"
                    value="{{ old('apellidos', $usuario->apellidos) }}"
                    type="text" 
                    name="apellidos"
                    id="apellidos">
                </div>

                <div class="col-12">
                  <label for="email" class="form-label">Correo</label>
                  <input required
                    class="form-control"
                    value="{{ old('email', $usuario->email) }}"
                    type="email" 
                    name="email"
                    id="email">
                </div>

                <div class="col-12 col-sm-6">
                  <label for="telefono" class="form-label">Telefono</label>
                  <input required
                    class="form-control"
                    value="{{ old('telefono', $usuario->telefono) }}"
                    type="text" 
                    name="telefono"
                    id="telefono">
                </div>

                <div class="col-12 col-sm-6">
                  <label for="documento" class="form-label">Número Documento</label>
                  <input required
                    class="form-control"
                    value="{{ old('documento', $usuario->documento) }}"
                    type="text" 
                    name="documento"
                    id="documento">
                </div>

                <div class="col-12 col-sm-6">
                  <label for="rol" class="form-label">Rol</label>
                  <select name="rol" id="rol" class="form-select" required>
                    <option value="" disabled>Seleccione un rol</option>
                    <option value="admin" {{ old('rol', $usuario->rol) == 'admin' ? 'selected' : '' }}>Administrador</option>
                    <option value="agente" {{ old('rol', $usuario->rol) == 'agente' ? 'selected' : '' }}>Agente</option>
                  </select>
                </div>

                <div class="col-12 col-sm-6">
                  <label for="password" class="form-label">Contraseña (dejar vacío para mantener actual)</label>
                  <input
                    class="form-control"
                    placeholder="ingrese la contraseña"
                    type="password" 
                    name="password"
                    id="password">
                </div>

              </div>

              <div class="d-flex flex-column flex-sm-row gap-2 mt-4 justify-content-end">
                <a href="{{ route('usuarios') }}" class="btn btn-secondary">
                  Cancelar
                </a>

                <button type="submit" class="btn btn-success">
                  Actualizar usuario
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

<script>
  document.addEventListener('DOMContentLoaded', function(){
    @if(session('success'))
      Swal.fire({icon: 'success', title: 'Éxito', text: '{{ session('success') }}', timer:2500, showConfirmButton:false});
    @endif
    @if(session('error'))
      Swal.fire({icon: 'error', title: 'Error', text: '{{ session('error') }}'});
    @endif
  });
</script>
