<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Testimonio extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'destino',
        'comentario',
        'calificacion',
        'foto',
        'estado',
        'destacado',
        'orden',
    ];

    protected $casts = [
        'calificacion' => 'integer',
        'destacado' => 'boolean',
        'orden' => 'integer',
    ];

    public function scopePublicados($consulta)
    {
        return $consulta
            ->where('estado', 'publicado')
            ->orderByDesc('destacado')
            ->orderBy('orden')
            ->orderByDesc('created_at');
    }
}