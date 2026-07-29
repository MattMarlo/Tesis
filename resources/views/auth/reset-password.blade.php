@extends('layouts.auth')

@section('title', 'Restablecer contraseña')

@section('brand-eyebrow', 'Último paso')

@section('brand-title', 'Protege nuevamente el acceso a tu cuenta.')

@section(
    'brand-copy',
    'Crea una contraseña nueva que puedas recordar y que no utilices en otros servicios.'
)

@section('content')

    <header class="form-header">
        <h2>Nueva contraseña</h2>

        <p>
            La contraseña debe contener al menos 8 caracteres.
            Escríbela nuevamente para confirmarla.
        </p>
    </header>

    <form
        id="resetForm"
        class="auth-form"
        action="{{ route('password.update') }}"
        method="POST"
        novalidate
    >
        @csrf

        <input
            type="hidden"
            name="token"
            value="{{ $token }}"
        >

        <input
            type="hidden"
            name="email"
            value="{{ $email }}"
        >

        <div class="form-group">
            <label class="form-label" for="password">
                Nueva contraseña
            </label>

            <div class="input-shell">
                <i class="bi bi-lock"></i>

                <input
                    id="password"
                    class="form-control"
                    type="password"
                    name="password"
                    placeholder="Mínimo 8 caracteres"
                    autocomplete="new-password"
                    required
                    autofocus
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

        <div class="form-group">
            <label
                class="form-label"
                for="password_confirmation"
            >
                Confirmar contraseña
            </label>

            <div class="input-shell">
                <i class="bi bi-shield-lock"></i>

                <input
                    id="password_confirmation"
                    class="form-control"
                    type="password"
                    name="password_confirmation"
                    placeholder="Repite la nueva contraseña"
                    autocomplete="new-password"
                    required
                >

                <button
                    class="password-toggle"
                    type="button"
                    data-target="#password_confirmation"
                    aria-label="Mostrar contraseña"
                >
                    <i class="bi bi-eye"></i>
                </button>
            </div>

            <span
                id="password_confirmationError"
                class="field-error"
            ></span>
        </div>

        <button
            class="btn-primary"
            type="submit"
            data-loading-text="Guardando contraseña..."
        >
            <span class="button-label">
                Guardar nueva contraseña
            </span>

            <i class="bi bi-check2"></i>
        </button>

        <div class="back-link">
            <a class="text-link" href="{{ route('login') }}">
                <i class="bi bi-arrow-left"></i>
                Volver al inicio de sesión
            </a>
        </div>
    </form>

@endsection

@section('scripts')

    <script>
        $(function () {
            const formulario = $('#resetForm');
            const password = $('#password');
            const confirmacion = $('#password_confirmation');

            function mostrarEstadoCampo(campo, mensaje) {
                const error = $('#' + campo.attr('id') + 'Error');

                campo.toggleClass(
                    'input-error',
                    Boolean(mensaje)
                );

                error
                    .text(mensaje || '')
                    .toggle(Boolean(mensaje));

                return !mensaje;
            }

            function validarPassword() {
                let mensaje = '';

                if (!password.val()) {
                    mensaje = 'Ingresa una contraseña nueva.';
                } else if (password.val().length < 8) {
                    mensaje = 'La contraseña debe tener al menos 8 caracteres.';
                }

                return mostrarEstadoCampo(password, mensaje);
            }

            function validarConfirmacion() {
                let mensaje = '';

                if (!confirmacion.val()) {
                    mensaje = 'Confirma la nueva contraseña.';
                } else if (confirmacion.val() !== password.val()) {
                    mensaje = 'Las contraseñas no coinciden.';
                }

                return mostrarEstadoCampo(
                    confirmacion,
                    mensaje
                );
            }

            password.on('blur input', function () {
                validarPassword();

                if (confirmacion.val()) {
                    validarConfirmacion();
                }
            });

            confirmacion.on(
                'blur input',
                validarConfirmacion
            );

            formulario.on('submit', function (event) {
                const passwordValido = validarPassword();
                const confirmacionValida = validarConfirmacion();

                if (!passwordValido || !confirmacionValida) {
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