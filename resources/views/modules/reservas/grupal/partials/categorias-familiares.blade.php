<section
    id="seccionFamiliaCategorias"
    class="grupal-seccion oculto"
>
    <div class="seccion-titulo">
        <h2>2. Titular y composición familiar</h2>
        <p>
            Selecciona únicamente al titular registrado y distribuye
            a todos los viajeros por categoría.
        </p>
    </div>

    <div class="campo-grupal campo-limitado">
        <label for="titular_id">
            Titular del grupo <span>*</span>
        </label>

        <select
            id="titular_id"
            name="titular_id"
            class="control-grupal"
        >
            <option value="">Selecciona al titular</option>

            @foreach ($clientes as $cliente)
                @php
                    $clienteCompleto =
                        $cliente->fecha_nacimiento &&
                        $cliente->tipo_documento &&
                        $cliente->documento &&
                        $cliente->nacionalidad;
                @endphp

                <option
                    value="{{ $cliente->id }}"
                    data-nombre="{{ $cliente->nombre_completo }}"
                    data-fecha-nacimiento="{{ $cliente->fecha_nacimiento?->format('Y-m-d') }}"
                    data-completo="{{ $clienteCompleto ? '1' : '0' }}"
                    @selected((int) $titularSeleccionado === $cliente->id)
                >
                    {{ $cliente->nombre_completo }} — {{ $cliente->documento }}
                    @if (!$clienteCompleto)
                        (información incompleta)
                    @endif
                </option>
            @endforeach
        </select>

        <small id="titular_idError" class="mensaje-error"></small>
    </div>

    <div class="nota-grupal nota-titular-categoria">
        <i class="bi bi-info-circle"></i>
        <span>
            El titular ya debe estar incluido en su categoría:
            adultos si tendrá entre 18 y 60 años, o adultos mayores
            si tendrá 61 años o más en la fecha del viaje.
        </span>
    </div>

    <div class="categorias-familiares-grid">
        @foreach ([
            ['cantidad_infantes', 'Infantes', 'Menores de 2 años', '0 %'],
            ['cantidad_ninos', 'Niños', 'De 2 a 11 años', '50 %'],
            ['cantidad_adultos', 'Adultos', 'De 12 a 60 años', '100 %'],
            ['cantidad_adultos_mayores', 'Adultos mayores', 'Desde 61 años', '50 %'],
        ] as [$campo, $tituloCategoria, $rango, $tarifa])
            <div class="categoria-familiar">
                <label for="{{ $campo }}">{{ $tituloCategoria }}</label>
                <span>{{ $rango }} · Tarifa {{ $tarifa }}</span>
                <input
                    id="{{ $campo }}"
                    name="{{ $campo }}"
                    class="control-grupal cantidad-familiar"
                    type="number"
                    min="0"
                    max="1000"
                    step="1"
                    value="{{ $cantidadesFamiliares[$campo] ?? 0 }}"
                >
                <small id="{{ $campo }}Error" class="mensaje-error"></small>
            </div>
        @endforeach
    </div>

    <div class="resumen-composicion-familiar">
        <span>Total de viajeros familiares</span>
        <strong id="totalViajerosFamiliares">0</strong>
    </div>
</section>
