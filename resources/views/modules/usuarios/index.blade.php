@extends('layouts.main')

@section('titulo', $titulo)

@section('content')

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">

<!-- jQuery (requerido para DataTables) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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

            <div style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
            <table class="table datatable table-striped table-hover" style="min-width: 900px;">
              <thead class="table-light">
                <tr>
                  <th class="text-center" style="white-space: nowrap;">#</th>
                  <th class="text-start" style="white-space: nowrap;">Nombres</th>
                  <th class="text-start" style="white-space: nowrap;">Apellidos</th>
                  <th class="text-start" style="white-space: nowrap;">Email</th>
                  <th class="text-start" style="white-space: nowrap;">Telefono</th>
                  <th class="text-start" style="white-space: nowrap;">Documento</th>
                  <th class="text-start" style="white-space: nowrap;">Rol</th>
                  <th class="text-center" style="white-space: nowrap;">Acciones </th>
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
                        <form action="{{ route('usuarios.destroy', $usuario->id) }}" method="POST" class="d-inline delete-usuario-form">
                          @csrf
                          @method('DELETE')
                          <button type="button" class="btn btn-outline-danger btn-sm btn-delete-usuario">
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
    </div>
  </section>
</main>
  <script>
    document.addEventListener('DOMContentLoaded', function(){
      // Flash messages
      @if(session('success'))
        Swal.fire({
          icon: 'success',
          title: 'Éxito',
          text: '{{ session('success') }}',
          timer: 2500,
          showConfirmButton: false
        });
      @endif

      @if(session('error'))
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: '{{ session('error') }}'
        });
      @endif

      // Delete confirmation (delegated handler for dynamic tables)
      document.addEventListener('click', function(e){
        const btn = e.target.closest('.btn-delete-usuario');
        if (!btn) return;
        e.preventDefault();
        const form = btn.closest('form');
        Swal.fire({
          title: '¿Estás seguro?',
          text: 'Esta acción eliminará el usuario permanentemente.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          confirmButtonText: 'Sí, eliminar',
          cancelButtonText: 'Cancelar'
        }).then((result) => {
          if (result.isConfirmed) {
            form.submit();
          }
        });
      });
    });
  </script>

  <!-- DataTables JS -->
  <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
  <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
  
  <script>
    document.addEventListener('DOMContentLoaded', function(){
      // Inicializar DataTables
      if ($.fn.DataTable.isDataTable('.table.datatable')) {
        $('.table.datatable').DataTable().destroy();
      }
      
      const table = $('.table.datatable').DataTable({
        language: {
          url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
        },
        responsive: true,
        autoWidth: false,
        columnDefs: [
          { targets: 0, searchable: true }
        ],
        order: [[0, 'asc']],
        pageLength: 10,
        dom: '<"d-flex justify-content-between align-items-center mb-3"lf>rtip'
      });
    });
  </script>
@endsection