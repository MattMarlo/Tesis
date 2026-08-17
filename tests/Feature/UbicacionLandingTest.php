<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UbicacionLandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_landing_muestra_la_ubicacion_configurada(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Estamos ubicados en Salcedo')
            ->assertSee('Salcedo, Cotopaxi, Ecuador')
            ->assertSee(
                'https://maps.app.goo.gl/BcySuXQbntDDHPZY8',
                false
            )
            ->assertSee(
                'Passion%20Travel%2C%20Salcedo%2C%20Cotopaxi%2C%20Ecuador',
                false
            );
    }

    public function test_un_administrador_actualiza_la_ubicacion_de_la_landing(): void
    {
        $this->actingAs($this->crearUsuario(User::ROL_ADMIN));

        $this->get(route('ubicacion.edit'))
            ->assertOk()
            ->assertSee('Ubicación de la agencia')
            ->assertSee('Ubicación')
            ->assertSee('selectorMapaUbicacion')
            ->assertSee('se completarán automáticamente');

        $respuesta = $this->put(
            route('ubicacion.update'),
            [
                'localidad' => 'Quito',
                'direccion' => 'Av. Amazonas y Naciones Unidas, Quito',
                'consulta_mapa' => 'Passion Travel, Av. Amazonas, Quito, Ecuador',
                'enlace_mapa' => 'https://maps.app.goo.gl/ubicacion-pruebas',
                'latitud' => '-0.1806532',
                'longitud' => '-78.4678382',
            ]
        );

        $respuesta
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('ubicacion.edit'));

        $this->assertDatabaseHas(
            'configuracion_ubicacion',
            [
                'localidad' => 'Quito',
                'direccion' => 'Av. Amazonas y Naciones Unidas, Quito',
                'latitud' => '-0.1806532',
                'longitud' => '-78.4678382',
            ]
        );

        $this->get('/')
            ->assertOk()
            ->assertSee('Estamos ubicados en Quito')
            ->assertSee('Av. Amazonas y Naciones Unidas, Quito')
            ->assertSee(
                'destination=-0.1806532%2C-78.4678382',
                false
            )
            ->assertSee(
                'q=-0.1806532%2C-78.4678382',
                false
            );
    }

    public function test_un_agente_no_puede_administrar_la_ubicacion(): void
    {
        $this->actingAs($this->crearUsuario(User::ROL_AGENTE));

        $this->get(route('ubicacion.edit'))
            ->assertForbidden();

        $this->put(
            route('ubicacion.update'),
            [
                'localidad' => 'Quito',
                'direccion' => 'Quito, Ecuador',
                'consulta_mapa' => 'Quito, Ecuador',
                'enlace_mapa' => 'https://maps.app.goo.gl/ubicacion-agente',
                'latitud' => '-0.1806532',
                'longitud' => '-78.4678382',
            ]
        )->assertForbidden();

        $this->assertDatabaseHas(
            'configuracion_ubicacion',
            ['localidad' => 'Salcedo']
        );
    }

    private function crearUsuario(string $rol): User
    {
        return User::create([
            'nombres' => 'Usuario',
            'apellidos' => 'Pruebas',
            'email' => $rol.'@ubicacion.test',
            'telefono' => '0999999999',
            'documento' => 'UBICACION-'.$rol,
            'rol' => $rol,
            'estado' => User::ESTADO_ACTIVO,
            'password' => 'password',
        ]);
    }
}
