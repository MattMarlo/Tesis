<?php

return [
    'version_politica' => env(
        'RESERVA_VERSION_POLITICA',
        '2026-08-05-v1'
    ),

    /*
     * Estas reglas se copian a cada reserva cuando se crea. De esta forma,
     * un cambio futuro de la política no altera contratos ya aceptados.
     */
    'porcentaje_anticipo' => (float) env(
        'RESERVA_PORCENTAJE_ANTICIPO',
        30
    ),

    'dias_para_pagar_anticipo' => (int) env(
        'RESERVA_DIAS_PARA_ANTICIPO',
        3
    ),

    'dias_antes_saldo_final' => (int) env(
        'RESERVA_DIAS_SALDO_FINAL',
        30
    ),

    'dias_gracia_riesgo' => (int) env(
        'RESERVA_DIAS_GRACIA_RIESGO',
        7
    ),
];
