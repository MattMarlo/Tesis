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
    ];

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
}