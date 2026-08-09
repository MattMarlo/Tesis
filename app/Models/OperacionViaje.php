<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperacionViaje extends Model
{
    public const ESTADO_PENDIENTE =
        'pendiente';

    public const ESTADO_PREPARACION =
        'preparacion';

    public const ESTADO_COMPLETO =
        'completo';

    public const ESTADO_NOTIFICADO =
        'notificado';

    protected $table =
        'operaciones_viaje';

    protected $fillable = [
        'reserva_id',
        'estado',
        'observaciones',
        'fecha_documentacion_completa',
        'fecha_notificacion',
        'creado_por_user_id',
        'actualizado_por_user_id',
    ];

    protected $casts = [
        'fecha_documentacion_completa' =>
            'datetime',

        'fecha_notificacion' =>
            'datetime',
    ];

    public function reserva()
    {
        return $this->belongsTo(
            Reserva::class,
            'reserva_id'
        );
    }

    public function creadoPor()
    {
        return $this->belongsTo(
            User::class,
            'creado_por_user_id'
        );
    }

    public function actualizadoPor()
    {
        return $this->belongsTo(
            User::class,
            'actualizado_por_user_id'
        );
    }

    public function vuelos()
    {
        return $this->hasMany(
            VueloReserva::class,
            'operacion_viaje_id'
        )->orderBy(
            'fecha_hora_salida'
        );
    }

    public function alojamientos()
    {
        return $this->hasMany(
            AlojamientoReserva::class,
            'operacion_viaje_id'
        )->orderBy(
            'fecha_hora_entrada'
        );
    }

    public function guias()
    {
        return $this->hasMany(
            GuiaReserva::class,
            'operacion_viaje_id'
        )->orderBy(
            'fecha_inicio'
        );
    }

    public function tareas()
    {
        return $this->hasMany(
            TareaOperacionViaje::class,
            'operacion_viaje_id'
        )
            ->orderBy('dia')
            ->orderBy('id');
    }

    public function tareasVigentes()
    {
        return $this->hasMany(
            TareaOperacionViaje::class,
            'operacion_viaje_id'
        )
            ->where('vigente', true)
            ->orderBy('dia')
            ->orderBy('id');
    }

    public function estaCompleta(): bool
    {
        return in_array(
            $this->estado,
            [
                self::ESTADO_COMPLETO,
                self::ESTADO_NOTIFICADO,
            ],
            true
        );
    }

    public function fueNotificada(): bool
    {
        return $this->estado ===
            self::ESTADO_NOTIFICADO;
    }
}