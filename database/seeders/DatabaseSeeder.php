<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->create([
            'nombres' => 'Marlon',
            'apellidos' => 'Acosta',
            'email' => 'acostamarlon28@gmail.com',
            'telefono' => '0990000000',
            'documento' => 'ADMIN-001',
            'rol' => User::ROL_ADMIN,
            'estado' => User::ESTADO_ACTIVO,
            'email_verified_at' => now(),
            'password' => '12345678',
        ]);
    }
}
