<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreReserva extends Model
{
    protected $table = 'pre_reservas';

    protected $fillable = [
        'cliente_nombre',
        'email',
        'destino',           
        'telefono',
        'cedula',
        'fecha_viaje',
        'fecha_reserva',
        'origen',
        'estado',
        'user_id',
        'reserva_id',
    ];

    protected $casts = [
        'fecha_viaje' => 'date',
    ];

    // Relación con el usuario (agente que convirtió la pre-reserva)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relación con la reserva real generada
    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'reserva_id');
    }
}
