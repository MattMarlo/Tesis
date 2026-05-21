<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Cliente extends Model
{
    
    protected $table = 'clientes';
    public $timestamps = true;
    protected $fillable = [
        'nombre',
        'apellido',
        'email',
        'telefono',
        'documento',
        'estado',
    ];
    // Relación: un cliente tiene muchas reservas
    public function reservas(){
        return $this->hasMany(Reserva::class,'cliente_id');
    }
    // Relación: muchos a muchos con grupos
    public function grupos(){
    return $this->belongsToMany(Grupo::class, 'grupo_cliente', 'cliente_id', 'grupo_id')
                ->withPivot('monto_asignado', 'es_lider');
    }
    // Pagos realizados por el cliente
    public function pagos(){
        return $this->hasMany(Pago::class, 'cliente_id');
    }

    
}
