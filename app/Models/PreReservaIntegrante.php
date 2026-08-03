<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreReservaIntegrante extends Model
{
    protected $table = 'pre_reserva_integrantes';

    protected $fillable = [
        'pre_reserva_id', 'nombres', 'apellidos', 'tipo_documento',
        'documento', 'fecha_nacimiento', 'fecha_caducidad_documento',
        'nacionalidad', 'email', 'telefono', 'contacto_emergencia',
        'telefono_emergencia', 'es_lider', 'es_responsable_pago',
        'edad_al_viajar', 'categoria_tarifa', 'porcentaje_tarifa',
        'precio_calculado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'fecha_caducidad_documento' => 'date',
            'es_lider' => 'boolean',
            'es_responsable_pago' => 'boolean',
            'edad_al_viajar' => 'integer',
            'porcentaje_tarifa' => 'decimal:2',
            'precio_calculado' => 'decimal:2',
        ];
    }

    public function preReserva()
    {
        return $this->belongsTo(PreReserva::class, 'pre_reserva_id');
    }
}
