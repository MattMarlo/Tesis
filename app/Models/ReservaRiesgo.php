<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservaRiesgo extends Model
{
    public const TIPO_ANTICIPO = 'anticipo_vencido';
    public const TIPO_SALDO = 'saldo_vencido';

    public const ESTADO_ACTIVA = 'activa';
    public const ESTADO_REVISION_CANCELACION = 'revision_cancelacion';
    public const ESTADO_REGULARIZADA = 'regularizada';
    public const ESTADO_CANCELADA = 'cancelada';

    protected $table = 'reservas_riesgo';

    protected $fillable = [
        'reserva_id',
        'tipo',
        'estado',
        'saldo_al_ingresar',
        'fecha_ingreso',
        'fecha_limite_regularizacion',
        'fecha_resolucion',
        'resuelto_por_user_id',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'saldo_al_ingresar' => 'decimal:2',
            'fecha_ingreso' => 'datetime',
            'fecha_limite_regularizacion' => 'datetime',
            'fecha_resolucion' => 'datetime',
        ];
    }

    public function reserva()
    {
        return $this->belongsTo(Reserva::class);
    }

    public function resueltoPor()
    {
        return $this->belongsTo(
            User::class,
            'resuelto_por_user_id'
        );
    }
}
