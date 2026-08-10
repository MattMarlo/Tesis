@php
    $esEdicion = $cliente->exists;

    $tipoDocumento = old(
        'tipo_documento',
        $cliente->tipo_documento
    );

    $estado = old(
        'estado',
        $cliente->estado ?: 'activo'
    );
@endphp

<form
    id="formularioCliente"
    class="formulario-cliente"
    action="{{ $esEdicion
        ? route('clientes.update', $cliente->id)
        : route('clientes.store') }}"
    method="POST"
    enctype="multipart/form-data"
    novalidate
>
    @csrf

    @if ($esEdicion)
        @method('PUT')
    @endif

    @if (isset($preReserva) && $preReserva)
        <input type="hidden" name="prereserva_id" value="{{ $preReserva->id }}">
    @elseif (request()->filled('prereserva_id'))
        <input type="hidden" name="prereserva_id" value="{{ request('prereserva_id') }}">
    @endif

    <section class="formulario-seccion">
        <div class="seccion-encabezado">
            <div>
                <h2>Información personal</h2>
                <p>
                    Registra los datos tal como aparecen en el documento
                    de identidad del cliente.
                </p>
            </div>
        </div>

        <div class="formulario-grid">
            <div class="campo-formulario">
                <label for="nombres">
                    Nombres <span>*</span>
                </label>

                <input
                    id="nombres"
                    class="control-formulario"
                    type="text"
                    name="nombres"
                    value="{{ old('nombres', $cliente->nombres) }}"
                    placeholder="Ejemplo: María Fernanda"
                    maxlength="100"
                    autocomplete="given-name"
                    required
                >

                <small
                    id="nombresError"
                    class="mensaje-error"
                ></small>
            </div>

            <div class="campo-formulario">
                <label for="apellidos">
                    Apellidos <span>*</span>
                </label>

                <input
                    id="apellidos"
                    class="control-formulario"
                    type="text"
                    name="apellidos"
                    value="{{ old('apellidos', $cliente->apellidos) }}"
                    placeholder="Ejemplo: Pérez López"
                    maxlength="100"
                    autocomplete="family-name"
                    required
                >

                <small
                    id="apellidosError"
                    class="mensaje-error"
                ></small>
            </div>

            <div class="campo-formulario">
                <label for="tipo_documento">
                    Tipo de documento <span>*</span>
                </label>

                <select
                    id="tipo_documento"
                    class="control-formulario"
                    name="tipo_documento"
                    required
                >
                    <option value="">
                        Selecciona una opción
                    </option>

                    <option
                        value="cedula"
                        @selected($tipoDocumento === 'cedula')
                    >
                        Cédula
                    </option>

                    <option
                        value="pasaporte"
                        @selected($tipoDocumento === 'pasaporte')
                    >
                        Pasaporte
                    </option>
                </select>

                <small
                    id="tipo_documentoError"
                    class="mensaje-error"
                ></small>
            </div>

            <div class="campo-formulario">
                <label for="documento">
                    Número de documento <span>*</span>
                </label>

                <input
                    id="documento"
                    class="control-formulario"
                    type="text"
                    name="documento"
                    value="{{ old('documento', $cliente->documento) }}"
                    placeholder="Selecciona primero el tipo de documento"
                    maxlength="20"
                    autocomplete="off"
                    required
                >

                <small
                    id="documentoAyuda"
                    class="mensaje-ayuda"
                >
                    Selecciona el tipo de documento.
                </small>

                <small
                    id="documentoError"
                    class="mensaje-error"
                ></small>
            </div>

            <div class="campo-formulario">
                <label for="fecha_nacimiento">
                    Fecha de nacimiento <span>*</span>
                </label>

                <input
                    id="fecha_nacimiento"
                    class="control-formulario"
                    type="date"
                    name="fecha_nacimiento"
                    value="{{ old(
                        'fecha_nacimiento',
                        $cliente->fecha_nacimiento?->format('Y-m-d')
                    ) }}"
                    min="{{ now()->subYears(100)->format('Y-m-d') }}"
                    max="{{ now()->subYear()->format('Y-m-d') }}"
                    required
                >

                <small
                    id="fecha_nacimientoError"
                    class="mensaje-error"
                ></small>
            </div>

            <div class="campo-formulario">
                <label for="nacionalidad">
                    Nacionalidad <span>*</span>
                </label>

                <select
                    id="nacionalidad"
                    class="control-formulario"
                    name="nacionalidad"
                    required
                >
                    <option value="">Selecciona un país</option>
                    @foreach ($paises as $pais)
                        <option
                            value="{{ $pais }}"
                            @selected(old('nacionalidad', $cliente->nacionalidad) === $pais)
                        >
                            {{ $pais }}
                        </option>
                    @endforeach
                </select>

                <small
                    id="nacionalidadError"
                    class="mensaje-error"
                ></small>
            </div>

            <div
                id="contenedorCaducidad"
                class="campo-formulario"
            >
                <label for="fecha_caducidad_documento">
                    Caducidad del pasaporte <span>*</span>
                </label>

                <input
                    id="fecha_caducidad_documento"
                    class="control-formulario"
                    type="date"
                    name="fecha_caducidad_documento"
                    value="{{ old(
                        'fecha_caducidad_documento',
                        $cliente->fecha_caducidad_documento?->format('Y-m-d')
                    ) }}"
                    min="{{ now()->addDay()->format('Y-m-d') }}"
                >

                <small class="mensaje-ayuda">
                    El pasaporte debe encontrarse vigente.
                </small>

                <small
                    id="fecha_caducidad_documentoError"
                    class="mensaje-error"
                ></small>
            </div>
        </div>
    </section>

    <section class="formulario-seccion">
        <div class="seccion-encabezado">
            <div>
                <h2>Información de contacto</h2>
                <p>
                    Estos datos se utilizarán para comunicarse con el
                    cliente y enviar información de sus viajes.
                </p>
            </div>
        </div>

        <div class="formulario-grid">
            <div class="campo-formulario">
                <label for="email">
                    Correo electrónico <span>*</span>
                </label>

                <input
                    id="email"
                    class="control-formulario"
                    type="email"
                    name="email"
                    value="{{ old('email', $cliente->email) }}"
                    placeholder="nombre@correo.com"
                    maxlength="50"
                    autocomplete="email"
                    required
                >

                <small
                    id="emailError"
                    class="mensaje-error"
                ></small>
            </div>

            <div class="campo-formulario">
                <label for="telefono">
                    Teléfono <span>*</span>
                </label>

                <input
                    id="telefono"
                    class="control-formulario"
                    type="tel"
                    name="telefono"
                    value="{{ old('telefono', $cliente->telefono) }}"
                    placeholder="Ejemplo: 0987654321"
                    maxlength="16"
                    inputmode="tel"
                    autocomplete="tel"
                    required
                >

                <small class="mensaje-ayuda">
                    Puedes incluir el código del país, por ejemplo +593.
                </small>

                <small
                    id="telefonoError"
                    class="mensaje-error"
                ></small>
            </div>

            <div class="campo-formulario">
                <label for="contacto_emergencia">
                    Contacto de emergencia
                </label>

                <input
                    id="contacto_emergencia"
                    class="control-formulario"
                    type="text"
                    name="contacto_emergencia"
                    value="{{ old(
                        'contacto_emergencia',
                        $cliente->contacto_emergencia
                    ) }}"
                    placeholder="Nombre y apellido"
                    maxlength="150"
                >

                <small class="mensaje-ayuda">
                    Este dato es opcional.
                </small>

                <small
                    id="contacto_emergenciaError"
                    class="mensaje-error"
                ></small>
            </div>

            <div class="campo-formulario">
                <label for="telefono_emergencia">
                    Teléfono de emergencia
                </label>

                <input
                    id="telefono_emergencia"
                    class="control-formulario"
                    type="tel"
                    name="telefono_emergencia"
                    value="{{ old(
                        'telefono_emergencia',
                        $cliente->telefono_emergencia
                    ) }}"
                    placeholder="Ejemplo: 0991234567"
                    maxlength="16"
                    inputmode="tel"
                >

                <small
                    id="telefono_emergenciaError"
                    class="mensaje-error"
                ></small>
            </div>
        </div>
    </section>

    <section class="formulario-seccion">
        <div class="seccion-encabezado">
            <div>
                <h2>Control del registro</h2>
                <p>
                    Define si el cliente puede utilizarse en nuevas
                    reservas y adjunta su documento de respaldo.
                </p>
            </div>
        </div>

        <div class="formulario-grid">
            <div class="campo-formulario">
                <label for="estado">
                    Estado <span>*</span>
                </label>

                <select
                    id="estado"
                    class="control-formulario"
                    name="estado"
                    required
                >
                    <option value="activo" @selected($estado === 'activo')>
                        Activo
                    </option>

                    <option value="inactivo" @selected($estado === 'inactivo')>
                        Inactivo
                    </option>
                </select>

                <small class="mensaje-ayuda">
                    Los clientes inactivos no podrán agregarse a nuevas
                    reservas.
                </small>

                <small
                    id="estadoError"
                    class="mensaje-error"
                ></small>
            </div>

            <div class="campo-formulario campo-archivo">
                <label for="archivo">
                    Documento de respaldo
                </label>

                <input
                    id="archivo"
                    class="control-formulario control-archivo"
                    type="file"
                    name="archivo"
                    accept=".pdf,.jpg,.jpeg,.png"
                >

                <small class="mensaje-ayuda">
                    PDF, JPG, JPEG o PNG. Tamaño máximo: 5 MB.
                </small>

                <small
                    id="archivoError"
                    class="mensaje-error"
                ></small>

                @if ($esEdicion && $cliente->archivo)
                    <div class="archivo-actual">
                        <i class="bi bi-file-earmark-check"></i>

                        <div>
                            <span>Documento actual</span>

                            <a
                                href="{{ Storage::url($cliente->archivo) }}"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                Ver documento
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <div class="acciones-formulario">
        <a
            href="{{ route('clientes') }}"
            class="btn-cancelar"
        >
            Cancelar
        </a>

        <button
            type="submit"
            class="btn-guardar"
            data-accion="{{ $esEdicion ? 'actualizar' : 'registrar' }}"
        >
            <span>
                {{ $esEdicion
                    ? 'Guardar cambios'
                    : 'Registrar cliente' }}
            </span>

            <i class="bi bi-check-lg"></i>
        </button>
    </div>
</form>
