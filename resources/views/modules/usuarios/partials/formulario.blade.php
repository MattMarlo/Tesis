@php
    $esEdicion = $usuario->exists;
@endphp

<link
    rel="stylesheet"
    href="{{ asset('css/usuarios-formulario.css') }}?v={{ filemtime(public_path('css/usuarios-formulario.css')) }}"
>

<form
    id="formularioUsuario"
    action="{{ $esEdicion
        ? route('usuarios.update', $usuario->id)
        : route('usuarios.store') }}"
    method="POST"
    novalidate
>
    @csrf

    @if($esEdicion)
        @method('PUT')
    @endif

    <div
        id="erroresServidorUsuario"
        data-errores='@json($errors->all())'
        data-errores-campos='@json($errors->toArray())'
    ></div>

    <div class="encabezado-formulario-usuario">
        <div>
            <h2>
                {{ $esEdicion
                    ? 'Editar usuario'
                    : 'Registrar usuario' }}
            </h2>

            <p>
                {{ $esEdicion
                    ? 'Actualiza la información y los permisos de la cuenta.'
                    : 'Completa los datos de la persona que utilizará el sistema.' }}
            </p>
        </div>

        <a href="{{ route('usuarios') }}">
            <i class="bi bi-arrow-left"></i>
            Volver al listado
        </a>
    </div>

    <section class="bloque-formulario-usuario">

        <h3>Información personal</h3>

        <div class="rejilla-formulario-usuario">

            <div class="campo-usuario">
                <label for="nombres">
                    Nombres <span>*</span>
                </label>

                <input
                    type="text"
                    id="nombres"
                    name="nombres"
                    minlength="2"
                    maxlength="100"
                    value="{{ old(
                        'nombres',
                        $usuario->nombres
                    ) }}"
                    placeholder="Ejemplo: María Fernanda"
                    autocomplete="given-name"
                    required
                >

                <small class="error-campo"></small>
            </div>

            <div class="campo-usuario">
                <label for="apellidos">
                    Apellidos <span>*</span>
                </label>

                <input
                    type="text"
                    id="apellidos"
                    name="apellidos"
                    minlength="2"
                    maxlength="100"
                    value="{{ old(
                        'apellidos',
                        $usuario->apellidos
                    ) }}"
                    placeholder="Ejemplo: López Pérez"
                    autocomplete="family-name"
                    required
                >

                <small class="error-campo"></small>
            </div>

            <div class="campo-usuario">
                <label for="documento">
                    Número de identificación <span>*</span>
                </label>

                <input
                    type="text"
                    id="documento"
                    name="documento"
                    minlength="5"
                    maxlength="30"
                    value="{{ old(
                        'documento',
                        $usuario->documento
                    ) }}"
                    placeholder="Cédula o pasaporte"
                    autocomplete="off"
                    autocapitalize="characters"
                    spellcheck="false"
                    required
                >

                <small class="ayuda-campo">
                    Puedes ingresar números, letras o guiones.
                </small>

                <small class="error-campo"></small>
            </div>

            <div class="campo-usuario">
                <label for="telefono">
                    Teléfono <span>*</span>
                </label>

                <input
                    type="tel"
                    id="telefono"
                    name="telefono"
                    maxlength="20"
                    value="{{ old(
                        'telefono',
                        $usuario->telefono
                    ) }}"
                    placeholder="Ejemplo: 0987654321"
                    autocomplete="tel"
                    inputmode="tel"
                    required
                >

                <small class="error-campo"></small>
            </div>

            <div class="campo-usuario campo-completo">
                <label for="email">
                    Correo electrónico <span>*</span>
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    maxlength="100"
                    value="{{ old(
                        'email',
                        $usuario->email
                    ) }}"
                    placeholder="nombre@correo.com"
                    autocomplete="email"
                    required
                >

                <small class="ayuda-campo">
                    Este correo se utilizará para iniciar sesión y recuperar
                    la contraseña.
                </small>

                <small class="error-campo"></small>
            </div>

        </div>

    </section>

    <section class="bloque-formulario-usuario">

        <h3>Acceso al sistema</h3>

        <div class="rejilla-formulario-usuario">

            <div class="campo-usuario">
                <label for="rol">
                    Tipo de usuario <span>*</span>
                </label>

                <select
                    id="rol"
                    name="rol"
                    required
                >
                    <option value="">
                        Selecciona una opción
                    </option>

                    <option
                        value="admin"
                        {{ old(
                            'rol',
                            $usuario->rol
                        ) === 'admin' ? 'selected' : '' }}
                    >
                        Administrador
                    </option>

                    <option
                        value="agente"
                        {{ old(
                            'rol',
                            $usuario->rol
                        ) === 'agente' ? 'selected' : '' }}
                    >
                        Equipo de trabajo
                    </option>
                </select>

                <small class="ayuda-campo" id="descripcionRol">
                    Selecciona el acceso que tendrá esta persona.
                </small>

                <small class="error-campo"></small>
            </div>

            <div class="campo-usuario">
                <label for="estado">
                    Estado de la cuenta <span>*</span>
                </label>

                <select
                    id="estado"
                    name="estado"
                    required
                >
                    <option
                        value="activo"
                        {{ old(
                            'estado',
                            $usuario->estado ?: 'activo'
                        ) === 'activo' ? 'selected' : '' }}
                    >
                        Activa
                    </option>

                    <option
                        value="inactivo"
                        {{ old(
                            'estado',
                            $usuario->estado
                        ) === 'inactivo' ? 'selected' : '' }}
                    >
                        Inactiva
                    </option>
                </select>

                <small class="ayuda-campo">
                    Una cuenta inactiva no podrá iniciar sesión.
                </small>

                <small class="error-campo"></small>
            </div>

        </div>

    </section>

    <section class="bloque-formulario-usuario">

        <h3>
            {{ $esEdicion
                ? 'Cambiar contraseña'
                : 'Contraseña de acceso' }}
        </h3>

        @if($esEdicion)
            <p class="texto-bloque">
                Deja los siguientes campos vacíos si deseas mantener la
                contraseña actual.
            </p>
        @else
            <p class="texto-bloque">
                La contraseña debe tener al menos ocho caracteres e incluir
                letras y números.
            </p>
        @endif

        <div class="rejilla-formulario-usuario">

            <div class="campo-usuario">
                <label for="password">
                    {{ $esEdicion
                        ? 'Nueva contraseña'
                        : 'Contraseña' }}

                    @if(!$esEdicion)
                        <span>*</span>
                    @endif
                </label>

                <div class="campo-contrasena">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        minlength="8"
                        maxlength="72"
                        autocomplete="new-password"
                        {{ $esEdicion ? '' : 'required' }}
                    >

                    <button
                        type="button"
                        class="mostrar-contrasena"
                        data-campo="#password"
                        aria-label="Mostrar contraseña"
                    >
                        <i class="bi bi-eye"></i>
                    </button>
                </div>

                <small class="error-campo"></small>
            </div>

            <div class="campo-usuario">
                <label for="password_confirmation">
                    Confirmar contraseña

                    @if(!$esEdicion)
                        <span>*</span>
                    @endif
                </label>

                <div class="campo-contrasena">
                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        minlength="8"
                        maxlength="72"
                        autocomplete="new-password"
                        {{ $esEdicion ? '' : 'required' }}
                    >

                    <button
                        type="button"
                        class="mostrar-contrasena"
                        data-campo="#password_confirmation"
                        aria-label="Mostrar contraseña"
                    >
                        <i class="bi bi-eye"></i>
                    </button>
                </div>

                <small class="error-campo"></small>
            </div>

        </div>

    </section>

    <div class="acciones-formulario-usuario">

        <a
            href="{{ route('usuarios') }}"
            class="boton-cancelar-usuario"
            id="cancelarUsuario"
        >
            Cancelar
        </a>

        <button
            type="submit"
            class="boton-guardar-usuario"
        >
            <i class="bi bi-check-circle"></i>

            {{ $esEdicion
                ? 'Guardar cambios'
                : 'Registrar usuario' }}
        </button>

    </div>

</form>
