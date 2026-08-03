<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Devolucion extends Model
{
    public const ESTADO_PROCESADA = 'procesada';

    public const ESTADO_ANULADA = 'anulada';

    protected $table = 'devoluciones';

    protected $fillable = [
        'pago_id', 'reserva_id', 'cliente_id', 'user_id',
        'monto', 'metodo', 'referencia', 'tipo', 'motivo', 'estado',
        'fecha_devolucion', 'motivo_anulacion', 'fecha_anulacion',
        'anulada_por_user_id',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'fecha_devolucion' => 'datetime',
            'fecha_anulacion' => 'datetime',
        ];
    }

    public function pago()
    {
        return $this->belongsTo(Pago::class);
    }

    public function reserva()
    {
        return $this->belongsTo(Reserva::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function anuladaPor()
    {
        return $this->belongsTo(User::class, 'anulada_por_user_id');
    }

    public function scopeProcesadas($query)
    {
        return $query->where('estado', self::ESTADO_PROCESADA);
    }

    public function estaAnulada(): bool
    {
        return $this->estado === self::ESTADO_ANULADA;
    }
}
