<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

class GuiaReserva extends Model
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
        'guias_reserva';

    protected $fillable = [
        'operacion_viaje_id',
        'nombre_completo',
        'empresa',
        'ciudad_servicio',
        'telefono',
        'correo',
        'idiomas',
        'fecha_inicio',
        'fecha_fin',
        'punto_encuentro',
        'fecha_hora_encuentro',
        'servicios_incluidos',
        'contacto_emergencia',
        'costo_total',
        'moneda',
        'estado',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' =>
                'date',

            'fecha_fin' =>
                'date',

            'fecha_hora_encuentro' =>
                'datetime',

            'costo_total' =>
                'decimal:2',
        ];
    }

    /**
     * Operación a la que pertenece la guía.
     */
    public function operacion()
    {
        return $this->belongsTo(
            OperacionViaje::class,
            'operacion_viaje_id'
        );
    }

    /**
     * Tareas del itinerario vinculadas a esta guía.
     *
     * Una misma guía puede encargarse de varias actividades
     * durante el viaje.
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
     * Verifica si la guía cubre una fecha determinada.
     */
    public function cubreFecha(
        CarbonInterface|string $fecha
    ): bool {
        $fechaEvaluada = $fecha instanceof CarbonInterface
            ? $fecha->copy()->startOfDay()
            : now()->parse($fecha)->startOfDay();

        if (
            $this->fecha_inicio &&
            $fechaEvaluada->lt(
                $this->fecha_inicio->copy()->startOfDay()
            )
        ) {
            return false;
        }

        if (
            $this->fecha_fin &&
            $fechaEvaluada->gt(
                $this->fecha_fin->copy()->endOfDay()
            )
        ) {
            return false;
        }

        return true;
    }

    /**
     * Una guía está lista cuando fue confirmada y tiene
     * los datos mínimos para contactar y encontrarla.
     */
    public function estaLista(): bool
    {
        return $this->estaConfirmado()
            && filled($this->nombre_completo)
            && filled($this->telefono)
            && filled($this->punto_encuentro);
    }

    /**
     * Nombre que se mostrará en los resúmenes operativos.
     */
    public function nombrePresentacion(): string
    {
        if (filled($this->empresa)) {
            return $this->nombre_completo
                . ' · '
                . $this->empresa;
        }

        return $this->nombre_completo;
    }
}