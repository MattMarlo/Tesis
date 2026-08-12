<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registro_normaliza_y_guarda_datos_validos(): void
    {
        $admin = $this->crearAdministrador();

        $respuesta = $this->actingAs($admin)->post(
            route('usuarios.store'),
            [
                'nombres' => '  María   José  ',
                'apellidos' => '  Pérez  López ',
                'email' => '  AGENTE@EXAMPLE.COM ',
                'telefono' => '+593 (99) 123-4567',
                'documento' => ' ec 123-45 ',
                'rol' => User::ROL_AGENTE,
                'estado' => User::ESTADO_ACTIVO,
                'password' => 'Clave1234',
                'password_confirmation' => 'Clave1234',
            ]
        );

        $respuesta->assertRedirect(route('usuarios'));
        $respuesta->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'nombres' => 'María José',
            'apellidos' => 'Pérez López',
            'email' => 'agente@example.com',
            'telefono' => '+593 (99) 123-4567',
            'documento' => 'EC123-45',
            'rol' => User::ROL_AGENTE,
            'estado' => User::ESTADO_ACTIVO,
        ]);
    }

    public function test_registro_rechaza_formatos_y_opciones_invalidas(): void
    {
        $admin = $this->crearAdministrador();

        $respuesta = $this->actingAs($admin)
            ->from(route('usuarios.create'))
            ->post(route('usuarios.store'), [
                'nombres' => '---',
                'apellidos' => "''",
                'email' => 'correo-invalido',
                'telefono' => '+++++++',
                'documento' => '-----',
                'rol' => 'supervisor',
                'estado' => 'bloqueado',
                'password' => 'sololetras',
                'password_confirmation' => 'sololetras',
            ]);

        $respuesta->assertRedirect(route('usuarios.create'));
        $respuesta->assertSessionHasErrors([
            'nombres',
            'apellidos',
            'email',
            'telefono',
            'documento',
            'rol',
            'estado',
            'password',
        ]);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_registro_rechaza_correo_y_documento_duplicados_normalizados(): void
    {
        $admin = $this->crearAdministrador([
            'email' => 'admin@example.com',
            'documento' => 'ADMIN-001',
        ]);

        $respuesta = $this->actingAs($admin)->post(
            route('usuarios.store'),
            [
                'nombres' => 'Otro',
                'apellidos' => 'Usuario',
                'email' => ' ADMIN@EXAMPLE.COM ',
                'telefono' => '0991234567',
                'documento' => ' admin-001 ',
                'rol' => User::ROL_AGENTE,
                'estado' => User::ESTADO_ACTIVO,
                'password' => 'Clave1234',
                'password_confirmation' => 'Clave1234',
            ]
        );

        $respuesta->assertSessionHasErrors([
            'email',
            'documento',
        ]);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_actualizacion_permite_conservar_correo_y_documento_propios(): void
    {
        $admin = $this->crearAdministrador();

        $respuesta = $this->actingAs($admin)->put(
            route('usuarios.update', $admin->id),
            [
                'nombres' => 'Administrador',
                'apellidos' => 'Principal',
                'email' => strtoupper($admin->email),
                'telefono' => '0991234567',
                'documento' => strtolower($admin->documento),
                'rol' => User::ROL_ADMIN,
                'estado' => User::ESTADO_ACTIVO,
                'password' => '',
                'password_confirmation' => '',
            ]
        );

        $respuesta->assertRedirect(route('usuarios'));
        $respuesta->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'nombres' => 'Administrador',
            'apellidos' => 'Principal',
            'email' => $admin->email,
            'documento' => $admin->documento,
        ]);
    }

    private function crearAdministrador(array $datos = []): User
    {
        return User::query()->create(array_merge([
            'nombres' => 'Admin',
            'apellidos' => 'Principal',
            'email' => 'admin@example.com',
            'telefono' => '0991234567',
            'documento' => 'ADMIN-001',
            'rol' => User::ROL_ADMIN,
            'estado' => User::ESTADO_ACTIVO,
            'password' => 'Clave1234',
        ], $datos));
    }
}
