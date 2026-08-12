<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'TravelManager Pro')</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- filepond-->
    <!-- CSS -->
    <link href="https://unpkg.com/filepond/dist/filepond.min.css" rel="stylesheet">

    <!-- JS -->
    <script src="https://unpkg.com/filepond/dist/filepond.min.js"></script>
    <!-- sweet alert para mensajes  -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Plugin preview -->
    <link href="https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.css" rel="stylesheet">

    <link
        rel="stylesheet"
        href="{{ asset('css/panel-administrativo.css') }}"
    >
</head>
<body>

<div id="overlay" class="overlay" onclick="closeSidebar()"></div>
<div id="sidebar" class="sidebar">

    <div class="logo-container">

        <div class="marca-panel">
            <span class="marca-panel-icono">
                <img
                    src="{{ asset('assets/logo/logo_passion.png') }}"
                    alt="Logo de Passion Travel"
                >
            </span>

            <span class="marca-panel-textos">
                <strong>Passion Travel</strong>
                <span>Panel administrativo</span>
            </span>
        </div>

        <button
            type="button"
            class="boton-contraer-menu d-none d-md-inline-flex"
            id="botonContraerMenu"
            onclick="toggleSidebar()"
            aria-label="Contraer menú"
        >
            <i class="bi bi-chevron-left"></i>
        </button>

        <button
            type="button"
            class="boton-contraer-menu d-md-none"
            onclick="closeSidebar()"
            aria-label="Cerrar menú"
        >
            <i class="bi bi-x-lg"></i>
        </button>

    </div>

    <ul class="nav-list">

        {{-- Principal --}}
        <li class="grupo-menu">

            <span class="titulo-grupo-menu">
                Principal
            </span>

            <a
                href="{{ route('main') }}"
                class="nav-link {{
                    request()->routeIs('main')
                        ? 'active'
                        : ''
                }}"
                title="Inicio"
            >
                <i class="bi bi-grid-1x2-fill"></i>
                <span>Inicio</span>
            </a>

            <a
                href="{{ url('/') }}"
                target="_blank"
                rel="noopener noreferrer"
                class="nav-link enlace-sitio-publico"
                title="Ver página pública"
            >
                <i class="bi bi-box-arrow-up-right"></i>
                <span>Ver página pública</span>
            </a>

        </li>

        {{-- Gestión de viajes --}}
        <li class="grupo-menu">

            <span class="titulo-grupo-menu">
                Gestión de viajes
            </span>

            <a
                href="{{ route('reservas') }}"
                class="nav-link {{
                    request()->routeIs('reservas*') ||
                    request()->routeIs('reservas_individual*') ||
                    request()->routeIs('reservas_grupal*')
                        ? 'active'
                        : ''
                }}"
                title="Reservas"
            >
                <i class="bi bi-calendar-check-fill"></i>
                <span>Reservas</span>
            </a>

            <a
                href="{{ route('operaciones.index') }}"
                class="nav-link {{
                    request()->routeIs('operaciones.*')
                        ? 'active'
                        : ''
                }}"
                title="Preparación de viajes"
            >
                <i class="bi bi-luggage-fill"></i>
                <span>Preparación de viajes</span>
            </a>

            <a
                href="{{ route('prereservas.index') }}"
                class="nav-link {{
                    request()->routeIs('prereservas*')
                        ? 'active'
                        : ''
                }}"
                title="Prerreservas"
            >
                <i class="bi bi-file-earmark-check-fill"></i>
                <span>Prerreservas</span>
            </a>

            <a
                href="{{ route('pagos') }}"
                class="nav-link {{
                    request()->routeIs('pagos*')
                        ? 'active'
                        : ''
                }}"
                title="Pagos"
            >
                <i class="bi bi-wallet2"></i>
                <span>Pagos</span>
            </a>

            <a
                href="{{ route('devoluciones.index') }}"
                class="nav-link {{
                    request()->routeIs('devoluciones.*')
                        ? 'active'
                        : ''
                }}"
                title="Devoluciones"
            >
                <i class="bi bi-arrow-counterclockwise"></i>
                <span>Devoluciones</span>
            </a>

            <a
                href="{{ route('clientes') }}"
                class="nav-link {{
                    request()->routeIs('clientes*')
                        ? 'active'
                        : ''
                }}"
                title="Clientes"
            >
                <i class="bi bi-people-fill"></i>
                <span>Clientes</span>
            </a>

        </li>

        {{-- Contenido público --}}
        <li class="grupo-menu">

            <span class="titulo-grupo-menu">
                Contenido público
            </span>

            <a
                href="{{ route('destinos') }}"
                class="nav-link {{
                    request()->routeIs('destinos*')
                        ? 'active'
                        : ''
                }}"
                title="Paquetes turísticos"
            >
                <i class="bi bi-map-fill"></i>
                <span>Paquetes turísticos</span>
            </a>

            <a
                href="{{ route('testimonios.index') }}"
                class="nav-link {{
                    request()->routeIs('testimonios.*')
                        ? 'active'
                        : ''
                }}"
                title="Testimonios"
            >
                <i class="bi bi-chat-quote-fill"></i>
                <span>Testimonios</span>
            </a>

        </li>

        {{-- Administración --}}
        <li class="grupo-menu">

            <span class="titulo-grupo-menu">
                Administración
            </span>

            <a
                href="{{ route('reportes.ingresos') }}"
                class="nav-link {{
                    request()->routeIs('reportes.ingresos*')
                        ? 'active'
                        : ''
                }}"
                title="Reportes"
            >
                <i class="bi bi-bar-chart-fill"></i>
                <span>Reportes</span>
            </a>

            @auth
                @if(auth()->user()->isAdmin())
                    <a
                        href="{{ route('usuarios') }}"
                        class="nav-link {{
                            request()->routeIs('usuarios*')
                                ? 'active'
                                : ''
                        }}"
                        title="Usuarios"
                    >
                        <i class="bi bi-person-gear"></i>
                        <span>Usuarios</span>
                    </a>
                @endif
            @endauth

        </li>

    </ul>

    <div class="user-profile">

        @auth
            <div class="perfil-panel">

                <span class="avatar-panel">
                    {{ mb_strtoupper(
                        mb_substr(
                            auth()->user()->nombres,
                            0,
                            1
                        )
                    ) }}
                </span>

                <div class="informacion-usuario-panel">
                    <strong>
                        {{ auth()->user()->nombres }}
                        {{ auth()->user()->apellidos }}
                    </strong>

                    <span>{{ auth()->user()->email }}</span>
                </div>

            </div>

            <form
                action="{{ route('logout') }}"
                method="POST"
                id="formularioCerrarSesion"
            >
                @csrf

                <button
                    type="submit"
                    class="boton-cerrar-sesion"
                >
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Cerrar sesión</span>
                </button>

            </form>
        @endauth

    </div>

