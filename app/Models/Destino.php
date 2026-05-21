<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Destino extends Model
{
    protected $table = 'destinos';
    public function reservas() {
        return $this->hasMany(Reserva::class, 'destino_id');
    }
}

