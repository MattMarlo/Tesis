<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrupoCliente extends Model
{
    use HasFactory;

    protected $table = 'grupos_clientes';

    protected $fillable = [
        'grupo_id',
        'cliente_id',
        'edad_al_viajar',
        'categoria_tarifa',
        'porcentaje_tarifa',
        'precio_base',
        'monto_asignado',
        'es_lider',
    ];

    protected function casts(): array
    {
        return [
            'edad_al_viajar' => 'integer',
            'porcentaje_tarifa' => 'decimal:2',
            'precio_base' => 'decimal:2',
            'monto_asignado' => 'decimal:2',
            'es_lider' => 'boolean',
        ];
    }

    public function grupo()
    {
        return $this->belongsTo(
            Grupo::class,
            'grupo_id'
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