<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperacionViaje extends Model
{
    public const ESTADO_PENDIENTE =
        'pendiente';

    public const ESTADO_PREPARACION =
        'preparacion';

    public const ESTADO_COMPLETO =
        'completo';

    public const ESTADO_NOTIFICADO =
        'notificado';

    protected $table =
        'operaciones_viaje';

    protected $fillable = [
        'reserva_id',
        'estado',
        'observaciones',
        'fecha_documentacion_completa',
        'fecha_notificacion',
        'creado_por_user_id',
        'actualizado_por_user_id',
    ];

    protected $casts = [
        'fecha_documentacion_completa' =>
            'datetime',

        'fecha_notificacion' =>
            'datetime',
    ];

    /**
     * Reserva a la que pertenece el expediente.
     */
    public function reserva()
    {
        return $this->belongsTo(
            Reserva::class,
            'reserva_id'
        );
    }

    public function creadoPor()
    {
        return $this->belongsTo(
            User::class,
            'creado_por_user_id'
        );
    }

    public function actualizadoPor()
    {
        return $this->belongsTo(
            User::class,
            'actualizado_por_user_id'
        );
    }

    /**
     * Vuelos registrados para la operación.
     */
    public function vuelos()
    {
        return $this->hasMany(
            VueloReserva::class,
            'operacion_viaje_id'
        )->orderBy(
            'fecha_hora_salida'
        );
    }

    /**
     * Alojamientos registrados para la operación.
     */
    public function alojamientos()
    {
        return $this->hasMany(
            AlojamientoReserva::class,
            'operacion_viaje_id'
        )->orderBy(
            'fecha_hora_entrada'
        );
    }

    /**
     * Guías registrados para la operación.
     */
    public function guias()
    {
        return $this->hasMany(
            GuiaReserva::class,
            'operacion_viaje_id'
        )->orderBy(
            'fecha_inicio'
        );
    }

    /**
     * Todas las tareas sincronizadas desde el itinerario,
     * incluyendo las que dejaron de estar vigentes.
     */
    public function tareas()
    {
        return $this->hasMany(
            TareaOperacionViaje::class,
            'operacion_viaje_id'
        )
            ->orderBy('dia')
            ->orderBy('id');
    }

    /**
     * Tareas vigentes que actualmente afectan el progreso.
     */
    public function tareasVigentes()
    {
        return $this->hasMany(
            TareaOperacionViaje::class,
            'operacion_viaje_id'
        )
            ->where(
                'vigente',
                true
            )
            ->orderBy('dia')
            ->orderBy('id');
    }

    /**
     * Gestiones genéricas de trenes, traslados,
     * entradas, alimentación, seguros y otros servicios.
     */
    public function gestionesOperativas()
    {
        return $this->hasMany(
            GestionOperativa::class,
            'operacion_viaje_id'
        )
            ->orderBy('fecha_hora_inicio')
            ->orderBy('id');
    }

    /**
     * Gestiones que continúan activas.
     */
    public function gestionesOperativasActivas()
    {
        return $this->hasMany(
            GestionOperativa::class,
            'operacion_viaje_id'
        )
            ->where(
                'estado',
                '!=',
                GestionOperativa::ESTADO_CANCELADO
            )
            ->orderBy('fecha_hora_inicio')
            ->orderBy('id');
    }

    /**
     * Indica si existe al menos una gestión genérica pendiente.
     */
    public function tieneGestionesPendientes(): bool
    {
        return $this->gestionesOperativas()
            ->whereIn(
                'estado',
                [
                    GestionOperativa::ESTADO_PENDIENTE,
                    GestionOperativa::ESTADO_EN_PROCESO,
                ]
            )
            ->exists();
    }

    /**
     * Indica si el expediente fue completado.
     */
    public function estaCompleta(): bool
    {
        return in_array(
            $this->estado,
            [
                self::ESTADO_COMPLETO,
                self::ESTADO_NOTIFICADO,
            ],
            true
        );
    }

    /**
     * Indica si la documentación ya fue enviada al cliente.
     */
    public function fueNotificada(): bool
    {
        return $this->estado ===
            self::ESTADO_NOTIFICADO;
    }
}