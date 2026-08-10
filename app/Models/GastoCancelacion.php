<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GastoCancelacion extends Model
{
    public const ESTADO_PENDIENTE =
        'pendiente';

    public const ESTADO_APROBADO =
        'aprobado';

    public const ESTADO_RECHAZADO =
        'rechazado';

    public const ESTADO_ANULADO =
        'anulado';

    protected $table =
        'gastos_cancelacion';

    protected $fillable = [
        'reserva_id',
        'registrado_por_user_id',
        'revisado_por_user_id',
        'proveedor',
        'concepto',
        'monto',
        'numero_documento',
        'fecha_documento',
        'archivo_path',
        'archivo_nombre_original',
        'archivo_mime',
        'archivo_tamano',
        'archivo_hash',
        'estado',
        'observaciones',
        'motivo_revision',
        'revisado_at',
    ];

    protected function casts(): array
    {
        return [
            'monto' =>
                'decimal:2',

            'fecha_documento' =>
                'date',

            'archivo_tamano' =>
                'integer',

            'revisado_at' =>
                'datetime',
        ];
    }

    public function reserva()
    {
        return $this->belongsTo(
            Reserva::class,
            'reserva_id'
        );
    }

    public function registradoPor()
    {
        return $this->belongsTo(
            User::class,
            'registrado_por_user_id'
        );
    }

    public function revisadoPor()
    {
        return $this->belongsTo(
            User::class,
            'revisado_por_user_id'
        );
    }

    public function scopePendientes(
        $consulta
    ) {
        return $consulta->where(
            'estado',
            self::ESTADO_PENDIENTE
        );
    }

    public function scopeAprobados(
        $consulta
    ) {
        return $consulta->where(
            'estado',
            self::ESTADO_APROBADO
        );
    }

    public function estaPendiente(): bool
    {
        return $this->estado ===
            self::ESTADO_PENDIENTE;
    }

    public function estaAprobado(): bool
    {
        return $this->estado ===
            self::ESTADO_APROBADO;
    }

    public function estaRechazado(): bool
    {
        return $this->estado ===
            self::ESTADO_RECHAZADO;
    }

    public function estaAnulado(): bool
    {
        return $this->estado ===
            self::ESTADO_ANULADO;
    }

    public function getTamanoLegibleAttribute(): string
    {
        $bytes = max(
            0,
            (int) $this->archivo_tamano
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