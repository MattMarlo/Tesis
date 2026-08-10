<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class GestionOperativa extends Model
{
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

    public const ESTADO_PENDIENTE =
        'pendiente';

    public const ESTADO_EN_PROCESO =
        'en_proceso';

    public const ESTADO_CONFIRMADO =
        'confirmado';

    public const ESTADO_CANCELADO =
        'cancelado';

    public const TIPOS_PERMITIDOS = [
        self::TIPO_TREN,
        self::TIPO_TRASLADO,
        self::TIPO_ENTRADA,
        self::TIPO_ALIMENTACION,
        self::TIPO_ACTIVIDAD_RESERVADA,
        self::TIPO_SEGURO,
        self::TIPO_OTRO,
    ];

    public const ESTADOS_PERMITIDOS = [
        self::ESTADO_PENDIENTE,
        self::ESTADO_EN_PROCESO,
        self::ESTADO_CONFIRMADO,
        self::ESTADO_CANCELADO,
    ];

    protected $table =
        'gestiones_operativas';

    protected $fillable = [
        'operacion_viaje_id',
        'tipo',
        'nombre',
        'proveedor',
        'contacto',
        'telefono',
        'correo',
        'fecha_hora_inicio',
        'fecha_hora_fin',
        'ubicacion_origen',
        'destino',
        'cantidad_viajeros',
        'capacidad',
        'referencia_confirmacion',
        'costo_total',
        'moneda',
        'estado',
        'archivo_comprobante',
        'observaciones',
        'datos_adicionales',
        'creado_por_user_id',
        'actualizado_por_user_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha_hora_inicio' =>
                'datetime',

            'fecha_hora_fin' =>
                'datetime',

            'cantidad_viajeros' =>
                'integer',

            'capacidad' =>
                'integer',

            'costo_total' =>
                'decimal:2',

            'datos_adicionales' =>
                'array',
        ];
    }

    /**
     * Operación o expediente al que pertenece.
     */
    public function operacion()
    {
        return $this->belongsTo(
            OperacionViaje::class,
            'operacion_viaje_id'
        );
    }

    /**
     * Detalles individuales de los viajeros incluidos.
     */
    public function detallesViajeros()
    {
        return $this->hasMany(
            GestionOperativaViajero::class,
            'gestion_operativa_id'
        );
    }

    /**
     * Viajeros incluidos en esta gestión.
     */
    public function viajeros()
    {
        return $this->belongsToMany(
            ViajeroReserva::class,
            'gestion_operativa_viajeros',
            'gestion_operativa_id',
            'viajero_reserva_id'
        )
            ->withPivot([
                'id',
                'numero_documento',
                'asiento',
                'referencia_individual',
                'estado',
                'restricciones',
                'observaciones',
            ])
            ->withTimestamps();
    }

    /**
     * Una misma gestión puede vincularse a varias tareas.
     *
     * Por ejemplo, el mismo servicio de traslado puede atender
     * varias actividades del itinerario.
     */
    public function tareas()
    {
        return $this->morphMany(
            TareaOperacionViaje::class,
            'gestionable'
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

    public function scopeDelTipo(
        Builder $consulta,
        string $tipo
    ): Builder {
        return $consulta->where(
            'tipo',
            $tipo
        );
    }

    public function scopeConfirmadas(
        Builder $consulta
    ): Builder {
        return $consulta->where(
            'estado',
            self::ESTADO_CONFIRMADO
        );
    }

    public function scopeActivas(
        Builder $consulta
    ): Builder {
        return $consulta->whereNot(
            'estado',
            self::ESTADO_CANCELADO
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

    public function estaConfirmada(): bool
    {
        return $this->estado ===
            self::ESTADO_CONFIRMADO;
    }

    public function estaCancelada(): bool
    {
        return $this->estado ===
            self::ESTADO_CANCELADO;
    }

    /**
     * Indica si el tipo normalmente requiere información
     * individual para cada viajero.
     */
    public function requiereDetalleIndividual(): bool
    {
        return in_array(
            $this->tipo,
            [
                self::TIPO_TREN,
                self::TIPO_ENTRADA,
                self::TIPO_ACTIVIDAD_RESERVADA,
                self::TIPO_SEGURO,
            ],
            true
        );
    }

    /**
     * Devuelve el nombre legible del tipo.
     */
    public function etiquetaTipo(): string
    {
        return TareaOperacionViaje::etiquetasTipoGestion()[
            $this->tipo
        ] ?? 'Otro servicio';
    }
}