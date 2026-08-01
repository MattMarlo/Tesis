<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    public const ESTADO_REGISTRADO = 'registrado';
    public const ESTADO_ANULADO = 'anulado';

    public const METODO_EFECTIVO = 'efectivo';
    public const METODO_TRANSFERENCIA = 'transferencia';
    public const METODO_TARJETA = 'tarjeta';
    public const METODO_OTRO = 'otro';

    protected $table = 'pagos';

    public $timestamps = false;

    protected $fillable = [
        'reserva_id',
        'cliente_id',
        'user_id',
        'monto_depositado',
        'fecha_pago',
        'metodo_pago',
        'referencia',
        'estado',
        'motivo_anulacion',
        'fecha_anulacion',
        'anulado_por_user_id',
    ];

    protected $casts = [
        'monto_depositado' => 'decimal:2',
        'fecha_pago' => 'datetime',
        'fecha_anulacion' => 'datetime',
    ];

    public function reserva()
    {
        return $this->belongsTo(
            Reserva::class,
            'reserva_id'
        );
    }

    public function cliente()
    {
        return $this->belongsTo(
            Cliente::class,
            'cliente_id'
        );
    }

    public function user()
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }

    public function anuladoPor()
    {
        return $this->belongsTo(
            User::class,
            'anulado_por_user_id'
        );
    }

    public function scopeRegistrados($consulta)
    {
        return $consulta->where(
            'estado',
            self::ESTADO_REGISTRADO
        );
    }

    public function estaAnulado(): bool
    {
        return $this->estado ===
            self::ESTADO_ANULADO;
    }
}