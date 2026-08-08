<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsentimientoDato extends Model
{
    protected $table = 'consentimientos_datos';

    protected $fillable = [
        'telefono',
        'canal',
        'estado',
        'version_politica',
        'politica_url',
        'mensaje_id',
        'fecha_evento',
        'evidencia',
    ];

    protected function casts(): array
    {
        return [
            'fecha_evento' => 'datetime',
            'evidencia' => 'array',
        ];
    }
}