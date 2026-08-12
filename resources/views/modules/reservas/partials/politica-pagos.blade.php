@php
    $porcentajeAnticipo = max(
        1,
        min(
            100,
            (float) config('reservas.porcentaje_anticipo', 30)
        )
    );
    $diasAnticipo = (int) config(
        'reservas.dias_para_pagar_anticipo',
        3
    );
    $diasSaldo = (int) config(
        'reservas.dias_antes_saldo_final',
        30
    );
    $diasGracia = (int) config(
        'reservas.dias_gracia_riesgo',
        7
    );
@endphp

<section class="{{ $claseSeccion }}">
    <div class="seccion-titulo">
        <h2>4. Política de pagos y cancelación</h2>
        <p>
            El cliente debe conocer y aceptar estas condiciones antes de
            crear la reserva provisional.
        </p>
    </div>

    <div class="politica-reglas">
        <article class="politica-regla">
            <i class="bi bi-wallet2"></i>
            <div>
                <span>Anticipo obligatorio</span>
                <strong>{{ number_format($porcentajeAnticipo, 0) }} %</strong>
                <small>
                    Debe pagarse dentro de {{ $diasAnticipo }} día(s).
                    Si faltan {{ $diasSaldo }} días o menos para viajar,
                    se exige el pago completo.
                </small>
            </div>
        </article>

        <article class="politica-regla">
            <i class="bi bi-calendar-check"></i>
            <div>
                <span>Saldo final</span>
                <strong>{{ $diasSaldo }} días antes</strong>
                <small>
                    Se permiten varios abonos, pero el saldo debe quedar
                    completado antes de esta fecha límite.
                </small>
            </div>
        </article>

        <article class="politica-regla politica-regla-riesgo">
            <i class="bi bi-exclamation-triangle"></i>
            <div>
                <span>Reserva en riesgo</span>
                <strong>{{ $diasGracia }} días de gracia</strong>
                <small>
                    Si el saldo vence, la reserva pasa a riesgo. Si no se
                    regulariza dentro del plazo, se cancela automáticamente.
                </small>
            </div>
        </article>

        <article class="politica-regla politica-regla-reembolso">
            <i class="bi bi-arrow-counterclockwise"></i>
            <div>
                <span>Cancelación y reembolso</span>
                <strong>Revisión documentada</strong>
                <small>
                    Se devuelve lo pagado menos costos reales no recuperables
                    de proveedores. En fuerza mayor se revisa la evidencia y
                    cada gasto debe contar con respaldo.
                </small>
            </div>
        </article>
    </div>

    <input
        id="canal_aceptacion_politica"
        name="canal_aceptacion_politica"
        type="hidden"
        value="otro"
    >
    <input
        id="referencia_aceptacion_politica"
        name="referencia_aceptacion_politica"
        type="hidden"
        value="Aceptación confirmada en el formulario administrativo"
    >

    <label class="politica-aceptacion">
        <input
            id="politica_aceptada"
            name="politica_aceptada"
            type="checkbox"
            value="1"
            @checked(old('politica_aceptada'))
            required
        >
        <span>
            <strong>Confirmación obligatoria</strong>
            Confirmo que el cliente o responsable recibió y aceptó estas
            reglas.
        </span>
    </label>
    <small
        id="politica_aceptadaError"
        class="mensaje-error"
    ></small>
</section>
