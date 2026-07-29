@extends('layouts.auth')

@section('title', 'Recuperar contraseña')

@section('brand-eyebrow', 'Recuperación segura')

@section('brand-title', 'Vuelve a tu cuenta en pocos pasos.')

@section(
    'brand-copy',
    'Te enviaremos un enlace seguro al correo asociado a tu cuenta para que puedas establecer una nueva contraseña.'
)

@section('content')

    <header class="form-header">
        <h2>Recuperar contraseña</h2>

        <p>
            Escribe tu correo electrónico y recibirás las instrucciones
            para restablecer el acceso.
        </p>
    </header>

    <form
        id="recoveryForm"
        class="auth-form"
        action="{{ route('password.email') }}"
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

        <button
            class="btn-primary"
            type="submit"
            data-loading-text="Enviando enlace..."
        >
            <span class="button-label">
                Enviar enlace de recuperación
            </span>

            <i class="bi bi-send"></i>
        </button>

        <div class="back-link">
            <a class="text-link" href="{{ route('login') }}">
                <i class="bi bi-arrow-left"></i>
                Volver al inicio de sesión
            </a>
        </div>
    </form>

    <div class="security-note">
        <i class="bi bi-clock-history"></i>

        <span>
            El enlace tendrá una duración limitada. Si no encuentras
            el mensaje, revisa también la carpeta de correo no deseado.
        </span>
    </div>

@endsection

@section('scripts')

    <script>
        $(function () {
            const formulario = $('#recoveryForm');
            const correo = $('#email');

            function validarCorreo() {
                const valor = $.trim(correo.val());
                let mensaje = '';

                if (!valor) {
                    mensaje = 'Ingresa el correo asociado a tu cuenta.';
                } else {
                    const formatoCorreo = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

                    if (!formatoCorreo.test(valor)) {
                        mensaje = 'Escribe un correo electrónico válido.';
                    }
                }

                correo.toggleClass(
                    'input-error',
                    Boolean(mensaje)
                );

                $('#emailError')
                    .text(mensaje)
                    .toggle(Boolean(mensaje));

                return !mensaje;
            }

            correo.on('blur input', validarCorreo);

            formulario.on('submit', function (event) {
                if (!validarCorreo()) {
                    event.preventDefault();
                    correo.trigger('focus');
                    return;
                }

                activarCargaFormulario(this);
            });
        });
    </script>

@endsection