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

    /*
     * Tipos anteriores conservados por compatibilidad.
     */
    public const TIPO_RESERVA =
        'reserva';

    public const TIPO_ACTIVIDAD =
        'actividad';

    /*
     * Tipos especializados y genéricos actuales.
     */
    public const TIPO_VUELO =
        'vuelo';

    public const TIPO_ALOJAMIENTO =
        'alojamiento';

    public const TIPO_GUIA =
        'guia';

    public const TIPO_TREN =
        'tren';

    public const TIPO_TRASLADO =
        'traslado';

    public const TIPO_ENTRADA =
        'entrada';

    public const TIPO_ALIMENTACION =
        'alimentacion';

    public const TIPO_ACTIVIDAD_RESERVADA =
        'actividad_reservada';

    public const TIPO_SEGURO =
        'seguro';

    public const TIPO_OTRO =
        'otro';

    /**
     * Tipos disponibles al crear o editar un paquete.
     */
    public const TIPOS_SELECCIONABLES = [
        self::TIPO_VUELO,
        self::TIPO_ALOJAMIENTO,
        self::TIPO_GUIA,
        self::TIPO_TREN,
        self::TIPO_TRASLADO,
        self::TIPO_ENTRADA,
        self::TIPO_ALIMENTACION,
        self::TIPO_ACTIVIDAD_RESERVADA,
        self::TIPO_SEGURO,
        self::TIPO_OTRO,
    ];

    /**
     * Tipos anteriores que deben seguir siendo válidos para
     * evitar perder itinerarios ya existentes.
     */
    public const TIPOS_LEGACY = [
        self::TIPO_RESERVA,
        self::TIPO_ACTIVIDAD,
    ];

    /**
     * Únicas coordinaciones que pertenecen al módulo
     * Preparación de viajes.
     */
    public const TIPOS_PREPARACION = [
        self::TIPO_VUELO,
        self::TIPO_ALOJAMIENTO,
        self::TIPO_ALIMENTACION,
    ];

    public static function tiposPreparacion(): array
    {
        return self::TIPOS_PREPARACION;
    }

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
        'gestionable_type',
        'gestionable_id',
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

            'gestionable_id' =>
                'integer',

            'vigente' =>
                'boolean',

            'completada_at' =>
                'datetime',
        ];
    }

    /**
     * Devuelve todos los tipos aceptados por el sistema,
     * incluyendo los tipos anteriores.
     */
    public static function tiposPermitidos(): array
    {
        return array_values(
            array_unique([
                ...self::TIPOS_SELECCIONABLES,
                ...self::TIPOS_LEGACY,
            ])
        );
    }

    /**
     * Nombres legibles de los tipos de gestión.
     */
    public static function etiquetasTipoGestion(): array
    {
        return [
            self::TIPO_VUELO =>
                'Vuelo',

            self::TIPO_ALOJAMIENTO =>
                'Alojamiento',

            self::TIPO_GUIA =>
                'Guía',

            self::TIPO_TREN =>
                'Tren',

            self::TIPO_TRASLADO =>
                'Traslado',

            self::TIPO_ENTRADA =>
                'Entrada',

            self::TIPO_ALIMENTACION =>
                'Alimentación',

            self::TIPO_ACTIVIDAD_RESERVADA =>
                'Actividad reservada',

            self::TIPO_SEGURO =>
                'Seguro',

            self::TIPO_OTRO =>
                'Otro servicio',

            self::TIPO_RESERVA =>
                'Reserva anterior sin clasificar',

            self::TIPO_ACTIVIDAD =>
                'Actividad anterior sin clasificar',
        ];
    }

    /**
     * Texto del botón que deberá mostrar cada tarea.
     */
    public static function accionesContextuales(): array
    {
        return [
            self::TIPO_VUELO =>
                'Gestionar vuelo y boletos',

            self::TIPO_ALOJAMIENTO =>
                'Gestionar hotel y habitaciones',

            self::TIPO_GUIA =>
                'Gestionar guía',

            self::TIPO_TREN =>
                'Gestionar reserva y boletos',

            self::TIPO_TRASLADO =>
                'Gestionar transporte',

            self::TIPO_ENTRADA =>
                'Gestionar entradas',

            self::TIPO_ALIMENTACION =>
                'Gestionar alimentación',

            self::TIPO_ACTIVIDAD_RESERVADA =>
                'Gestionar actividad',

            self::TIPO_SEGURO =>
                'Gestionar seguro',

            self::TIPO_OTRO =>
                'Gestionar servicio',

            self::TIPO_RESERVA =>
                'Clasificar y gestionar reserva',

            self::TIPO_ACTIVIDAD =>
                'Clasificar y gestionar actividad',
        ];
    }

    /**
     * Expediente operativo al que pertenece la tarea.
     */
    public function operacion()
    {
        return $this->belongsTo(
            OperacionViaje::class,
            'operacion_viaje_id'
        );
    }

    /**
     * Registro especializado o genérico relacionado.
     *
     * Puede devolver:
     *
     * - VueloReserva
     * - AlojamientoReserva
     * - GuiaReserva
     * - GestionOperativa
     */
    public function gestionable()
    {
        return $this->morphTo();
    }

    /**
     * Usuario que completó u omitió la tarea.
     */
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

    public function scopeDelTipo(
        Builder $consulta,
        string $tipo
    ): Builder {
        return $consulta->where(
            'tipo_gestion',
            $tipo
        );
    }

    public function scopePendientes(
        Builder $consulta
    ): Builder {
        return $consulta->whereIn(
            'estado',
            [
                self::ESTADO_PENDIENTE,
                self::ESTADO_EN_PROCESO,
            ]
        );
    }

    /**
     * Nombre legible del tipo de gestión.
     */
    public function etiquetaTipoGestion(): string
    {
        return self::etiquetasTipoGestion()[
            $this->tipo_gestion
        ] ?? 'Otra gestión';
    }

    /**
     * Texto del botón contextual correspondiente.
     */
    public function accionContextual(): string
    {
        return self::accionesContextuales()[
            $this->tipo_gestion
        ] ?? 'Gestionar servicio';
    }

    /**
     * Indica si la tarea ya está relacionada con un registro.
     */
    public function tieneGestionVinculada(): bool
    {
        return filled($this->gestionable_type)
            && filled($this->gestionable_id);
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
