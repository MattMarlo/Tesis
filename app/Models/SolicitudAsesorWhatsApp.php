<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudAsesorWhatsApp extends Model
{
    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_ATENDIDA = 'atendida';

    protected $table = 'solicitud_asesor_whats_apps';

    protected $fillable = [
        'nombre',
        'telefono',
        'motivo',
        'estado',
        'mensaje_id',
        'atendido_por',
        'fecha_contacto',
    ];

    protected function casts(): array
    {
        return [
            'fecha_contacto' => 'datetime',
        ];
    }

    public function atendidoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'atendido_por'
        );
    }
}