</div>

<div id="content" class="content">
    
    <header class="topbar">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-light d-md-none" onclick="openSidebar()">
                <i class="bi bi-list"></i>
            </button>
            <div>
                <h5 class="m-0 fw-bold text-dark">@yield('header','Resumen General')</h5>
                <p class="m-0 text-muted small">Bienvenido de nuevo al panel</p>
            </div>
        </div>

        <div class="d-flex align-items-center gap-3">
            <div class="position-relative cursor-pointer me-3">
                <a href="#">
                    <i class="bi bi-bell fs-5"></i>
                </a>
                <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"></span>
            </div>
            <a class="btn btn-primary rounded-pill px-4 btn-sm fw-bold" href="{{route('reservas_individual.create')}}">
                <i class="bi bi-plus-lg me-2" ></i>Nueva Reserva
            </a>
        </div>
    </header>

    <main class="p-4 p-md-5">
        @section('content')
        <div class="row g-4">
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card-stats d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-medium">Reservas Hoy</span>
                        <h3 class="fw-bold m-0 mt-1">24</h3>
                        <span class="text-success small fw-bold"><i class="bi bi-arrow-up-short"></i> +12%</span>
                    </div>
                    <div class="stat-icon bg-soft-blue"><i class="bi bi-calendar-check"></i></div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card-stats d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-medium">Clientes Activos</span>
                        <h3 class="fw-bold m-0 mt-1">1,204</h3>
                        <span class="text-success small fw-bold"><i class="bi bi-arrow-up-short"></i> +5%</span>
                    </div>
                    <div class="stat-icon bg-soft-green"><i class="bi bi-people"></i></div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card-stats d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-medium">Ingresos Mes</span>
                        <h3 class="fw-bold m-0 mt-1">$12,450</h3>
                        <span class="text-muted small">Meta: $15k</span>
                    </div>
                    <div class="stat-icon bg-soft-purple"><i class="bi bi-currency-dollar"></i></div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card-stats d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-medium">Alertas n8n</span>
                        <h3 class="fw-bold m-0 mt-1">3</h3>
                        <span class="text-danger small fw-bold">Requiere atención</span>
                    </div>
                    <div class="stat-icon bg-soft-orange"><i class="bi bi-robot"></i></div>
                </div>
            </div>
        </div>
        @show
    </main>
</div>

<script src="{{ asset('js/panel-administrativo.js') }}"></script>

@yield('scripts')

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Alerta de ÉXITO (Para cuando guardas, editas o eliminas)
        @if(session('success'))
            Swal.fire({
                icon: 'success',
                title: '¡Operación Exitosa!',
                text: "{{ session('success') }}",
                confirmButtonColor: '#0d6efd', /* Color azul que combina con tu botón */
                timer: 3000, /* Se cierra solo en 3 segundos si el usuario no le da click */
                timerProgressBar: true
            });
        @endif

        // Alerta de ERROR (Por si salta alguna excepción en el try-catch)
        @if(session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Ha ocurrido un problema',
                text: "{{ session('error') }}",
                confirmButtonColor: '#0d6efd'
            });
        @endif
    });
</script>
</body>
</html>
