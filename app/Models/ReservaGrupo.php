<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservaGrupo extends Model
{
    
    protected $table = 'reservas_grupos';

    protected $fillable = [
        'reserva_id',
        'grupo_id'
    ];

    /**
     * Relación con la Reserva (Padre)
     */
    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'reserva_id');
    }

    /**
     * Relación con el Grupo
     */
    public function grupo()
    {
        return $this->belongsTo(Grupo::class, 'grupo_id');
    }
}
