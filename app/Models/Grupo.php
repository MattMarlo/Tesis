<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grupo extends Model
{
    public const TIPO_FAMILIAR = 'familiar';
    public const TIPO_INDEPENDIENTE = 'independiente';

    protected $table = 'grupos';

    protected $fillable = [
        'nombre_grupo',
        'descripcion',
        'tipo_grupo',
        'responsable_pago_id',
        'usa_categorias_familiares',
        'cantidad_infantes',
        'cantidad_ninos',
        'cantidad_adultos',
        'cantidad_adultos_mayores',
    ];

    protected function casts(): array
    {
        return [
            'usa_categorias_familiares' => 'boolean',
            'cantidad_infantes' => 'integer',
            'cantidad_ninos' => 'integer',
            'cantidad_adultos' => 'integer',
            'cantidad_adultos_mayores' => 'integer',
        ];
    }

    public function reserva()
    {
        return $this->hasOne(
            ReservaGrupo::class,
            'grupo_id'
        );
    }

    public function clientes()
    {
        return $this->belongsToMany(
            Cliente::class,
            'grupos_clientes',
            'grupo_id',
            'cliente_id'
        )
            ->withPivot([
                'monto_asignado',
                'es_lider',
                'edad_al_viajar',
                'categoria_tarifa',
                'porcentaje_tarifa',
                'precio_base',
            ])
            ->withTimestamps();
    }

    public function responsablePago()
    {
        return $this->belongsTo(
            Cliente::class,
            'responsable_pago_id'
        );
    }

    public function esFamiliar(): bool
    {
        return $this->tipo_grupo === self::TIPO_FAMILIAR;
    }

    public function esIndependiente(): bool
    {
        return $this->tipo_grupo === self::TIPO_INDEPENDIENTE;
    }

    public function usaCategoriasFamiliares(): bool
    {
        return $this->esFamiliar() &&
            (bool) $this->usa_categorias_familiares;
    }

    public function getCantidadViajerosPorCategoriasAttribute(): int
    {
        return (int) $this->cantidad_infantes +
            (int) $this->cantidad_ninos +
            (int) $this->cantidad_adultos +
            (int) $this->cantidad_adultos_mayores;
    }

    public function composicionFamiliar(): array
    {
        return [
            'cantidad_infantes' => (int) $this->cantidad_infantes,
            'cantidad_ninos' => (int) $this->cantidad_ninos,
            'cantidad_adultos' => (int) $this->cantidad_adultos,
            'cantidad_adultos_mayores' =>
                (int) $this->cantidad_adultos_mayores,
        ];
    }
}
