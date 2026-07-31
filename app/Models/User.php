<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    public const ROL_ADMIN = 'admin';
    public const ROL_AGENTE = 'agente';

    public const ESTADO_ACTIVO = 'activo';
    public const ESTADO_INACTIVO = 'inactivo';

    protected $fillable = [
        'nombres',
        'apellidos',
        'email',
        'telefono',
        'documento',
        'rol',
        'estado',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->rol === self::ROL_ADMIN;
    }

    public function isAgente(): bool
    {
        return $this->rol === self::ROL_AGENTE;
    }

    public function estaActivo(): bool
    {
        return $this->estado === self::ESTADO_ACTIVO;
    }

    public function hasPermission(string $permiso): bool
    {
        if (!$this->estaActivo()) {
            return false;
        }

        if ($this->isAdmin()) {
            return true;
        }

        /*
         * El equipo de trabajo no puede acceder a ninguna
         * función relacionada con la administración de usuarios.
         */
        if (str_starts_with($permiso, 'usuarios.')) {
            return false;
        }

        return $this->isAgente();
    }

    public function reservas()
    {
        return $this->hasMany(
            Reserva::class,
            'user_id'
        );
    }

    public function pagos()
    {
        return $this->hasMany(
            Pago::class,
            'user_id'
        );
    }
}