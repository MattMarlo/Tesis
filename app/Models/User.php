<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    /*
     * Roles.
     */
    public const ROL_ADMIN =
        'admin';

    public const ROL_AGENTE =
        'agente';

    /*
     * Estados.
     */
    public const ESTADO_ACTIVO =
        'activo';

    public const ESTADO_INACTIVO =
        'inactivo';

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
            'email_verified_at' =>
                'datetime',

            'password' =>
                'hashed',
        ];
    }

    /*
     * Comprobaciones del usuario.
     */
    public function isAdmin(): bool
    {
        return $this->rol ===
            self::ROL_ADMIN;
    }

    public function isAgente(): bool
    {
        return $this->rol ===
            self::ROL_AGENTE;
    }

    public function estaActivo(): bool
    {
        return $this->estado ===
            self::ESTADO_ACTIVO;
    }

    /*
     * Control general de permisos.
     */
    public function hasPermission(
        string $permiso
    ): bool {
        if (!$this->estaActivo()) {
            return false;
        }

        if ($this->isAdmin()) {
            return true;
        }

        /*
         * Los agentes no pueden acceder
         * a la administración de usuarios.
         */
        if (
            str_starts_with(
                $permiso,
                'usuarios.'
            )
        ) {
            return false;
        }

        return $this->isAgente();
    }

    /*
     * Reservas creadas por el usuario.
     */
    public function reservas()
    {
        return $this->hasMany(
            Reserva::class,
            'user_id'
        );
    }

    /*
     * Pagos registrados por el usuario.
     */
    public function pagos()
    {
        return $this->hasMany(
            Pago::class,
            'user_id'
        );
    }

    /*
     * Solicitudes de cancelación registradas
     * por este usuario.
     *
     * El usuario puede ser administrador
     * o agente.
     */
    public function solicitudesCancelacionRegistradas()
    {
        return $this->hasMany(
            SolicitudCancelacion::class,
            'solicitado_por_user_id'
        );
    }

    /*
     * Solicitudes aprobadas o rechazadas
     * por este usuario.
     *
     * Normalmente corresponde a un
     * administrador.
     */
    public function solicitudesCancelacionRevisadas()
    {
        return $this->hasMany(
            SolicitudCancelacion::class,
            'revisado_por_user_id'
        );
    }

    /*
     * Solicitudes anuladas por este usuario
     * antes de ser revisadas.
     */
    public function solicitudesCancelacionAnuladas()
    {
        return $this->hasMany(
            SolicitudCancelacion::class,
            'anulado_por_user_id'
        );
    }
}