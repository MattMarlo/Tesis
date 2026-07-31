<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title') | Passion Travel</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap"
          rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
          rel="stylesheet">

    <style>
        :root {
            --azul-principal: #093D77;
            --azul-secundario: #3A7CA5;
            --blanco: #F8F9FA;
            --oscuro: #2B2B2B;
            --dorado: #DAA520;
            --gris: #6B7280;
            --borde: #DCE3EA;
            --error: #B42318;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            min-height: 100%;
            margin: 0;
        }

        body {
            min-height: 100vh;
            background: var(--blanco);
            color: var(--oscuro);
            font-family: "Manrope", sans-serif;
        }

        button,
        input {
            font: inherit;
        }

        .auth-page {
            min-height: 100vh;
            display: grid;
            grid-template-columns: minmax(320px, 44%) 1fr;
        }

        /* Sección izquierda */

        .auth-brand {
            min-height: 100vh;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            padding: clamp(2rem, 5vw, 4.5rem);
            color: white;
            background:
                radial-gradient(
                    circle at 15% 15%,
                    rgba(218, 165, 32, 0.25),
                    transparent 25rem
                ),
                linear-gradient(
                    145deg,
                    #093D77 0%,
                    #0B4B89 55%,
                    #3A7CA5 100%
                );
        }

        .auth-brand::after {
            content: "";
            position: absolute;
            right: -9rem;
            bottom: -12rem;
            width: 31rem;
            height: 31rem;
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 50%;
            box-shadow:
                0 0 0 4rem rgba(255, 255, 255, 0.035),
                0 0 0 8rem rgba(255, 255, 255, 0.025);
        }

        .brand-mark,
        .brand-message,
        .brand-footer {
            position: relative;
            z-index: 1;
        }

        .brand-mark {
            width: fit-content;
            display: inline-flex;
            align-items: center;
            gap: 0.85rem;
            color: white;
            text-decoration: none;
        }

        .brand-icon {
            width: 3rem;
            height: 3rem;
            display: grid;
            place-items: center;
            border: 1px solid rgba(255, 255, 255, 0.35);
            border-radius: 0.9rem;
            background: rgba(255, 255, 255, 0.12);
            color: var(--dorado);
            font-size: 1.45rem;
        }

        .brand-name {
            display: block;
            font-size: 1.12rem;
            font-weight: 800;
        }

        .brand-caption {
            display: block;
            margin-top: 0.12rem;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.76rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .brand-message {
            max-width: 32rem;
            margin: auto 0;
            padding: 4rem 0;
        }

        .brand-eyebrow {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin: 0 0 1.1rem;
            color: #F7D878;
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .brand-eyebrow::before {
            content: "";
            width: 2.5rem;
            height: 2px;
            background: var(--dorado);
        }

        .brand-message h1 {
            max-width: 30rem;
            margin: 0;
            font-size: clamp(2rem, 4vw, 3.55rem);
            font-weight: 800;
            letter-spacing: -0.045em;
            line-height: 1.06;
        }

        .brand-message p {
            max-width: 27rem;
            margin: 1.35rem 0 0;
            color: rgba(255, 255, 255, 0.78);
            font-size: 1rem;
            line-height: 1.75;
        }

        .brand-footer {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            color: rgba(255, 255, 255, 0.65);
            font-size: 0.78rem;
        }

        .brand-footer i {
            color: var(--dorado);
        }

        /* Sección del formulario */

        .auth-content {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: clamp(1.5rem, 6vw, 5rem);
        }

        .auth-card {
            width: min(100%, 29rem);
        }

        .mobile-brand {
            display: none;
        }

        .form-header {
            margin-bottom: 2rem;
        }

        .form-header h2 {
            margin: 0;
            color: var(--azul-principal);
            font-size: clamp(1.75rem, 3vw, 2.25rem);
            font-weight: 800;
            letter-spacing: -0.035em;
        }

        .form-header p {
            margin: 0.75rem 0 0;
            color: var(--gris);
            font-size: 0.94rem;
            line-height: 1.65;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.48rem;
            color: var(--oscuro);
            font-size: 0.84rem;
            font-weight: 700;
        }

        .input-shell {
            position: relative;
        }

        .input-shell > i {
            position: absolute;
            top: 50%;
            left: 1rem;
            color: var(--azul-secundario);
            font-size: 1.05rem;
            transform: translateY(-50%);
            pointer-events: none;
        }

        .form-control {
            width: 100%;
            height: 3.35rem;
            padding: 0.75rem 3rem 0.75rem 2.85rem;
            border: 1px solid var(--borde);
            border-radius: 0.8rem;
            outline: none;
            background: white;
            color: var(--oscuro);
            font-size: 0.92rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-control::placeholder {
            color: #9AA5B1;
        }

        .form-control:focus {
            border-color: var(--azul-secundario);
            box-shadow: 0 0 0 0.22rem rgba(58, 124, 165, 0.14);
        }

        .form-control.input-error {
            border-color: var(--error);
            box-shadow: 0 0 0 0.2rem rgba(180, 35, 24, 0.08);
        }

        .field-error {
            display: none;
            margin-top: 0.42rem;
            color: var(--error);
            font-size: 0.78rem;
            font-weight: 600;
        }

        .password-toggle {
            position: absolute;
            top: 50%;
            right: 0.85rem;
            width: 2rem;
            height: 2rem;
            display: grid;
            place-items: center;
            border: 0;
            border-radius: 0.5rem;
            background: transparent;
            color: #65717E;
            cursor: pointer;
            transform: translateY(-50%);
        }

        .password-toggle:hover {
            background: #EEF3F6;
            color: var(--azul-principal);
        }

        .form-options {
            display: flex;
            justify-content: flex-end;
            margin: -0.15rem 0 1.45rem;
        }

        .text-link {
            color: var(--azul-principal);
            font-size: 0.83rem;
            font-weight: 700;
            text-decoration: none;
        }

        .text-link:hover {
            color: var(--azul-secundario);
            text-decoration: underline;
        }

        .btn-primary {
            width: 100%;
            min-height: 3.35rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.65rem;
            border: 0;
            border-radius: 0.8rem;
            background: var(--azul-principal);
            color: white;
            font-size: 0.92rem;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 0.65rem 1.5rem rgba(9, 61, 119, 0.18);
            transition: 0.2s;
        }

        .btn-primary:hover {
            background: #0B4B89;
            transform: translateY(-1px);
        }

        .btn-primary:disabled {
            cursor: wait;
            opacity: 0.75;
            transform: none;
        }

        .back-link {
            display: flex;
            justify-content: center;
            margin-top: 1.4rem;
        }

        .security-note {
            display: flex;
            gap: 0.8rem;
            margin-top: 1.5rem;
            padding: 0.95rem 1rem;
            border-left: 3px solid var(--dorado);
            border-radius: 0 0.65rem 0.65rem 0;
            background: #FFF9E8;
            color: #665720;
            font-size: 0.78rem;
            line-height: 1.55;
        }

        .security-note i {
            color: #9C7410;
            font-size: 1rem;
        }

        .swal2-popup {
            border-radius: 1rem !important;
            font-family: "Manrope", sans-serif !important;
        }

        @media (max-width: 860px) {
            .auth-page {
                display: block;
            }

            .auth-brand {
                display: none;
            }

            .auth-content {
                min-height: 100vh;
                padding: 2rem 1.25rem;
            }

            .mobile-brand {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                width: fit-content;
                margin-bottom: 2.5rem;
                color: var(--azul-principal);
                text-decoration: none;
            }

            .mobile-brand .brand-icon {
                width: 2.65rem;
                height: 2.65rem;
                border-color: rgba(9, 61, 119, 0.15);
                background: rgba(9, 61, 119, 0.07);
            }

            .mobile-brand .brand-caption {
                color: var(--gris);
            }
        }
    </style>
</head>

<body>
    <main class="auth-page">

        <section class="auth-brand">

            <a class="brand-mark" href="{{ url('/') }}">
                <span class="brand-icon">
                    <i class="bi bi-airplane-fill"></i>
                </span>

                <span>
                    <span class="brand-name">Passion Travel</span>
                    <span class="brand-caption">Gestión de viajes</span>
                </span>
            </a>

            <div class="brand-message">
                <p class="brand-eyebrow">
                    @yield('brand-eyebrow', 'Panel de gestión')
                </p>

                <h1>
                    @yield('brand-title', 'Tus operaciones de viaje, en un solo lugar.')
                </h1>

                <p>
                    @yield(
                        'brand-copy',
                        'Administra reservas, clientes y pagos de manera sencilla y segura.'
                    )
                </p>
            </div>

            <div class="brand-footer">
                <i class="bi bi-shield-check"></i>
                <span>Acceso exclusivo para personal autorizado</span>
            </div>
        </section>

        <section class="auth-content">
            <div class="auth-card">

                <a class="mobile-brand" href="{{ url('/') }}">
                    <span class="brand-icon">
                        <i class="bi bi-airplane-fill"></i>
                    </span>

                    <span>
                        <span class="brand-name">Passion Travel</span>
                        <span class="brand-caption">Gestión de viajes</span>
                    </span>
                </a>

                @yield('content')
            </div>
        </section>
    </main>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(function () {
            $('.password-toggle').on('click', function () {
                const input = $($(this).data('target'));
                const mostrar = input.attr('type') === 'password';

                input.attr('type', mostrar ? 'text' : 'password');

                $(this)
                    .attr(
                        'aria-label',
                        mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña'
                    )
                    .find('i')
                    .toggleClass('bi-eye bi-eye-slash');
            });

            @if (session('status'))
                Swal.fire({
                    icon: 'success',
                    title: 'Revisa tu correo',
                    text: @json(session('status')),
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#093D77'
                });
            @endif

            @if (session('error'))
                Swal.fire({
                    icon: 'error',
                    title: 'No se pudo completar la solicitud',
                    text: @json(session('error')),
                    confirmButtonText: 'Intentar nuevamente',
                    confirmButtonColor: '#093D77'
                });
            @endif

            @if ($errors->any())
                @php
                    $mensajeError = $errors->first();
                    $mensajeMinusculas = mb_strtolower($mensajeError);

                    if (str_contains($mensajeMinusculas, 'inactiva')) {
                        $tituloError = 'Cuenta inactiva';
                    } elseif (
                        str_contains($mensajeMinusculas, 'contraseña') ||
                        str_contains($mensajeMinusculas, 'credenciales')
                    ) {
                        $tituloError = 'No se pudo iniciar sesión';
                    } elseif (
                        str_contains($mensajeMinusculas, 'correo') &&
                        str_contains($mensajeMinusculas, 'asociada')
                    ) {
                        $tituloError = 'Correo no registrado';
                    } else {
                        $tituloError = 'Revisa la información ingresada';
                    }
                @endphp

                Swal.fire({
                    icon: 'error',
                    title: @json($tituloError),
                    text: @json(implode("\n", $errors->all())),
                    confirmButtonText: 'Corregir',
                    confirmButtonColor: '#093D77'
                });
            @endif
        });

        function activarCargaFormulario(formulario) {
            const boton = $(formulario).find('button[type="submit"]');

            boton.prop('disabled', true);
            boton.find('.button-label').text(
                boton.data('loading-text')
            );

            boton.find('i')
                .removeClass()
                .addClass('bi bi-arrow-repeat');
        }
    </script>

    @yield('scripts')
</body>
</html>