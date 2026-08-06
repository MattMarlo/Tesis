<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    /*
     * Estados del pago.
     */
    public const ESTADO_REGISTRADO =
        'registrado';

    public const ESTADO_ANULADO =
        'anulado';

    /*
     * Métodos de pago.
     */
    public const METODO_EFECTIVO =
        'efectivo';

    public const METODO_TRANSFERENCIA =
        'transferencia';

    public const METODO_TARJETA =
        'tarjeta';

    public const METODO_OTRO =
        'otro';

    /*
     * Conceptos del pago.
     */
    public const CONCEPTO_ANTICIPO =
        'anticipo';

    public const CONCEPTO_ABONO =
        'abono';

    public const CONCEPTO_SALDO_FINAL =
        'saldo_final';

    protected $table = 'pagos';

    /*
     * La tabla pagos no utiliza created_at ni updated_at.
     */
    public $timestamps = false;

    protected $fillable = [
        'reserva_id',
        'cliente_id',
        'user_id',
        'monto_depositado',
        'concepto',
        'fecha_pago',
        'metodo_pago',
        'referencia',
        'estado',
        'motivo_anulacion',
        'fecha_anulacion',
        'anulado_por_user_id',
    ];

    protected $casts = [
        'monto_depositado' =>
            'decimal:2',

        'fecha_pago' =>
            'datetime',

        'fecha_anulacion' =>
            'datetime',
    ];

    /*
     * Reserva asociada al pago.
     */
    public function reserva()
    {
        return $this->belongsTo(
            Reserva::class,
            'reserva_id'
        );
    }

    /*
     * Cliente que realizó el pago.
     */
    public function cliente()
    {
        return $this->belongsTo(
            Cliente::class,
            'cliente_id'
        );
    }

    /*
     * Usuario que registró el pago.
     */
    public function user()
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }

    /*
     * Usuario que anuló el pago.
     */
    public function anuladoPor()
    {
        return $this->belongsTo(
            User::class,
            'anulado_por_user_id'
        );
    }

    /*
     * Devoluciones realizadas sobre este pago.
     */
    public function devoluciones()
    {
        return $this->hasMany(
            Devolucion::class,
            'pago_id'
        );
    }

    /*
     * Filtra únicamente pagos registrados.
     */
    public function scopeRegistrados($consulta)
    {
        return $consulta->where(
            'estado',
            self::ESTADO_REGISTRADO
        );
    }

    /*
     * Calcula cuánto se puede devolver de este pago.
     */
    public function getMontoDisponibleReembolsoAttribute(): float
    {
        if ($this->relationLoaded('devoluciones')) {
            $totalDevuelto = (float) $this
                ->devoluciones
                ->where(
                    'estado',
                    Devolucion::ESTADO_PROCESADA
                )
                ->sum('monto');
        } else {
            $totalDevuelto = (float) $this
                ->devoluciones()
                ->procesadas()
                ->sum('monto');
        }

        return max(
            0,
            round(
                (float) $this->monto_depositado -
                $totalDevuelto,
                2
            )
        );
    }

    public function estaAnulado(): bool
    {
        return $this->estado ===
            self::ESTADO_ANULADO;
    }
}