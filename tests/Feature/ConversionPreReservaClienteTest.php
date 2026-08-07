<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Destino;
use App\Models\PreReserva;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversionPreReservaClienteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::create([
            'nombres' => 'Administrador',
            'apellidos' => 'Pruebas',
            'email' => 'conversion@example.com',
            'telefono' => '0998765432',
            'documento' => 'ADMIN-CONVERSION',
            'rol' => User::ROL_ADMIN,
            'estado' => User::ESTADO_ACTIVO,
            'password' => 'password',
        ]));
    }

    public function test_si_no_existe_documento_precarga_los_datos_de_n8n(): void
    {
        [$destino, $preReserva] = $this->preReserva();

        $respuesta = $this->post(route('prereservas.convertir', $preReserva));

        $respuesta->assertRedirect(route('clientes.create', [
            'prereserva_id' => $preReserva->id,
            'destino_id' => $destino->id,
        ]));

        $this->get($respuesta->headers->get('Location'))
            ->assertOk()
            ->assertSee('value="Maria"', false)
            ->assertSee('value="Perez"', false)
            ->assertSee('value="PA123456"', false)
            ->assertSee('value="maria@example.com"', false)
            ->assertSee('value="0998765432"', false)
            ->assertSee('name="prereserva_id"', false);
    }

    public function test_al_registrar_cliente_continua_hacia_la_reserva(): void
    {
        [$destino, $preReserva] = $this->preReserva();

        $respuesta = $this->post(route('clientes.store'), [
            'prereserva_id' => $preReserva->id,
            'nombres' => 'Maria',
            'apellidos' => 'Perez',
            'tipo_documento' => Cliente::DOCUMENTO_PASAPORTE,
            'documento' => 'PA123456',
            'fecha_nacimiento' => now()->subYears(30)->format('Y-m-d'),
            'nacionalidad' => 'Ecuador',
            'fecha_caducidad_documento' => now()->addYears(3)->format('Y-m-d'),
            'email' => 'maria@example.com',
            'telefono' => '0998765432',
            'estado' => Cliente::ESTADO_ACTIVO,
        ]);

        $cliente = Cliente::firstOrFail();
        $respuesta->assertRedirect(route('reservas_individual.create', [
            'cliente_id' => $cliente->id,
            'destino_id' => $destino->id,
            'prereserva_id' => $preReserva->id,
        ]));
    }

    public function test_documento_existente_convierte_directamente_a_reserva(): void
    {
        [$destino, $preReserva] = $this->preReserva();
        $cliente = Cliente::create([
            'nombres' => 'Maria', 'apellidos' => 'Perez',
            'tipo_documento' => Cliente::DOCUMENTO_PASAPORTE,
            'documento' => 'PA123456',
            'fecha_nacimiento' => now()->subYears(30),
            'nacionalidad' => 'Ecuador',
            'fecha_caducidad_documento' => now()->addYears(3),
            'email' => 'otro-correo@example.com',
            'telefono' => '0987654320',
            'estado' => Cliente::ESTADO_ACTIVO,
        ]);

        $this->post(route('prereservas.convertir', $preReserva))
            ->assertRedirect(route('reservas_individual.create', [
                'cliente_id' => $cliente->id,
                'destino_id' => $destino->id,
                'prereserva_id' => $preReserva->id,
            ]));
    }

    private function preReserva(): array
    {
        $destino = Destino::create([
            'nombre_paquete' => 'Cartagena', 'slug' => 'cartagena-conversion',
            'etiqueta' => 'Oferta',
            'pais' => 'Colombia', 'ciudad_destino' => 'Cartagena',
            'categoria' => 'Playa', 'descripcion_corta' => 'Viaje',
            'descripcion' => 'Viaje de prueba', 'ciudad_salida' => 'Quito',
            'fecha_salida' => now()->addYear(), 'fecha_regreso' => now()->addYear()->addDays(5),
            'precio' => 800, 'moneda' => 'USD', 'dias' => 5, 'noches' => 4,
            'capacidad' => 20, 'incluye' => [], 'no_incluye' => [],
            'itinerario' => [], 'estado_publicacion' => 'publicado', 'destacado' => false,
        ]);

        $preReserva = PreReserva::create([
            'cliente_nombre' => 'Maria Perez', 'email' => 'maria@example.com',
            'telefono' => '0998765432', 'cedula' => 'PA123456',
            'destino' => $destino->nombre_paquete, 'destino_id' => $destino->id,
            'fecha_viaje' => $destino->fecha_salida, 'cantidad_personas' => 1,
            'fecha_reserva' => now(), 'origen' => PreReserva::ORIGEN_TELEGRAM,
            'estado' => PreReserva::ESTADO_PENDIENTE, 'tipo_reserva' => 'individual',
        ]);

        $preReserva->integrantes()->create([
            'nombres' => 'Maria', 'apellidos' => 'Perez',
            'tipo_documento' => 'pasaporte', 'documento' => 'PA123456',
            'fecha_nacimiento' => now()->subYears(30),
            'fecha_caducidad_documento' => now()->addYears(3),
            'nacionalidad' => 'Ecuatoriana', 'email' => 'maria@example.com',
            'telefono' => '0998765432', 'es_lider' => true,
            'edad_al_viajar' => 31, 'categoria_tarifa' => 'adulto',
            'porcentaje_tarifa' => 100, 'precio_calculado' => 800,
        ]);

        return [$destino, $preReserva];
    }
}
