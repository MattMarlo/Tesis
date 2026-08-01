<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuiaReserva extends Model
{
    public const ESTADO_CONFIRMADO =
        'confirmado';

    public const ESTADO_PENDIENTE =
        'pendiente';

    public const ESTADO_CANCELADO =
        'cancelado';

    protected $table =
        'guias_reserva';

    protected $fillable = [
        'operacion_viaje_id',
        'nombre_completo',
        'empresa',
        'ciudad_servicio',
        'telefono',
        'correo',
        'idiomas',
        'fecha_inicio',
        'fecha_fin',
        'punto_encuentro',
        'fecha_hora_encuentro',
        'servicios_incluidos',
        'contacto_emergencia',
        'costo_total',
        'moneda',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'fecha_inicio' =>
            'date',
        'fecha_fin' =>
            'date',
        'fecha_hora_encuentro' =>
            'datetime',
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
}