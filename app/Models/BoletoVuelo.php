<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoletoVuelo extends Model
{
    public const ESTADO_PENDIENTE =
        'pendiente';

    public const ESTADO_EMITIDO =
        'emitido';

    public const ESTADO_CANCELADO =
        'cancelado';

    protected $table =
        'boletos_vuelo';

    protected $fillable = [
        'vuelo_reserva_id',
        'cliente_id',
        'numero_boleto',
        'asiento',
        'clase',
        'estado_emision',
        'archivo_boleto',
        'observaciones',
    ];

    public function vuelo()
    {
        return $this->belongsTo(
            VueloReserva::class,
            'vuelo_reserva_id'
        );
    }

    public function cliente()
    {
        return $this->belongsTo(
            Cliente::class,
            'cliente_id'
        );
    }
}