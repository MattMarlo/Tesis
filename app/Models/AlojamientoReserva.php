<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlojamientoReserva extends Model
{
    public const ESTADO_CONFIRMADO =
        'confirmado';

    public const ESTADO_PENDIENTE =
        'pendiente';

    public const ESTADO_CANCELADO =
        'cancelado';

    public const ESTADOS_PERMITIDOS = [
        self::ESTADO_CONFIRMADO,
        self::ESTADO_PENDIENTE,
        self::ESTADO_CANCELADO,
    ];

    protected $table =
        'alojamientos_reserva';

    protected $fillable = [
        'operacion_viaje_id',
        'nombre_hotel',
        'ciudad',
        'pais',
        'direccion',
        'fecha_hora_entrada',
        'fecha_hora_salida',
        'codigo_confirmacion',
        'tipo_habitacion',
        'cantidad_habitaciones',
        'distribucion_habitaciones',
        'alimentacion_incluida',
        'telefono_hotel',
        'correo_hotel',
        'proveedor',
        'fecha_compra',
        'costo_total',
        'moneda',
        'estado',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha_hora_entrada' =>
                'datetime',

            'fecha_hora_salida' =>
                'datetime',

            'fecha_compra' =>
                'date',

            'cantidad_habitaciones' =>
                'integer',

            'costo_total' =>
                'decimal:2',
        ];
    }

    /**
     * Operación a la que pertenece el alojamiento.
     */
    public function operacion()
    {
        return $this->belongsTo(
            OperacionViaje::class,
            'operacion_viaje_id'
        );
    }

    /**
     * Habitaciones creadas dentro del alojamiento.
     */
    public function habitaciones()
    {
        return $this->hasMany(
            HabitacionAlojamiento::class,
            'alojamiento_reserva_id'
        );
    }

    /**
     * Distribución individual de viajeros en habitaciones.
     */
    public function asignacionesHabitacion()
    {
        return $this->hasMany(
            AsignacionHabitacion::class,
            'alojamiento_reserva_id'
        );
    }

    /**
     * Viajeros asignados al alojamiento.
     */
    public function viajeros()
    {
        return $this->belongsToMany(
            ViajeroReserva::class,
            'asignaciones_habitacion',
            'alojamiento_reserva_id',
            'viajero_reserva_id'
        )
            ->withPivot([
                'id',
                'habitacion_alojamiento_id',
                'cliente_id',
            ])
            ->withTimestamps();
    }

    /**
     * Tareas del itinerario vinculadas a este alojamiento.
     *
     * Permite reutilizar el mismo hotel cuando varias tareas
     * del itinerario corresponden a la misma estancia.
     */
    public function tareas()
    {
        return $this->morphMany(
            TareaOperacionViaje::class,
            'gestionable'
        );
    }

    public function estaConfirmado(): bool
    {
        return $this->estado ===
            self::ESTADO_CONFIRMADO;
    }

    public function estaPendiente(): bool
    {
        return $this->estado ===
            self::ESTADO_PENDIENTE;
    }

    public function estaCancelado(): bool
    {
        return $this->estado ===
            self::ESTADO_CANCELADO;
    }

    /**
     * Número de viajeros actuales con una habitación asignada.
     */
    public function cantidadViajerosAsignados(): int
    {
        $viajerosReserva = $this->asignacionesHabitacion()
            ->whereNotNull(
                'viajero_reserva_id'
            )
            ->distinct()
            ->count(
                'viajero_reserva_id'
            );

        $clientes = $this->asignacionesHabitacion()
            ->whereNotNull('cliente_id')
            ->distinct()
            ->count('cliente_id');

        return $viajerosReserva + $clientes;
    }

    /**
     * Verifica si todos los viajeros esperados tienen
     * una habitación asignada.
     */
    public function tieneHabitacionPara(
        int $cantidadViajeros
    ): bool {
        if ($cantidadViajeros < 1) {
            return false;
        }

        return $this->cantidadViajerosAsignados()
            >= $cantidadViajeros;
    }

    /**
     * El alojamiento queda listo cuando está confirmado
     * y todos los viajeros tienen habitación.
     */
    public function estaListoPara(
        int $cantidadViajeros
    ): bool {
        return $this->estaConfirmado()
            && $this->tieneHabitacionPara(
                $cantidadViajeros
            );
    }
}
