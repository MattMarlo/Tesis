@extends('layouts.main')

@section('titulo', $titulo)

@section('content')
<main id="main" class="main">

    <div class="pagetitle d-flex justify-content-between align-items-center">
        <div>
            <h1 class="text-dark fw-bold">Clientes</h1>
            <p class="text-muted small mb-0">Gestiona la base de datos de clientes</p>
        </div>
        <button type="button" class="btn btn-primary px-3 py-2 fw-semibold" style="border-radius: 8px;" data-bs-toggle="modal" data-bs-target="#modalNuevoCliente">
            <i class="bi bi-plus-lg me-1"></i> Nuevo Cliente
        </button>
    </div>

    <section class="section">
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm" style="border-radius: 10px;">
                    <div class="card-body p-2">
                        <div class="row g-2 align-items-center">
                            <div class="col-md-8 col-sm-12">
                                <form action="{{ route('clientes') }}" method="GET" class="d-flex gap-2">
                                    <input 
                                        type="text" 
                                        name="documento" 
                                        class="form-control " 
                                        placeholder="Buscar cliente por cédula"
                                        value="{{ request('documento') }}"
                                    >
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="bi bi-search"></i> Buscar Cliente
                                    </button>
                                    @if(request('documento'))
                                        <a href="{{ route('clientes') }}" class="btn btn-outline-secondary btn-sm ">
                                            <i class="bi bi-x-circle"></i> Limpiar
                                        </a>
                                    @endif
                                </form>
                            </div>
                            
                            <div class="col-md-4 col-sm-12 text-md-end text-start">
                                <div class="dropdown d-inline-block w-100 text-md-end">
                                    <button class="btn btn-light border-0 py-2 px-3 dropdown-toggle text-secondary" type="button" id="filterStatus" data-bs-toggle="dropdown" aria-expanded="false" style="border-radius: 8px; background-color: #f1f3f5;">
                                        <i class="bi bi-funnel me-1"></i> Todos los estados
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="filterStatus">
                                        <li>
                                            <a class="dropdown-item {{ !request('estado') || request('estado') == 'todos' ? 'active' : '' }}" 
                                            href="{{ route('clientes', array_merge(request()->only('documento'), ['estado' => 'todos'])) }}">
                                                <i class="bi bi-circle-fill text-primary me-2" style="font-size: 0.6rem;"></i>Todos los estados
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item {{ request('estado') == 'activo' ? 'active' : '' }}" 
                                            href="{{ route('clientes', array_merge(request()->only('documento'), ['estado' => 'activo'])) }}">
                                                <i class="bi bi-circle-fill text-success me-2" style="font-size: 0.6rem;"></i>Activos
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item {{ request('estado') == 'inactivo' ? 'active' : '' }}" 
                                            href="{{ route('clientes', array_merge(request()->only('documento'), ['estado' => 'inactivo'])) }}">
                                                <i class="bi bi-circle-fill text-danger me-2" style="font-size: 0.6rem;"></i>Inactivos
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            @forelse ($clientes as $cliente)
                <div class="col-12 col-md-6">
                    <div class="card client-card shadow-sm border-0 h-100">
                        
                        <div class="dropdown card-action-menu">
                            <button class="btn btn-three-dots dropdown-toggle no-caret" type="button" id="actionMenu{{ $cliente->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="actionMenu{{ $cliente->id }}" style="border-radius: 8px;">
                                <li>
                                    <a class="dropdown-item py-2 text-warning" href="{{ route('clientes.edit', $cliente->id) }}">
                                        <i class="bi bi-pencil-square me-2"></i> Editar
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider my-1"></li>
                                <li>
                                    <form action="{{ route('clientes.destroy', $cliente->id) }}" method="POST" class="d-inline form-eliminar">
                                      @csrf
                                      @method('DELETE')
                                      <button type="button" class="dropdown-item py-2 text-danger btn-borrar">
                                          <i class="bi bi-trash3-fill me-2"></i> Eliminar
                                      </button>
                                  </form>
                                </li>
                            </ul>
                        </div>

                        <div class="card-body p-4 d-flex flex-column h-100">
                            <div class="d-flex align-items-start mb-3">
                                @php
                                    // Genera iniciales automáticas basándose en el nombre y apellido
                                    $iniciales = strtoupper(substr($cliente->nombres, 0, 1) . substr($cliente->apellidos, 0, 1));
                                @endphp
                                <div class="avatar-circle bg-primary text-white shadow-sm">
                                    {{ $iniciales }}
                                </div>
                                <div>
                                    <h5 class="card-title p-0 m-0 text-dark fw-bold" style="font-size: 1.1rem;">{{ $cliente->nombres }} {{ $cliente->apellidos }}</h5>
                                    <p class="text-muted small mb-2">DNI: {{ $cliente->documento }}</p>
                                    <span class="client-status-pill text-white {{ $cliente->estado == 'activo' ? 'bg-success' : 'bg-danger' }}">
                                        {{ $cliente->estado }}
                                    </span>
                                </div>
                            </div>

                            <div class="mt-2" style="font-size: 0.9rem; color: #5f666c;">
                                <div class="mb-2 d-flex align-items-center">
                                    <i class="bi bi-envelope text-muted me-2"></i>
                                    <span>{{ $cliente->email }}</span>
                                </div>
                                <div class="mb-2 d-flex align-items-center">
                                    <i class="bi bi-telephone text-muted me-2"></i>
                                    <span>{{ $cliente->telefono }}</span>
                                </div>
                                
                                <!-- BOTÓN SUBIÓ AQUÍ (justo después del teléfono) -->
                                @if($cliente->archivo)
                                    <div class="mb-2 d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-file-earmark-text text-muted me-2"></i>
                                            <span class="text-muted">Documento:</span>
                                        </div>
                                        <a href="{{ Storage::url($cliente->archivo) }}" 
                                        target="_blank" 
                                        class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 fw-semibold" 
                                        style="border-color: #0d6efd; background-color: #f0f7ff; transition: all 0.2s; font-size: 0.75rem;"
                                        onmouseover="this.style.backgroundColor='#0d6efd'; this.style.color='white';" 
                                        onmouseout="this.style.backgroundColor='#f0f7ff'; this.style.color='#0d6efd';">
                                            <i class="bi bi-file-earmark-text me-1"></i> Ver documento
                                        </a>
                                    </div>
                                @endif
                                <div class="mb-2 d-flex align-items-center">
                                    <i class="bi bi-geo-alt text-muted me-2"></i>
                                    <span>viajes (X viajes realizados)</span>
                                </div>
                                

                            </div>

                            <div class="text-end text-muted small mt-a pt-2 border-top" style="font-size: 0.8rem; margin-top: auto;">
                                Registrado el {{ $cliente->created_at->format('d/m/Y') }}
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center py-5">
                    <i class="bi bi-people text-muted" style="font-size: 3rem;"></i>
                    <h4 class="text-muted mt-3">No se encontraron clientes registrados.</h4>
                </div>
            @endforelse
        </div>
    </section>
