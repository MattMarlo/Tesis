@extends('layouts.main')

@section('titulo', 'Usuarios')

@section('content')
<link rel="stylesheet" href="{{ asset('css/usuarios-listado.css') }}">

<main id="main" class="main usuarios-pagina">

    <div class="usuarios-encabezado">
        <div>
            <span class="usuarios-seccion">Administración</span>
            <h1>Usuarios del sistema</h1>
            <p>
                Administra las cuentas que pueden ingresar al sistema.
            </p>
        </div>

        <a href="{{ route('usuarios.create') }}" class="btn-nuevo-usuario">
            <i class="bi bi-person-plus"></i>
            Agregar usuario
        </a>
    </div>

    <section class="usuarios-resumen">
        <div class="resumen-item">
            <span>Total de usuarios</span>
            <strong>{{ $usuarios->count() }}</strong>
        </div>

        <div class="resumen-item">
            <span>Cuentas activas</span>
            <strong>
                {{ $usuarios->where('estado', 'activo')->count() }}
            </strong>
        </div>

        <div class="resumen-item">
            <span>Administradores</span>
            <strong>
                {{ $usuarios->where('rol', 'admin')->count() }}
            </strong>
        </div>
    </section>

    <section class="usuarios-contenedor">

        <div class="usuarios-herramientas">
            <div class="campo-busqueda">
                <i class="bi bi-search"></i>

                <input
                    id="buscarUsuario"
                    type="search"
                    placeholder="Buscar por nombre, correo o documento"
                    autocomplete="off"
                >
            </div>

            <div class="filtro-estado">
                <label for="filtrarEstado">Estado:</label>

                <select id="filtrarEstado">
                    <option value="">Todos</option>
                    <option value="activo">Activos</option>
                    <option value="inactivo">Inactivos</option>
                </select>
            </div>
        </div>

        <div class="tabla-responsive">
            <table id="tablaUsuarios" class="tabla-usuarios">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Contacto</th>
                        <th>Documento</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th class="columna-acciones">Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($usuarios as $usuario)
                        <tr data-estado="{{ $usuario->estado ?? 'inactivo' }}">

                            <td>
                                <div class="usuario-datos">
                                    <span class="usuario-inicial">
                                        {{ strtoupper(substr($usuario->nombres, 0, 1)) }}
                                    </span>

                                    <div>
                                        <strong>
                                            {{ $usuario->nombres }}
                                            {{ $usuario->apellidos }}
                                        </strong>

                                        <small>
                                            Usuario #{{ $usuario->id }}
                                        </small>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <div class="contacto-datos">
                                    <span>{{ $usuario->email }}</span>

                                    <small>
                                        {{ $usuario->telefono ?: 'Sin teléfono registrado' }}
                                    </small>
                                </div>
                            </td>

                            <td>
                                {{ $usuario->documento }}
                            </td>

                            <td>
                                @if ($usuario->rol === 'admin')
                                    <span class="etiqueta-rol administrador">
                                        Administrador
                                    </span>
                                @else
                                    <span class="etiqueta-rol equipo">
                                        Equipo de trabajo
                                    </span>
                                @endif
                            </td>

                            <td>
                                @if ($usuario->estado === 'activo')
                                    <span class="etiqueta-estado activo">
                                        <span></span>
                                        Activo
                                    </span>
                                @else
                                    <span class="etiqueta-estado inactivo">
                                        <span></span>
                                        Inactivo
                                    </span>
                                @endif
                            </td>

                            <td>
                                <div class="acciones-usuario">
                                    <a
                                        href="{{ route('usuarios.edit', $usuario->id) }}"
                                        class="accion-editar"
                                        title="Editar usuario"
                                        aria-label="Editar usuario"
                                    >
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    @if (auth()->id() !== $usuario->id)
                                        <form
                                            action="{{ route('usuarios.destroy', $usuario->id) }}"
                                            method="POST"
                                            class="formulario-eliminar-usuario"
                                            data-nombre="{{ $usuario->nombres }} {{ $usuario->apellidos }}"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="button"
                                                class="accion-eliminar"
                                                title="Eliminar usuario"
                                                aria-label="Eliminar usuario"
                                            >
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </form>
                                    @else
                                        <span
                                            class="usuario-actual"
                                            title="Esta es tu cuenta"
                                        >
                                            Tu cuenta
                                        </span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="fila-sin-usuarios">
                            <td colspan="6">
                                <div class="sin-usuarios">
                                    <i class="bi bi-people"></i>
                                    <strong>No hay usuarios registrados</strong>
                                    <span>
                                        Agrega el primer usuario para comenzar.
                                    </span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div id="sinResultados" class="sin-resultados">
            No se encontraron usuarios con los filtros seleccionados.
        </div>
    </section>
</main>

<script>
    window.usuariosMensajes = {
        exito: @json(session('success')),
        error: @json(session('error'))
    };
</script>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="{{ asset('js/usuarios-listado.js') }}"></script>
@endsection