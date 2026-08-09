<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VueloReserva extends Model
{
    public const TRAMO_IDA =
        'ida';

    public const TRAMO_REGRESO =
        'regreso';

    public const TRAMO_CONEXION =
        'conexion';

    public const ESTADO_CONFIRMADO =
        'confirmado';

    public const ESTADO_PENDIENTE =
        'pendiente';

    public const ESTADO_CANCELADO =
        'cancelado';

    public const TRAMOS_PERMITIDOS = [
        self::TRAMO_IDA,
        self::TRAMO_REGRESO,
        self::TRAMO_CONEXION,
    ];

    public const ESTADOS_PERMITIDOS = [
        self::ESTADO_CONFIRMADO,
        self::ESTADO_PENDIENTE,
        self::ESTADO_CANCELADO,
    ];

    protected $table =
        'vuelos_reserva';

    protected $fillable = [
        'operacion_viaje_id',
        'tipo_tramo',
        'aerolinea',
        'numero_vuelo',
        'ciudad_origen',
        'aeropuerto_origen',
        'ciudad_destino',
        'aeropuerto_destino',
        'fecha_hora_salida',
        'fecha_hora_llegada',
        'terminal_salida',
        'terminal_llegada',
        'localizador_reserva',
        'equipaje_incluido',
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
            'fecha_hora_salida' =>
                'datetime',

            'fecha_hora_llegada' =>
                'datetime',

            'fecha_compra' =>
                'date',

            'costo_total' =>
                'decimal:2',
        ];
    }

    /**
     * Operación a la que pertenece el vuelo.
     */
    public function operacion()
    {
        return $this->belongsTo(
            OperacionViaje::class,
            'operacion_viaje_id'
        );
    }

    /**
     * Boletos individuales emitidos para los viajeros.
     */
    public function boletos()
    {
        return $this->hasMany(
            BoletoVuelo::class,
            'vuelo_reserva_id'
        );
    }

    /**
     * Tareas del itinerario vinculadas a este vuelo.
     *
     * Permite que una tarea contextual abra directamente
     * el vuelo que le corresponde.
     */
    public function tareas()
    {
        return $this->morphMany(
            TareaOperacionViaje::class,
            'gestionable'
        );
    }

    /**
     * Boletos que ya fueron emitidos.
     */
    public function boletosEmitidos()
    {
        return $this->hasMany(
            BoletoVuelo::class,
            'vuelo_reserva_id'
        )->where(
            'estado_emision',
            BoletoVuelo::ESTADO_EMITIDO
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
     * Indica si todos los viajeros esperados tienen
     * un boleto emitido para este vuelo.
     */
    public function tieneBoletosEmitidosPara(
        int $cantidadViajeros
    ): bool {
        if ($cantidadViajeros < 1) {
            return false;
        }

        return $this->boletosEmitidos()
            ->count() >= $cantidadViajeros;
    }

    /**
     * Indica si el vuelo está listo operativamente.
     */
    public function estaListoPara(
        int $cantidadViajeros
    ): bool {
        return $this->estaConfirmado()
            && $this->tieneBoletosEmitidosPara(
                $cantidadViajeros
            );
    }

    /**
     * Nombre legible del tipo de tramo.
     */
    public function etiquetaTramo(): string
    {
        return match ($this->tipo_tramo) {
            self::TRAMO_IDA =>
                'Ida',

            self::TRAMO_REGRESO =>
                'Regreso',

            self::TRAMO_CONEXION =>
                'Conexión',

            default =>
                'Sin clasificar',
        };
    }
}