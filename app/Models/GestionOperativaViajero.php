<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class GestionOperativaViajero extends Model
{
    public const ESTADO_PENDIENTE =
        'pendiente';

    public const ESTADO_EN_PROCESO =
        'en_proceso';

    public const ESTADO_CONFIRMADO =
        'confirmado';

    public const ESTADO_CANCELADO =
        'cancelado';

    public const ESTADOS_PERMITIDOS = [
        self::ESTADO_PENDIENTE,
        self::ESTADO_EN_PROCESO,
        self::ESTADO_CONFIRMADO,
        self::ESTADO_CANCELADO,
    ];

    protected $table =
        'gestion_operativa_viajeros';

    protected $fillable = [
        'gestion_operativa_id',
        'viajero_reserva_id',
        'numero_documento',
        'asiento',
        'referencia_individual',
        'estado',
        'restricciones',
        'observaciones',
    ];

    /**
     * Gestión operativa a la que pertenece el detalle.
     */
    public function gestion()
    {
        return $this->belongsTo(
            GestionOperativa::class,
            'gestion_operativa_id'
        );
    }

    /**
     * Viajero al que pertenecen el boleto, asiento,
     * referencia o restricción.
     */
    public function viajero()
    {
        return $this->belongsTo(
            ViajeroReserva::class,
            'viajero_reserva_id'
        );
    }

    public function scopePendientes(
        Builder $consulta
    ): Builder {
        return $consulta->where(
            'estado',
            self::ESTADO_PENDIENTE
        );
    }

    public function scopeConfirmados(
        Builder $consulta
    ): Builder {
        return $consulta->where(
            'estado',
            self::ESTADO_CONFIRMADO
        );
    }

    public function scopeActivos(
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

    public function estaConfirmado(): bool
    {
        return $this->estado ===
            self::ESTADO_CONFIRMADO;
    }

    public function estaCancelado(): bool
    {
        return $this->estado ===
            self::ESTADO_CANCELADO;
    }

    /**
     * Un detalle está resuelto cuando fue confirmado
     * o cancelado justificadamente.
     */
    public function estaResuelto(): bool
    {
        return in_array(
            $this->estado,
            [
                self::ESTADO_CONFIRMADO,
                self::ESTADO_CANCELADO,
            ],
            true
        );
    }

    /**
     * Indica si existe algún identificador individual
     * para este viajero.
     */
    public function tieneReferenciaIndividual(): bool
    {
        return filled($this->numero_documento)
            || filled($this->referencia_individual);
    }
}