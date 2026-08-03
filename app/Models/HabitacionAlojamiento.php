<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HabitacionAlojamiento extends Model
{
    public const CAPACIDADES = [
        'individual' => 1,
        'matrimonial' => 2,
        'doble' => 2,
        'triple' => 3,
        'cuadruple' => 4,
        'quintuple' => 5,
    ];

    protected $table = 'habitaciones_alojamiento';

    protected $fillable = [
        'alojamiento_reserva_id',
        'tipo',
        'capacidad',
        'referencia',
        'observaciones',
    ];

    protected function casts(): array
    {
        return ['capacidad' => 'integer'];
    }

    public function alojamiento()
    {
        return $this->belongsTo(
            AlojamientoReserva::class,
            'alojamiento_reserva_id'
        );
    }

    public function asignaciones()
    {
        return $this->hasMany(
            AsignacionHabitacion::class,
            'habitacion_alojamiento_id'
        );
    }
}