</main>

<div class="modal fade" id="modalNuevoCliente" tabindex="-1" aria-labelledby="modalNuevoClienteLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            
            <div class="modal-header border-0 pb-0 pt-4 px-4 position-relative">
                <div>
                    <h4 class="modal-title fw-bold text-dark" id="modalNuevoClienteLabel" style="font-size: 1.4rem;">Registrar Nuevo Cliente</h4>
                    <p class="text-muted small mb-0">Completa la información del cliente</p>
                </div>
                <button type="button" class="btn-close position-absolute" data-bs-dismiss="modal" aria-label="Close" style="top: 25px; right: 25px;"></button>
            </div>

            <div class="modal-body p-4">
                <form action="{{ route('clientes.store') }}" id="form_nuevo_cliente" method="post" enctype="multipart/form-data">
                    @csrf

                    <div class="row g-3">
                        <div class="col-12 col-sm-6">
                            <label for="nombres" class="form-label text-secondary small fw-semibold">Nombres</label>
                            <input required class="form-control py-2 bg-light border-0" placeholder="María" type="text" name="nombres" id="nombres" style="border-radius: 8px;">
                        </div>

                        <div class="col-12 col-sm-6">
                            <label for="apellidos" class="form-label text-secondary small fw-semibold">Apellidos</label>
                            <input required class="form-control py-2 bg-light border-0" placeholder="González" type="text" name="apellidos" id="apellidos" style="border-radius: 8px;">
                        </div>

                        <div class="col-12">
                            <label for="email" class="form-label text-secondary small fw-semibold">Correo electrónico</label>
                            <input required class="form-control py-2 bg-light border-0" placeholder="maria@gmail.com" type="email" name="email" id="email" style="border-radius: 8px;">
                        </div>

                        <div class="col-12">
                            <label for="telefono" class="form-label text-secondary small fw-semibold">Teléfono</label>
                            <input required class="form-control py-2 bg-light border-0" placeholder="09876543210" type="text" name="telefono" id="telefono" style="border-radius: 8px;">
                        </div>

                        <div class="col-12 col-sm-6">
                            <label for="documento" class="form-label text-secondary small fw-semibold">Cédula</label>
                            <input required class="form-control py-2 bg-light border-0" placeholder="1700000000" type="text" name="documento" id="documento" style="border-radius: 8px;">
                        </div>

            

                        <div class="col-12 col-sm-6">
                            <label for="estado" class="form-label text-secondary small fw-semibold">Estado</label>
                            <select name="estado" id="estado" class="form-select py-2 bg-light border-0" required style="border-radius: 8px;">
                                <option value="" disabled selected hidden>Seleccione un estado</option>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-12 col-sm-12">
                        <label for="archivo" class="form-label text-secondary small fw-semibold">Visa (PDF o Imagen) opcional</label>
                        <input  class="form-control py-2 bg-light border-0"  type="file" name="archivo" id="archivo" style="border-radius: 8px;"
                        accept=".pdf,.jpg,.jpeg,.png" style="border-radius: 8px;">
                    </div>

                    <div class="d-flex gap-2 mt-4 pt-2 justify-content-end">
                        <button type="button" class="btn btn-light px-4 py-2 border text-secondary fw-semibold" data-bs-dismiss="modal" style="border-radius: 8px; background-color: #fff;">
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold" style="border-radius: 8px; background-color: #0d6efd;">
                            Guardar Cliente
                        </button>
                    </div>

                </form>
            </div>

        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const botonesBorrar = document.querySelectorAll('.btn-borrar');
        
        botonesBorrar.forEach(boton => {
            boton.addEventListener('click', function(e) {
                e.preventDefault();
                const formulario = this.closest('.form-eliminar');

                Swal.fire({
                    title: '¿Estás seguro de eliminar este cliente?',
                    text: "Esta acción no se puede deshacer.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        formulario.submit();
                    }
                });
            });
        });
    });
</script>
@endsection