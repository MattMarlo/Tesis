@extends('layouts.auth')

@section('title', 'Iniciar sesión')

@section('brand-eyebrow', 'Bienvenido de vuelta')

@section('brand-title', 'Cada viaje comienza con una buena gestión.')

@section(
    'brand-copy',
    'Consulta tus reservas, registra pagos y realiza el seguimiento de tus clientes desde un espacio organizado.'
)

@section('content')

    <header class="form-header">
        <h2>Iniciar sesión</h2>

        <p>
            Ingresa con el correo electrónico y la contraseña
            asignados a tu cuenta.
        </p>
    </header>

    <form
        id="loginForm"
        class="auth-form"
        action="{{ route('login.process') }}"
        method="POST"
        novalidate
    >
        @csrf

        <div class="form-group">
            <label class="form-label" for="email">
                Correo electrónico
            </label>

            <div class="input-shell">
                <i class="bi bi-envelope"></i>

                <input
                    id="email"
                    class="form-control"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    placeholder="nombre@correo.com"
                    autocomplete="email"
                    required
                    autofocus
                >
            </div>

            <span id="emailError" class="field-error"></span>
        </div>

        <div class="form-group">
            <label class="form-label" for="password">
                Contraseña
            </label>

            <div class="input-shell">
                <i class="bi bi-lock"></i>

                <input
                    id="password"
                    class="form-control"
                    type="password"
                    name="password"
                    placeholder="Ingresa tu contraseña"
                    autocomplete="current-password"
                    required
                >

                <button
                    class="password-toggle"
                    type="button"
                    data-target="#password"
                    aria-label="Mostrar contraseña"
                >
                    <i class="bi bi-eye"></i>
                </button>
            </div>

            <span id="passwordError" class="field-error"></span>
        </div>

        <div class="form-options">
            <a
                class="text-link"
                href="{{ route('password.request') }}"
            >
                ¿Olvidaste tu contraseña?
            </a>
        </div>

        <button
            class="btn-primary"
            type="submit"
            data-loading-text="Iniciando sesión..."
        >
            <span class="button-label">Ingresar al sistema</span>
            <i class="bi bi-arrow-right"></i>
        </button>
    </form>

    <div class="security-note">
        <i class="bi bi-info-circle"></i>

        <span>
            No compartas tus credenciales ni dejes la sesión abierta
            en equipos de uso público.
        </span>
    </div>

@endsection

@section('scripts')

    <script>
        $(function () {
            const formulario = $('#loginForm');
            const correo = $('#email');
            const password = $('#password');

            function mostrarEstadoCampo(campo, mensaje) {
                const contenedorError = $('#' + campo.attr('id') + 'Error');

                campo.toggleClass('input-error', Boolean(mensaje));

                contenedorError
                    .text(mensaje || '')
                    .toggle(Boolean(mensaje));

                return !mensaje;
            }

            function validarCorreo() {
                const valor = $.trim(correo.val());

                if (!valor) {
                    return mostrarEstadoCampo(
                        correo,
                        'Ingresa tu correo electrónico.'
                    );
                }

                const formatoCorreo = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

                if (!formatoCorreo.test(valor)) {
                    return mostrarEstadoCampo(
                        correo,
                        'Escribe un correo electrónico válido.'
                    );
                }

                return mostrarEstadoCampo(correo, '');
            }

            function validarPassword() {
                if (!password.val()) {
                    return mostrarEstadoCampo(
                        password,
                        'Ingresa tu contraseña.'
                    );
                }

                return mostrarEstadoCampo(password, '');
            }

            correo.on('blur input', validarCorreo);
            password.on('blur input', validarPassword);

            formulario.on('submit', function (event) {
                const correoValido = validarCorreo();
                const passwordValido = validarPassword();

                if (!correoValido || !passwordValido) {
                    event.preventDefault();

                    $('.input-error')
                        .first()
                        .trigger('focus');

                    return;
                }

                activarCargaFormulario(this);
            });
        });
    </script>

@endsection