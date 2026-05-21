<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    protected $table = 'pagos';

    protected $fillable = [
        'reserva_id',
        'cliente_id',
        'user_id',
        'monto_depositado',
        'fecha_pago',
        'metodo_pago',
        'referencia'
    ];

    // 🔗 Relaciones

    // Un pago pertenece a una reserva
    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'reserva_id');
    }

    // Un pago pertenece a un cliente
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    // Un pago pertenece a un usuario
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}