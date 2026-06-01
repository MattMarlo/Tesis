<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConversacionesTelegram extends Model
{
    protected $table = 'conversaciones_telegram';

    protected $fillable = [
        'chat_id',
        'estado',
        'destino',
        'nombre',
        'fecha_viaje',
    ];

}
