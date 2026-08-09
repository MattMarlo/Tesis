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
     * Tipos anteriores.
     *
     * Se conservan para no perder compatibilidad
     * con los itinerarios creados anteriormente.
     */
    public const TIPO_RESERVA =
        'reserva';

    public const TIPO_ACTIVIDAD =
        'actividad';

    /*
     * Tipos específicos actuales.
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

    /*
     * Estos son los tipos disponibles para actividades
     * nuevas en el formulario de paquetes.
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

    /*
     * Tipos que pueden existir en paquetes antiguos.
     */
    public const TIPOS_LEGACY = [
        self::TIPO_RESERVA,
        self::TIPO_ACTIVIDAD,
    ];

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

    /**
     * Devuelve todos los tipos aceptados por el servidor,
     * incluidos los valores anteriores.
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
     * Etiquetas visibles en formularios y tareas.
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
     * Texto que posteriormente utilizarán los botones
     * contextuales de cada tarea.
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

    public function nombreTipoGestion(): string
    {
        return self::etiquetasTipoGestion()[
            $this->tipo_gestion
        ] ?? 'Otra gestión';
    }

    public function accionContextual(): string
    {
        return self::accionesContextuales()[
            $this->tipo_gestion
        ] ?? 'Gestionar servicio';
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