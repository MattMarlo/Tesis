<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VueloReserva extends Model
{
    public const TRAMO_IDA = 'ida';
    public const TRAMO_REGRESO = 'regreso';
    public const TRAMO_CONEXION = 'conexion';

    public const ESTADO_CONFIRMADO =
        'confirmado';

    public const ESTADO_PENDIENTE =
        'pendiente';

    public const ESTADO_CANCELADO =
        'cancelado';

    protected $table =
        'vuelos_reserva';

    protected $fillable = [
        'operacion_viaje_id',
        'tipo_tramo',
        'aerolinea',
        'numero_vuelo',
        'ciudad_origen',
        'aeropuerto_origen',
        'ciudad_destino',
        'aeropuerto_destino',
        'fecha_hora_salida',
        'fecha_hora_llegada',
        'terminal_salida',
        'terminal_llegada',
        'localizador_reserva',
        'equipaje_incluido',
        'proveedor',
        'fecha_compra',
        'costo_total',
        'moneda',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'fecha_hora_salida' =>
            'datetime',
        'fecha_hora_llegada' =>
            'datetime',
        'fecha_compra' =>
            'date',
        'costo_total' =>
            'decimal:2',
    ];

    public function operacion()
    {
        return $this->belongsTo(
            OperacionViaje::class,
            'operacion_viaje_id'
        );
    }

    public function boletos()
    {
        return $this->hasMany(
            BoletoVuelo::class,
            'vuelo_reserva_id'
        );
    }
}