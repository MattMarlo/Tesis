<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TareaOperacionViaje extends Model
{
    public const ESTADO_PENDIENTE =
        'pendiente';

    public const ESTADO_EN_PROCESO =
        'en_proceso';

    public const ESTADO_COMPLETADA =
        'completada';

    public const ESTADO_OMITIDA =
        'omitida';

    public const TIPO_RESERVA =
        'reserva';

    public const TIPO_ENTRADA =
        'entrada';

    public const TIPO_GUIA =
        'guia';

    public const TIPO_ALIMENTACION =
        'alimentacion';

    public const TIPO_ALOJAMIENTO =
        'alojamiento';

    public const TIPO_ACTIVIDAD =
        'actividad';

    public const TIPO_OTRO =
        'otro';

    protected $table =
        'tareas_operacion_viaje';

    protected $fillable = [
        'operacion_viaje_id',
        'actividad_uuid',
        'dia',
        'nombre',
        'descripcion',
        'hora_inicio',
        'hora_fin',
        'ubicacion',
        'tipo_gestion',
        'estado',
        'vigente',
        'observaciones',
        'completada_at',
        'completada_por_user_id',
    ];

    protected function casts(): array
    {
        return [
            'dia' =>
                'integer',

            'vigente' =>
                'boolean',

            'completada_at' =>
                'datetime',
        ];
    }

    public function operacion()
    {
        return $this->belongsTo(
            OperacionViaje::class,
            'operacion_viaje_id'
        );
    }

    public function completadaPor()
    {
        return $this->belongsTo(
            User::class,
            'completada_por_user_id'
        );
    }

    public function scopeVigentes(
        Builder $consulta
    ): Builder {
        return $consulta->where(
            'vigente',
            true
        );
    }

    public function estaPendiente(): bool
    {
        return $this->estado ===
            self::ESTADO_PENDIENTE;
    }

    public function estaEnProceso(): bool
    {
        return $this->estado ===
            self::ESTADO_EN_PROCESO;
    }

    public function estaCompletada(): bool
    {
        return $this->estado ===
            self::ESTADO_COMPLETADA;
    }

    public function estaOmitida(): bool
    {
        return $this->estado ===
            self::ESTADO_OMITIDA;
    }

    public function estaResuelta(): bool
    {
        return in_array(
            $this->estado,
            [
                self::ESTADO_COMPLETADA,
                self::ESTADO_OMITIDA,
            ],
            true
        );
    }
}