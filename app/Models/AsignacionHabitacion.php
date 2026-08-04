<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsignacionHabitacion extends Model
{
    protected $table = 'asignaciones_habitacion';

    protected $fillable = [
        'alojamiento_reserva_id',
        'habitacion_alojamiento_id',
        'viajero_reserva_id',
        'cliente_id',
    ];

    public function alojamiento()
    {
        return $this->belongsTo(
            AlojamientoReserva::class,
            'alojamiento_reserva_id'
        );
    }

    public function habitacion()
    {
        return $this->belongsTo(
            HabitacionAlojamiento::class,
            'habitacion_alojamiento_id'
        );
    }

    public function viajeroReserva()
    {
        return $this->belongsTo(ViajeroReserva::class, 'viajero_reserva_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }
}
