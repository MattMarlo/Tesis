<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    public const ESTADO_ACTIVO = 'activo';
    public const ESTADO_INACTIVO = 'inactivo';

    public const DOCUMENTO_CEDULA = 'cedula';
    public const DOCUMENTO_PASAPORTE = 'pasaporte';

    protected $table = 'clientes';

    protected $fillable = [
        'nombres',
        'apellidos',
        'tipo_documento',
        'documento',
        'fecha_nacimiento',
        'nacionalidad',
        'fecha_caducidad_documento',
        'email',
        'telefono',
        'contacto_emergencia',
        'telefono_emergencia',
        'estado',
        'archivo',
    ];

    protected $attributes = [
        'estado' => self::ESTADO_ACTIVO,
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'fecha_caducidad_documento' => 'date',
        ];
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim($this->nombres . ' ' . $this->apellidos);
    }

    public function estaActivo(): bool
    {
        return $this->estado === self::ESTADO_ACTIVO;
    }

    public function scopeActivos($query)
    {
        return $query->where('estado', self::ESTADO_ACTIVO);
    }

    public function reservas()
    {
        return $this->hasMany(Reserva::class, 'cliente_id');
    }

    public function grupos()
    {
        return $this->belongsToMany(
            Grupo::class,
            'grupos_clientes',
            'cliente_id',
            'grupo_id'
        )
            ->withPivot('monto_asignado', 'es_lider')
            ->withTimestamps();
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'cliente_id');
    }
}