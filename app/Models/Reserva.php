<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reserva extends Model
{
    /*
     * Tipos de reserva.
     */
    public const TIPO_INDIVIDUAL = 'individual';
    public const TIPO_GRUPAL = 'grupal';

    /*
     * Estados de la reserva.
     */
    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_CONFIRMADA = 'confirmada';
    public const ESTADO_CANCELADA = 'cancelada';

    /*
     * Estados del pago.
     */
    public const PAGO_PENDIENTE = 'pendiente';
    public const PAGO_PARCIAL = 'parcial';
    public const PAGO_COMPLETO = 'pagado';

    /*
     * Estados de cobranza.
     */
    public const COBRANZA_PENDIENTE_ANTICIPO =
        'pendiente_anticipo';

    public const COBRANZA_AL_DIA =
        'al_dia';

    public const COBRANZA_EN_RIESGO =
        'en_riesgo';

    public const COBRANZA_REVISION_CANCELACION =
        'revision_cancelacion';

    public const COBRANZA_PAGADA =
        'pagada';

    public const COBRANZA_CANCELADA =
        'cancelada';

    /*
     * Estados del reembolso.
     */
    public const REEMBOLSO_NO_APLICA =
        'no_aplica';

    public const REEMBOLSO_PENDIENTE_REVISION =
        'pendiente_revision';

    public const REEMBOLSO_PENDIENTE =
        'pendiente';

    public const REEMBOLSO_PARCIAL =
        'parcial';

    public const REEMBOLSO_COMPLETADO =
        'completado';

    public const REEMBOLSO_SIN_REEMBOLSO =
        'sin_reembolso';

    /*
     * Categorías tarifarias.
     */
    public const TARIFA_INFANTE = 'infante';
    public const TARIFA_NINO = 'nino';
    public const TARIFA_ADULTO = 'adulto';
    public const TARIFA_ADULTO_MAYOR =
        'adulto_mayor';

    protected $table = 'reservas';

    protected $fillable = [
        'codigo_reserva',
        'cliente_id',
        'destino_id',
        'user_id',
        'tipo',
        'fecha_reserva',
        'fecha_viaje',
        'precio_total_viaje',

        /*
         * Política de pagos.
         */
        'porcentaje_anticipo',
        'monto_anticipo',
        'fecha_limite_anticipo',
        'fecha_vencimiento_saldo',

        /*
         * Información tarifaria.
         */
        'moneda',
        'precio_base_persona',
        'cantidad_viajeros',
        'edad_viajero',
        'categoria_tarifa',
        'porcentaje_tarifa',

        /*
         * Estados.
         */
        'estado',
        'estado_pago',
        'estado_cobranza',
        'anticipo_completado_at',
        'saldo_completado_at',

        /*
         * Aceptación de la política.
         */
        'version_politica_pago',
        'politica_pago_aceptada',
        'politica_aceptada_at',
        'canal_aceptacion_politica',
        'referencia_aceptacion_politica',

        /*
         * Cancelación.
         */
        'motivo_cancelacion',
        'tipo_cancelacion',
        'cancelacion_automatica',
        'fecha_cancelacion',
        'cancelado_por_user_id',
        'evidencia_cancelacion',

        /*
         * Reembolso.
         */
        'estado_reembolso',
        'monto_pagado_al_cancelar',
        'gastos_no_reembolsables',
        'monto_reembolsable',
        'detalle_gastos_no_reembolsables',
    ];

    protected function casts(): array
    {
        return [
            'fecha_reserva' =>
                'date',

            'fecha_viaje' =>
                'date',

            'fecha_limite_anticipo' =>
                'datetime',

            'fecha_vencimiento_saldo' =>
                'date',

            'anticipo_completado_at' =>
                'datetime',

            'saldo_completado_at' =>
                'datetime',

            'politica_aceptada_at' =>
                'datetime',

            'fecha_cancelacion' =>
                'datetime',

            'precio_total_viaje' =>
                'decimal:2',

            'porcentaje_anticipo' =>
                'decimal:2',

            'monto_anticipo' =>
                'decimal:2',

            'precio_base_persona' =>
                'decimal:2',

            'porcentaje_tarifa' =>
                'decimal:2',

            'monto_pagado_al_cancelar' =>
                'decimal:2',

            'gastos_no_reembolsables' =>
                'decimal:2',

            'monto_reembolsable' =>
                'decimal:2',

            'cantidad_viajeros' =>
                'integer',

            'edad_viajero' =>
                'integer',

            'cancelacion_automatica' =>
                'boolean',
        ];
    }

    /*
     * Relaciones principales.
     */

    public function cliente()
    {
        return $this->belongsTo(
            Cliente::class,
            'cliente_id'
        );
    }

    public function destino()
    {
        return $this->belongsTo(
            Destino::class,
            'destino_id'
        );
    }

    public function user()
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }

    public function canceladoPor()
    {
        return $this->belongsTo(
            User::class,
            'cancelado_por_user_id'
        );
    }

    public function reservaGrupo()
    {
        return $this->hasOne(
            ReservaGrupo::class,
            'reserva_id'
        );
    }

    public function grupo()
    {
        return $this->hasOneThrough(
            Grupo::class,
            ReservaGrupo::class,
            'reserva_id',
            'id',
            'id',
            'grupo_id'
        );
    }

    /*
     * Pagos registrados.
     */
    public function pagos()
    {
        return $this->hasMany(
            Pago::class,
            'reserva_id'
        )->where(
            'estado',
            Pago::ESTADO_REGISTRADO
        );
    }

    /*
     * Relación temporal para conservar
     * compatibilidad con código anterior.
     */
    public function pago()
    {
        return $this->pagos();
    }

    /*
     * Incluye pagos registrados y anulados.
     */
    public function todosLosPagos()
    {
        return $this->hasMany(
            Pago::class,
            'reserva_id'
        );
    }

    /*
     * Devoluciones procesadas.
     */
    public function devoluciones()
    {
        return $this->hasMany(
            Devolucion::class,
            'reserva_id'
        )->where(
            'estado',
            Devolucion::ESTADO_PROCESADA
        );
    }

    /*
     * Incluye devoluciones procesadas y anuladas.
     */
    public function todasLasDevoluciones()
    {
        return $this->hasMany(
            Devolucion::class,
            'reserva_id'
        );
    }

    /*
     * Registros de riesgo.
     */
    public function riesgos()
    {
        return $this->hasMany(
            ReservaRiesgo::class,
            'reserva_id'
        );
    }

    public function riesgoActivo()
    {
        return $this->hasOne(
            ReservaRiesgo::class,
            'reserva_id'
        )
            ->whereIn(
                'estado',
                [
                    ReservaRiesgo::ESTADO_ACTIVA,
                    ReservaRiesgo::
                        ESTADO_REVISION_CANCELACION,
                ]
            )
            ->latestOfMany();
    }

    /*
     * Todos los gastos documentados,
     * independientemente de su estado.
     */
    public function gastosCancelacion()
    {
        return $this->hasMany(
            GastoCancelacion::class,
            'reserva_id'
        );
    }

    /*
     * Solo gastos revisados y aprobados.
     */
    public function gastosCancelacionAprobados()
    {
        return $this->hasMany(
            GastoCancelacion::class,
            'reserva_id'
        )->where(
            'estado',
            GastoCancelacion::ESTADO_APROBADO
        );
    }

    /*
     * Cálculos financieros.
     */

    public function getTotalPagadoAttribute(): float
    {
        if ($this->relationLoaded('pagos')) {
            $totalPagos = (float) $this
                ->pagos
                ->sum('monto_depositado');
        } else {
            $totalPagos = (float) $this
                ->pagos()
                ->sum('monto_depositado');
        }

        if ($this->relationLoaded('devoluciones')) {
            $totalDevuelto = (float) $this
                ->devoluciones
                ->sum('monto');
        } else {
            $totalDevuelto = (float) $this
                ->devoluciones()
                ->sum('monto');
        }

        return max(
            0,
            round(
                $totalPagos - $totalDevuelto,
                2
            )
        );
    }

    public function getSaldoPendienteAttribute(): float
    {
        return max(
            0,
            round(
                (float) $this->precio_total_viaje -
                $this->total_pagado,
                2
            )
        );
    }

    public function getUltimoPagoAttribute(): ?Pago
    {
        if ($this->relationLoaded('pagos')) {
            return $this
                ->pagos
                ->sortByDesc('fecha_pago')
                ->first();
        }

        return $this
            ->pagos()
            ->latest('fecha_pago')
            ->first();
    }

    /*
     * Total de gastos que fueron aprobados.
     */
    public function getTotalGastosCancelacionAprobadosAttribute(): float
    {
        if (
            $this->relationLoaded(
                'gastosCancelacionAprobados'
            )
        ) {
            return round(
                (float) $this
                    ->gastosCancelacionAprobados
                    ->sum('monto'),
                2
            );
        }

        return round(
            (float) $this
                ->gastosCancelacionAprobados()
                ->sum('monto'),
            2
        );
    }

    /*
     * Calcula cuánto puede devolverse utilizando
     * únicamente gastos aprobados y documentados.
     */
    public function getMontoReembolsableDocumentadoAttribute(): float
    {
        $basePagada = $this->estaCancelada()
            ? (float) (
                $this->monto_pagado_al_cancelar ??
                $this->total_pagado
            )
            : $this->total_pagado;

        return round(
            max(
                0,
                $basePagada -
                $this
                    ->total_gastos_cancelacion_aprobados
            ),
            2
        );
    }

    /*
     * Alertas del pago final.
     */

    public function getPagoFinalProximoAttribute(): bool
    {
        if (!$this->fecha_vencimiento_saldo) {
            return false;
        }

        if (
            $this->estado_pago ===
            self::PAGO_COMPLETO
        ) {
            return false;
        }

        if ($this->estaCancelada()) {
            return false;
        }

        $fechaInicio =
            now()->startOfDay();

        $fechaFin = $fechaInicio
            ->copy()
            ->addDays(
                (int) config(
                    'reservas.dias_antes_saldo_final',
                    30
                )
            )
            ->endOfDay();

        return $this
            ->fecha_vencimiento_saldo
            ->copy()
            ->endOfDay()
            ->between(
                $fechaInicio,
                $fechaFin
            );
    }

    public function getPagoFinalVencidoAttribute(): bool
    {
        if (!$this->fecha_vencimiento_saldo) {
            return false;
        }

        if (
            $this->estado_pago ===
            self::PAGO_COMPLETO
        ) {
            return false;
        }

        if ($this->estaCancelada()) {
            return false;
        }

        return now()->gt(
            $this
                ->fecha_vencimiento_saldo
                ->copy()
                ->endOfDay()
        );
    }

    /*
     * Comprobaciones de la reserva.
     */

    public function estaCancelada(): bool
    {
        return $this->estado ===
            self::ESTADO_CANCELADA;
    }

    public function esGrupal(): bool
    {
        return $this->tipo ===
            self::TIPO_GRUPAL;
    }

    public function esIndividual(): bool
    {
        return $this->tipo ===
            self::TIPO_INDIVIDUAL;
    }

    /*
     * Operación del viaje.
     */

    public function operacionViaje()
    {
        return $this->hasOne(
            OperacionViaje::class,
            'reserva_id'
        );
    }

    public function viajerosReserva()
    {
        return $this->hasMany(
            ViajeroReserva::class,
            'reserva_id'
        )
            ->orderByDesc('es_titular')
            ->orderBy('id');
    }
}