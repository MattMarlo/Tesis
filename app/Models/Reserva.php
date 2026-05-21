<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Cliente;
use App\Models\Destino;
use App\Models\Grupo;
use App\Models\User;

class Reserva extends Model
{
    protected $table = 'reservas';
    protected $fillable = [
        'codigo_reserva',
        'cliente_id',
        'grupo_id',
        'destino_id',
        'user_id',
        'tipo',
        'fecha_reserva',
        'fecha_viaje',
        'precio_total_viaje',
        'estado',
        'estado_pago',

    ];
    public function cliente(){
        return $this->belongsTo(Cliente::class,'cliente_id');
    }
    public function reservaGrupo() {
        return $this->hasOne(ReservaGrupo::class, 'reserva_id');
    }
    
    public function grupo(){
        return $this->belongsTo(Grupo::class,'grupo_id');

    }
    public function destino(){
        return $this->belongsTo(Destino::class,'destino_id');
        
    }
    public function user(){
        return $this->belongsTo(User::class,'user_id');
        
    }
    public function pago(){
        return $this->hasMany(Pago::class,'reserva_id');
    }
}
