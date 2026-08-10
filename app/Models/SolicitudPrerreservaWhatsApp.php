<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudPrerreservaWhatsApp extends Model
{
    public const ESTADO_PENDIENTE = 'pendiente';

    protected $table =
        'solicitudes_prerreserva_whatsapp';

    protected $fillable = [
        'destino_id',
        'referencia_externa',
        'nombre_completo',
        'cedula',
        'correo',
        'telefono',
        'tipo_reserva',
        'cantidad_personas',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'destino_id' => 'integer',
            'cantidad_personas' => 'integer',
        ];
    }

    public function destino(): BelongsTo
    {
        return $this->belongsTo(Destino::class);
    }
}