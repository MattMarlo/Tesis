<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    public function test_el_admin_puede_acceder_al_modulo_de_usuarios(): void
    {
        $admin = new User([
            'nombres' => 'Admin',
            'apellidos' => 'Test',
            'email' => 'admin@test.com',
            'telefono' => '3000000000',
            'documento' => '1000000000',
            'rol' => User::ROL_ADMIN,
            'password' => 'password123',
        ]);

        $response = $this->actingAs($admin)->get('/usuarios');

        $response->assertOk();
    }

    public function test_el_agente_no_puede_acceder_al_modulo_de_usuarios(): void
    {
        $agente = new User([
            'nombres' => 'Agente',
            'apellidos' => 'Test',
            'email' => 'agente@test.com',
            'telefono' => '3000000001',
            'documento' => '1000000001',
            'rol' => User::ROL_AGENTE,
            'password' => 'password123',
        ]);

        $response = $this->actingAs($agente)->get('/usuarios');

        $response->assertForbidden();
    }
}
