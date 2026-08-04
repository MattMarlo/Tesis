<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ViajeroReserva extends Model
{
    protected $table = 'viajeros_reserva';

    protected $fillable = [
        'reserva_id',
        'cliente_id',
        'nombres',
        'apellidos',
        'tipo_documento',
        'documento',
        'fecha_nacimiento',
        'edad_al_viajar',
        'categoria_tarifa',
        'es_titular',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'edad_al_viajar' => 'integer',
            'es_titular' => 'boolean',
        ];
    }

    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'reserva_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function boletos()
    {
        return $this->hasMany(BoletoVuelo::class, 'viajero_reserva_id');
    }

    public function asignacionesHabitacion()
    {
        return $this->hasMany(
            AsignacionHabitacion::class,
            'viajero_reserva_id'
        );
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim($this->nombres . ' ' . $this->apellidos);
    }

    public function getDocumentoEnmascaradoAttribute(): string
    {
        if (!$this->documento) {
            return 'Pendiente';
        }

        $longitud = mb_strlen($this->documento);
        $visibles = min(4, $longitud);

        return str_repeat('*', max(0, $longitud - $visibles)) .
            mb_substr($this->documento, -$visibles);
    }

    public function tieneDocumentoCompleto(): bool
    {
        return filled($this->tipo_documento) && filled($this->documento);
    }
}
