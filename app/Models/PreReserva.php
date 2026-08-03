<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreReserva extends Model
{
    public const ORIGEN_TELEGRAM =
        'telegram_bot';

    public const ESTADO_PENDIENTE =
        'pendiente_contacto';

    public const ESTADO_CONTACTADO =
        'contactado';

    public const ESTADO_CONVERTIDA =
        'convertida';

    public const ESTADO_DESCARTADA =
        'perdida';

    protected $table =
        'pre_reservas';

    protected $fillable = [
        'cliente_nombre',
        'email',
        'destino',
        'destino_id',
        'telefono',
        'cedula',
        'fecha_viaje',
        'cantidad_personas',
        'fecha_reserva',
        'origen',
        'telegram_chat_id',
        'referencia_externa',
        'estado',
        'fecha_contacto',
        'fecha_descarte',
        'observaciones',
        'user_id',
        'reserva_id',
        'tipo_reserva',
        'tipo_grupo',
        'nombre_grupo',
        'precio_estimado',
        'moneda',
        'acepta_condiciones',
        'confirmada_por_cliente_at',
    ];

    protected function casts(): array
    {
        return [
            'fecha_viaje' => 'date',

            'fecha_reserva' => 'datetime',

            'cantidad_personas' => 'integer',

            'fecha_contacto' => 'datetime',

            'fecha_descarte' => 'datetime',
            'precio_estimado' => 'decimal:2',
            'acepta_condiciones' => 'boolean',
            'confirmada_por_cliente_at' => 'datetime',
        ];
    }

    public function integrantes()
    {
        return $this->hasMany(PreReservaIntegrante::class, 'pre_reserva_id');
    }

    public function user()
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }

    public function reserva()
    {
        return $this->belongsTo(
            Reserva::class,
            'reserva_id'
        );
    }

    public function destinoRelacionado()
    {
        return $this->belongsTo(
            Destino::class,
            'destino_id'
        );
    }

    public function estaConvertida(): bool
    {
        return
            $this->estado ===
                self::ESTADO_CONVERTIDA ||
            ! empty($this->reserva_id);
    }

    public function estaDescartada(): bool
    {
        return $this->estado ===
            self::ESTADO_DESCARTADA;
    }

    public function puedeGestionarse(): bool
    {
        return
            ! $this->estaConvertida() &&
            ! $this->estaDescartada();
    }

    public function scopePendientes(
        $consulta
    ) {
        return $consulta->whereIn(
            'estado',
            [
                self::ESTADO_PENDIENTE,
                self::ESTADO_CONTACTADO,
            ]
        );
    }
}
