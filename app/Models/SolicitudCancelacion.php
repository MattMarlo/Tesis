<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudCancelacion extends Model
{
    /*
     * Persona o entidad que origina
     * la solicitud.
     */
    public const SOLICITANTE_CLIENTE =
        'cliente';

    public const SOLICITANTE_AGENCIA =
        'agencia';

    public const SOLICITANTE_PROVEEDOR =
        'proveedor';

    public const SOLICITANTE_SISTEMA =
        'sistema';

    /*
     * Tipos de cancelación manual.
     */
    public const TIPO_DECISION_CLIENTE =
        'decision_cliente';

    public const TIPO_FUERZA_MAYOR =
        'fuerza_mayor';

    public const TIPO_RESPONSABILIDAD_AGENCIA =
        'responsabilidad_agencia';

    public const TIPO_PROBLEMA_PROVEEDOR =
        'problema_proveedor';

    public const TIPO_CAMBIO_VIAJE =
        'cambio_viaje';

    public const TIPO_OTRO =
        'otro';

    /*
     * Canales por los que puede recibirse
     * una solicitud.
     */
    public const CANAL_PRESENCIAL =
        'presencial';

    public const CANAL_LLAMADA =
        'llamada';

    public const CANAL_WHATSAPP =
        'whatsapp';

    public const CANAL_CORREO =
        'correo';

    public const CANAL_OTRO =
        'otro';

    /*
     * Estados de la solicitud.
     */
    public const ESTADO_PENDIENTE =
        'pendiente';

    public const ESTADO_APROBADA =
        'aprobada';

    public const ESTADO_RECHAZADA =
        'rechazada';

    public const ESTADO_ANULADA =
        'anulada';

    protected $table =
        'solicitudes_cancelacion';

    protected $fillable = [
        'reserva_id',
        'solicitado_por_user_id',
        'revisado_por_user_id',
        'solicitante',
        'tipo_cancelacion',
        'motivo',
        'canal_solicitud',
        'referencia_comunicacion',
        'evidencia_path',
        'evidencia_nombre_original',
        'evidencia_mime',
        'evidencia_tamano',
        'evidencia_hash',
        'monto_pagado_solicitud',
        'moneda',
        'estado_cobranza_anterior',
        'estado',
        'observaciones_internas',
        'motivo_revision',
        'solicitado_at',
        'revisado_at',
        'anulado_por_user_id',
        'anulado_at',
    ];

    protected function casts(): array
    {
        return [
            'monto_pagado_solicitud' =>
                'decimal:2',

            'evidencia_tamano' =>
                'integer',

            'solicitado_at' =>
                'datetime',

            'revisado_at' =>
                'datetime',

            'anulado_at' =>
                'datetime',
        ];
    }

    /*
     * Reserva relacionada con la solicitud.
     */
    public function reserva(): BelongsTo
    {
        return $this->belongsTo(
            Reserva::class,
            'reserva_id'
        );
    }

    /*
     * Usuario que registra la solicitud.
     */
    public function solicitadoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'solicitado_por_user_id'
        );
    }

    /*
     * Administrador que aprueba
     * o rechaza la solicitud.
     */
    public function revisadoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'revisado_por_user_id'
        );
    }

    /*
     * Usuario que anula la solicitud.
     */
    public function anuladoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'anulado_por_user_id'
        );
    }

    /*
     * Filtros reutilizables.
     */
    public function scopePendientes(
        Builder $consulta
    ): Builder {
        return $consulta->where(
            'estado',
            self::ESTADO_PENDIENTE
        );
    }

    public function scopeAprobadas(
        Builder $consulta
    ): Builder {
        return $consulta->where(
            'estado',
            self::ESTADO_APROBADA
        );
    }

    public function scopeRechazadas(
        Builder $consulta
    ): Builder {
        return $consulta->where(
            'estado',
            self::ESTADO_RECHAZADA
        );
    }

    public function scopeDeReserva(
        Builder $consulta,
        int $reservaId
    ): Builder {
        return $consulta->where(
            'reserva_id',
            $reservaId
        );
    }

    /*
     * Consultas de estado.
     */
    public function estaPendiente(): bool
    {
        return $this->estado ===
            self::ESTADO_PENDIENTE;
    }

    public function estaAprobada(): bool
    {
        return $this->estado ===
            self::ESTADO_APROBADA;
    }

    public function estaRechazada(): bool
    {
        return $this->estado ===
            self::ESTADO_RECHAZADA;
    }

    public function estaAnulada(): bool
    {
        return $this->estado ===
            self::ESTADO_ANULADA;
    }

    /*
     * Fuerza mayor requiere evidencia.
     */
    public function requiereEvidencia(): bool
    {
        return $this->tipo_cancelacion ===
            self::TIPO_FUERZA_MAYOR;
    }

    public function tieneEvidencia(): bool
    {
        return !empty(
            $this->evidencia_path
        );
    }

    /*
     * Etiquetas para la interfaz.
     */
    public function getTipoLegibleAttribute(): string
    {
        return match (
            $this->tipo_cancelacion
        ) {
            self::TIPO_DECISION_CLIENTE =>
                'Decisión del cliente',

            self::TIPO_FUERZA_MAYOR =>
                'Fuerza mayor',

            self::TIPO_RESPONSABILIDAD_AGENCIA =>
                'Responsabilidad de la agencia',

            self::TIPO_PROBLEMA_PROVEEDOR =>
                'Problema con proveedor',

            self::TIPO_CAMBIO_VIAJE =>
                'Cambio o cancelación del viaje',

            default =>
                'Otro motivo',
        };
    }

    public function getCanalLegibleAttribute(): string
    {
        return match (
            $this->canal_solicitud
        ) {
            self::CANAL_PRESENCIAL =>
                'Presencial',

            self::CANAL_LLAMADA =>
                'Llamada telefónica',

            self::CANAL_WHATSAPP =>
                'WhatsApp',

            self::CANAL_CORREO =>
                'Correo electrónico',

            default =>
                'Otro canal',
        };
    }

    public function getEstadoLegibleAttribute(): string
    {
        return match (
            $this->estado
        ) {
            self::ESTADO_PENDIENTE =>
                'Pendiente de revisión',

            self::ESTADO_APROBADA =>
                'Aprobada',

            self::ESTADO_RECHAZADA =>
                'Rechazada',

            self::ESTADO_ANULADA =>
                'Anulada',

            default =>
                ucfirst(
                    (string) $this->estado
                ),
        };
    }

    /*
     * Tamaño de la evidencia para
     * mostrarlo en la interfaz.
     */
    public function getTamanoEvidenciaLegibleAttribute(): string
    {
        $bytes = max(
            0,
            (int) $this->evidencia_tamano
        );

        if ($bytes >= 1048576) {
            return number_format(
                $bytes / 1048576,
                2
            ) . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format(
                $bytes / 1024,
                2
            ) . ' KB';
        }

        return $bytes . ' bytes';
    }
}