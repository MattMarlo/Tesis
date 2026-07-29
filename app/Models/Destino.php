<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Destino extends Model
{
    protected $table = 'destinos';

    protected $fillable = [
        'nombre_paquete',
        'slug',
        'etiqueta',
        'pais',
        'ciudad_destino',
        'categoria',
        'descripcion_corta',
        'descripcion',
        'ciudad_salida',
        'fecha_salida',
        'fecha_regreso',
        'precio',
        'moneda',
        'precio_promocional',
        'dias',
        'noches',
        'aerolinea',
        'hotel',
        'capacidad',
        'incluye',
        'no_incluye',
        'itinerario',
        'condiciones',
        'estado_publicacion',
        'destacado',
        'imagen',
    ];

    protected function casts(): array
    {
        return [
            'fecha_salida' => 'date',
            'fecha_regreso' => 'date',
            'precio' => 'decimal:2',
            'precio_promocional' => 'decimal:2',
            'incluye' => 'array',
            'no_incluye' => 'array',
            'itinerario' => 'array',
            'destacado' => 'boolean',
        ];
    }

    public function reservas()
    {
        return $this->hasMany(Reserva::class, 'destino_id');
    }
}