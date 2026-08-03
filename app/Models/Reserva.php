<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Pago;

class Reserva extends Model
{
    public const TIPO_INDIVIDUAL = 'individual';
    public const TIPO_GRUPAL = 'grupal';

    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_CONFIRMADA = 'confirmada';
    public const ESTADO_CANCELADA = 'cancelada';

    public const PAGO_PENDIENTE = 'pendiente';
    public const PAGO_PARCIAL = 'parcial';
    public const PAGO_COMPLETO = 'pagado';

    public const TARIFA_INFANTE = 'infante';
    public const TARIFA_NINO = 'nino';
    public const TARIFA_ADULTO = 'adulto';
    public const TARIFA_ADULTO_MAYOR = 'adulto_mayor';

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
        'moneda',
        'precio_base_persona',
        'cantidad_viajeros',
        'edad_viajero',
        'categoria_tarifa',
        'porcentaje_tarifa',
        'estado',
        'estado_pago',
        'motivo_cancelacion',
        'fecha_cancelacion',
        'cancelado_por_user_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha_reserva' => 'date',
            'fecha_viaje' => 'date',
            'precio_total_viaje' => 'decimal:2',
            'precio_base_persona' => 'decimal:2',
            'cantidad_viajeros' => 'integer',
            'edad_viajero' => 'integer',
            'porcentaje_tarifa' => 'decimal:2',
            'fecha_cancelacion' => 'datetime',
        ];
    }

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
     * Relación temporal para mantener funcionando el código anterior.
     * Se eliminará cuando Pagos utilice el nombre correcto: pagos().
     */
    public function pago()
    {
        return $this->pagos();
    }

    public function todosLosPagos()
    {
        return $this->hasMany(
            Pago::class,
            'reserva_id'
        );
    }

    public function getTotalPagadoAttribute(): float
    {
        if ($this->relationLoaded('pagos')) {
            return (float) $this->pagos->sum(
                'monto_depositado'
            );
        }

        return (float) $this->pagos()
            ->sum('monto_depositado');
    }

    public function getSaldoPendienteAttribute(): float
    {
        return max(
            0,
            (float) $this->precio_total_viaje -
            $this->total_pagado
        );
    }

    public function estaCancelada(): bool
    {
        return $this->estado === self::ESTADO_CANCELADA;
    }

    public function esGrupal(): bool
    {
        return $this->tipo === self::TIPO_GRUPAL;
    }

    public function esIndividual(): bool
    {
        return $this->tipo === self::TIPO_INDIVIDUAL;
    }

    public function operacionViaje()
    {
        return $this->hasOne(
            OperacionViaje::class,
            'reserva_id'
        );
    }

    public function viajerosReserva()
    {
        return $this->hasMany(ViajeroReserva::class, 'reserva_id')
            ->orderByDesc('es_titular')
            ->orderBy('id');
    }
}
