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
    <link href="{{ asset('css/cliente-modulo.css') }}" rel="stylesheet">

    <!-- JS -->
    <script src="https://unpkg.com/filepond/dist/filepond.min.js"></script>
    <!-- sweet alert para mensajes  -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Plugin preview -->
    <link href="https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.css" rel="stylesheet">

    <style>

        :root {
            --sidebar-width: 280px;
            --sidebar-collapsed-width: 85px;
            --primary-color: #2553eb;
            --bg-body: #f8fafc;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        html, body {
            height: 100%;
            min-height: 100%;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-body);
            color: #1e293b;
            overflow-x: hidden;
            margin: 0;
        }

        /* SIDEBAR PRO */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            inset: 0 auto auto 0;
            left: 0;
            overflow: hidden;
            position: fixed;
            background: #ffffff;
            border-right: 1px solid #e2e8f0;
            transition: var(--transition);
            z-index: 1050;
            display: flex;
            flex-direction: column;
            box-sizing: border-box;
        }

        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }

        .sidebar .logo-container {
            flex-shrink: 0;
            padding: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo-text {
            font-weight: 700;
            font-size: 1.25rem;
            color: var(--primary-color);
            letter-spacing: -0.5px;
            display: block;
        }

        .sidebar.collapsed .logo-text,
        .sidebar.collapsed .nav-link span,
        .sidebar.collapsed .user-info {
            display: none;
        }

        /* NAVEGACIÓN */
        .nav-list {
            flex: 1 1 auto;
            min-height: 0;
            padding: 0 16px;
            margin: 0;
            overflow-y: auto;
            overflow-x: hidden;
            list-style: none;
            box-sizing: border-box;
        }

        .sidebar .user-profile {
            flex-shrink: 0;
            padding: 16px 20px;
            border-top: 1px solid #e2e8f0;
            background: #fcfcfc;
            margin-top: auto;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: #64748b;
            text-decoration: none;
            border-radius: 12px;
            margin-bottom: 4px;
            font-weight: 500;
            transition: var(--transition);
        }

        .nav-link i {
            font-size: 1.25rem;
        }

        .bi {
            font-size: 1rem;
            line-height: 1;
            display: inline-block;
            vertical-align: middle;
            width: auto;
            height: auto;
        }

        .sidebar .logo-container button {
            width: 40px;
            height: 40px;
            min-width: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            border-radius: 12px;
            overflow: hidden;
        }

        .sidebar .logo-container button i,
        .sidebar .logo-container button svg {
            font-size: 1rem;
            width: 1rem;
            height: 1rem;
            line-height: 1;
            display: inline-block;
            vertical-align: middle;
        }

        .pagination svg {
            width: auto !important;
            height: auto !important;
            max-width: none !important;
        }

        .pagination .page-link,
        .pagination span {
            min-width: 2.5rem;
        }

        .nav-link:hover {
            background: #f1f5f9;
            color: var(--primary-color);
        }

        .nav-link.active {
            background: #eff6ff;
            color: var(--primary-color);
        }

        /* CONTENT AREA */
        .content {
            margin-left: var(--sidebar-width);
            transition: var(--transition);
            min-height: 100vh;
        }

        .content.collapsed {
            margin-left: var(--sidebar-collapsed-width);
        }

        /* TOPBAR */
        .topbar {
            height: 72px;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
            sticky: top;
            z-index: 1000;
        }

        /* CARDS PROFESIONALES */
        .card-stats {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 24px;
            transition: var(--transition);
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }

        .card-stats:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        /* Colores de Marca */
        .bg-soft-blue { background: #eff6ff; color: #2563eb; }
        .bg-soft-green { background: #f0fdf4; color: #16a34a; }
        .bg-soft-purple { background: #faf5ff; color: #9333ea; }
        .bg-soft-orange { background: #fff7ed; color: #ea580c; }

        /* User Section */
        .user-profile {
            flex-shrink: 0;
            margin-top:auto; 
            padding: 16px 20px;
            border-top: 1px solid #e2e8f0;
            background: #fcfcfc;
        }
        /*edición rapida en modal de detalle reserva grupal*/
        .editable-cell {
        position: relative;
        cursor: pointer;
        padding: 5px !important;
        }
        .editable-cell:hover {
            background-color: rgba(59, 130, 246, 0.05); /* Un azul muy tenue */
        }
        .edit-icon {
            visibility: hidden;
            color: #3b82f6;
            margin-left: 8px;
        }
        .editable-cell:hover .edit-icon {
            visibility: visible;
        }
        .input-edit {
            width: 100%;
            border: 1px solid #3b82f6 !important;
            font-weight: bold;
        }
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar { left: -100%; }
            .sidebar.active { left: 0; width: 280px !important; }
            .content { margin-left: 0 !important; }
            .logo-text, .nav-link span { display: block !important; }
        }
        
    </style>
</head>
<body>

<div id="overlay" class="overlay" onclick="closeSidebar()"></div>

<div id="sidebar" class="sidebar">
    <div class="logo-container">
        <span class="logo-text">Passion Travel</span>
        <button class="btn btn-sm btn-light d-none d-md-block" onclick="toggleSidebar()" style="width:40px;height:40px;min-width:40px;padding:0;display:inline-flex;align-items:center;justify-content:center;overflow:hidden;">
            <i class="bi bi-chevron-left" style="font-size:1rem;line-height:1;display:inline-block;vertical-align:middle;"></i>
        </button>
    </div>

    <ul class="nav-list flex-column mb-auto">
        <!-- <li><a href="#" class="nav-link "><i class="bi bi-grid-1x2-fill"></i><span>Dashboard</span></a></li>-->
        <li><a href="{{route('reportes.ingresos')}}" class="nav-link {{ request()->routeIs('reportes.ingresos*') ? 'active' : '' }}"><i class="bi bi-clipboard2"></i><span>Reportes</span></a></li>
        <li><a href="{{route('usuarios')}}" class="nav-link {{ request()->routeIs('usuarios*') ? 'active' : '' }}"><i class="bi bi-people-fill"></i><span>Usuarios</span></a></li>
        <li><a href="{{route('clientes')}}" class="nav-link {{ request()->routeIs('clientes*') ? 'active' : '' }}"><i class="bi bi-person-circle"></i><span>Clientes</span></a></li>
        <li><a href="{{route('destinos')}}" class="nav-link {{ request()->routeIs('destinos*') ? 'active' : '' }}"><i class="bi bi-airplane"></i><span>Destinos</span></a></li>
        <!--<li><a href="{{route('grupos')}}" class="nav-link {{ request()->routeIs('grupos*') ? 'active' : '' }}"><i class="bi bi-geo-alt"></i><span>Grupos</span></a></li>-->
        <li><a href="{{route('reservas')}}" class="nav-link {{ request()->routeIs('reservas*') ? 'active' : '' }}"><i class="bi bi-calendar-fill"></i><span>Reservas</span></a></li>
        <li><a href="{{route('prereservas.index')}}" class="nav-link {{ request()->routeIs('prereservas*') ? 'active' : '' }}"><i class="bi bi-file-earmark-text-fill"></i><span>Pre-reservas</span></a></li>
        <li><a href="{{route('pagos')}}" class="nav-link {{ request()->routeIs('pagos*') ? 'active' : '' }}"><i class="bi bi-wallet2"></i><span>Pagos</span></a></li>
        <li><a href="#" class="nav-link"><i class="bi bi-cpu"></i><span>Automatización</span></a></li>
        <li><a href="#" class="nav-link"><i class="bi bi-chat-dots"></i><span>Mensajería</span></a></li>
    </ul>

    <div class="user-profile">
        @auth
        <div class="d-flex align-items-center gap-3 mb-3 user-info">
            <img src="https://ui-avatars.com/api/?name={{ auth()->user()->nombres }}&background=2563eb&color=fff" class="rounded-circle" width="38">
            <div class="overflow-hidden">
                <p class="m-0 fw-bold text-truncate" style="font-size: 0.9rem;">{{ auth()->user()->nombres }} {{ auth()->user()->apellidos }}</p>
                <small class="text-muted">{{ auth()->user()->email }}</small>
            </div>
        </div>
        <form action="{{ route('logout') }}" method="post" class="d-grid">
            @csrf
            <button type="submit" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-box-arrow-right me-1"></i>Cerrar Sesión
            </button>
        </form>
        @else
        <div class="text-center">
            <a href="{{ route('login') }}" class="btn btn-primary btn-sm w-100 mb-2">
                <i class="bi bi-box-arrow-in-right me-1"></i>Iniciar Sesión
            </a>
            <a href="{{ route('register') }}" class="btn btn-outline-primary btn-sm w-100">
                <i class="bi bi-person-plus me-1"></i>Registrarse
            </a>
        </div>
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
                <i class="bi bi-bell fs-5"></i>
                <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"></span>
            </div>
            <button class="btn btn-primary rounded-pill px-4 btn-sm fw-bold">
                <i class="bi bi-plus-lg me-2"></i>Nueva Reserva
            </button>
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

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const content = document.getElementById('content');
        const icon = document.querySelector('.sidebar .bi-chevron-left, .sidebar .bi-chevron-right');
        
        sidebar.classList.toggle('collapsed');
        content.classList.toggle('collapsed');

        // Cambiar icono
        if(sidebar.classList.contains('collapsed')) {
            icon.classList.replace('bi-chevron-left', 'bi-chevron-right');
        } else {
            icon.classList.replace('bi-chevron-right', 'bi-chevron-left');
        }
    }

    function openSidebar() {
        document.getElementById('sidebar').classList.add('active');
        document.getElementById('overlay').classList.add('active');
    }

    function closeSidebar() {
        document.getElementById('sidebar').classList.remove('active');
        document.getElementById('overlay').classList.remove('active');
    }
</script>
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