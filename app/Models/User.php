<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Reserva;
use App\Models\Pago;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'nombres',
        'apellidos',
        'email',
        'telefono',
        'documento',
        'rol',
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

    // 🔥 ROLES
    const ROL_ADMIN = 'admin';
    const ROL_AGENTE = 'agente';

   
    public function reservas(){
       return $this->hasMany(Reserva::class, 'user_id');
    }

    
    public function pagos(){
        return $this->hasMany(Pago::class, 'users_id');
    }
    
}