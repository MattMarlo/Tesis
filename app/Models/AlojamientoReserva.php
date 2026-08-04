<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlojamientoReserva extends Model
{
    public const ESTADO_CONFIRMADO =
        'confirmado';

    public const ESTADO_PENDIENTE =
        'pendiente';

    public const ESTADO_CANCELADO =
        'cancelado';

    protected $table =
        'alojamientos_reserva';

    protected $fillable = [
        'operacion_viaje_id',
        'nombre_hotel',
        'ciudad',
        'pais',
        'direccion',
        'fecha_hora_entrada',
        'fecha_hora_salida',
        'codigo_confirmacion',
        'tipo_habitacion',
        'cantidad_habitaciones',
        'distribucion_habitaciones',
        'alimentacion_incluida',
        'telefono_hotel',
        'correo_hotel',
        'proveedor',
        'fecha_compra',
        'costo_total',
        'moneda',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'fecha_hora_entrada' =>
            'datetime',
        'fecha_hora_salida' =>
            'datetime',
        'fecha_compra' =>
            'date',
        'cantidad_habitaciones' =>
            'integer',
        'costo_total' =>
            'decimal:2',
    ];

    public function operacion()
    {
        return $this->belongsTo(
            OperacionViaje::class,
            'operacion_viaje_id'
        );
    }

    public function habitaciones()
    {
        return $this->hasMany(
            HabitacionAlojamiento::class,
            'alojamiento_reserva_id'
        );
    }

    public function asignacionesHabitacion()
    {
        return $this->hasMany(
            AsignacionHabitacion::class,
            'alojamiento_reserva_id'
        );
    }
}
