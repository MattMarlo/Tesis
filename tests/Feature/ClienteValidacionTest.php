<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClienteValidacionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::create([
            'nombres' => 'Administrador',
            'apellidos' => 'Pruebas',
            'email' => 'admin@example.com',
            'telefono' => '0998765432',
            'documento' => 'ADMIN-CLIENTES',
            'rol' => User::ROL_ADMIN,
            'estado' => User::ESTADO_ACTIVO,
            'password' => 'password',
        ]));
    }

    public function test_exige_edad_entre_1_y_100_anos(): void
    {
        foreach ([now()->subMonths(11)->format('Y-m-d'), now()->subYears(101)->format('Y-m-d')] as $fecha) {
            $respuesta = $this->post(route('clientes.store'), $this->datosValidos([
                'fecha_nacimiento' => $fecha,
            ]));

            $respuesta->assertSessionHasErrors('fecha_nacimiento');
        }

        $this->assertDatabaseCount('clientes', 0);

        $this->post(route('clientes.store'), $this->datosValidos([
            'fecha_nacimiento' => now()->subYear()->format('Y-m-d'),
        ]))->assertRedirect(route('clientes'));
    }

    public function test_acepta_telefono_con_prefijo_internacional(): void
    {
        $this->post(route('clientes.store'), $this->datosValidos([
            'telefono' => '+593 99 876 5432',
        ]))->assertRedirect(route('clientes'));

        $this->assertDatabaseHas('clientes', [
            'telefono' => '+593998765432',
        ]);
    }

    public function test_rechaza_telefonos_secuenciales_repetidos_o_con_letras(): void
    {
        foreach (['1234567890', '1111111111', '+59399ABC5432'] as $telefono) {
            $this->post(route('clientes.store'), $this->datosValidos([
                'telefono' => $telefono,
            ]))->assertSessionHasErrors('telefono');
        }

        $this->assertDatabaseCount('clientes', 0);
    }

    public function test_rechaza_correo_con_dominio_no_valido(): void
    {
        $this->post(route('clientes.store'), $this->datosValidos([
            'email' => 'acostamarlon@g.com',
        ]))->assertSessionHasErrors('email');

        $this->assertDatabaseCount('clientes', 0);
    }

    public function test_nacionalidad_debe_ser_un_pais_de_la_lista(): void
    {
        $this->post(route('clientes.store'), $this->datosValidos([
            'nacionalidad' => 'Inventada',
        ]))->assertSessionHasErrors('nacionalidad');

        $this->assertDatabaseCount('clientes', 0);
    }

    public function test_permite_registrar_y_actualizar_con_datos_validos(): void
    {
        $this->post(route('clientes.store'), $this->datosValidos())
            ->assertRedirect(route('clientes'));

        $cliente = Cliente::firstOrFail();

        $this->put(route('clientes.update', $cliente), $this->datosValidos([
            'email' => 'cliente.actualizado@example.com',
            'nacionalidad' => 'Antigua y Barbuda',
        ]))->assertRedirect(route('clientes'));

        $this->assertDatabaseHas('clientes', [
            'id' => $cliente->id,
            'email' => 'cliente.actualizado@example.com',
            'nacionalidad' => 'Antigua y Barbuda',
        ]);
    }

    private function datosValidos(array $cambios = []): array
    {
        return array_merge([
            'nombres' => 'Marlon',
            'apellidos' => 'Acosta',
            'tipo_documento' => Cliente::DOCUMENTO_PASAPORTE,
            'documento' => 'AB123456',
            'fecha_nacimiento' => now()->subYears(30)->format('Y-m-d'),
            'nacionalidad' => 'Ecuador',
            'fecha_caducidad_documento' => now()->addYears(2)->format('Y-m-d'),
            'email' => 'marlon.acosta@example.com',
            'telefono' => '0998765432',
            'estado' => Cliente::ESTADO_ACTIVO,
        ], $cambios);
    }
}
