<?php

namespace Tests\Feature;

use App\Models\Destino;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrerreservaPublicaTest extends TestCase
{
    use RefreshDatabase;

    public function test_detalle_muestra_boton_y_formulario_de_prerreserva(): void
    {
        $destino = $this->crearDestino();

        $this->get(route('paquetes.detalle', $destino->slug))
            ->assertOk()
            ->assertSee('id="irFormularioPrerreserva"', false)
            ->assertSee('id="formularioPrerreserva"', false)
            ->assertSee('id="prerreservaPublicaForm"', false)
            ->assertSee(
                route(
                    'paquetes.prerreserva.store',
                    ['destino' => $destino->slug]
                ),
                false
            );
    }

    public function test_registra_prerreserva_individual_desde_el_detalle(): void
    {
        $destino = $this->crearDestino();

        $respuesta = $this
            ->postJson(
                $this->ruta($destino),
                $this->datosValidos()
            );

        $respuesta
            ->assertCreated()
            ->assertJson([
                'success' => true,
                'duplicada' => false,
                'estado' => 'pendiente_contacto',
            ]);

        $this->assertDatabaseHas('pre_reservas', [
            'destino_id' => $destino->id,
            'cliente_nombre' => 'María Pruebas',
            'email' => 'maria@example.com',
            'telefono' => '0991234567',
            'cedula' => '1710034065',
            'cantidad_personas' => 1,
            'origen' => 'landing_page',
            'tipo_reserva' => 'individual',
            'acepta_condiciones' => 1,
        ]);

        $this->assertDatabaseCount(
            'pre_reserva_integrantes',
            0
        );
    }

    public function test_registra_grupal_solo_con_datos_del_titular(): void
    {
        $destino = $this->crearDestino([
            'capacidad' => 8,
        ]);

        $datos = $this->datosValidos([
            'tipo_reserva' => 'grupal',
            'cantidad_personas' => 5,
        ]);

        $this->postJson($this->ruta($destino), $datos)
            ->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('pre_reservas', [
            'destino_id' => $destino->id,
            'tipo_reserva' => 'grupal',
            'cantidad_personas' => 5,
            'cliente_nombre' => 'María Pruebas',
        ]);
    }

    public function test_valida_la_cantidad_segun_el_tipo_de_prerreserva(): void
    {
        $destino = $this->crearDestino();

        $this->postJson(
            $this->ruta($destino),
            $this->datosValidos([
                'tipo_reserva' => 'individual',
                'cantidad_personas' => 2,
            ])
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cantidad_personas');

        $this->postJson(
            $this->ruta($destino),
            $this->datosValidos([
                'tipo_reserva' => 'grupal',
                'cantidad_personas' => 1,
            ])
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cantidad_personas');
    }

    public function test_valida_cedula_y_celular_ecuatorianos(): void
    {
        $destino = $this->crearDestino();

        $this->postJson(
            $this->ruta($destino),
            $this->datosValidos([
                'cedula' => '0102030405',
                'telefono' => '12345',
            ])
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'cedula',
                'telefono',
            ]);
    }

    public function test_no_permite_solicitar_mas_personas_que_cupos(): void
    {
        $destino = $this->crearDestino([
            'capacidad' => 3,
        ]);

        $this->postJson(
            $this->ruta($destino),
            $this->datosValidos([
                'tipo_reserva' => 'grupal',
                'cantidad_personas' => 4,
            ])
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cantidad_personas');

        $this->assertDatabaseCount('pre_reservas', 0);
    }

    public function test_devuelve_prerreserva_activa_sin_duplicarla(): void
    {
        $destino = $this->crearDestino();
        $datos = $this->datosValidos();

        $primera = $this->postJson(
            $this->ruta($destino),
            $datos
        );

        $segunda = $this->postJson(
            $this->ruta($destino),
            $datos
        );

        $primera->assertCreated();

        $segunda
            ->assertOk()
            ->assertJson([
                'success' => true,
                'duplicada' => true,
            ])
            ->assertJsonPath(
                'pre_reserva_id',
                $primera->json('pre_reserva_id')
            );

        $this->assertDatabaseCount('pre_reservas', 1);
    }

    public function test_no_muestra_formulario_si_el_paquete_ya_no_admite_cupos(): void
    {
        $destino = $this->crearDestino([
            'capacidad' => 0,
        ]);

        $this->get(route('paquetes.detalle', $destino->slug))
            ->assertOk()
            ->assertSee('Prerreserva cerrada')
            ->assertDontSee('id="prerreservaPublicaForm"', false);
    }

    private function crearDestino(
        array $cambios = []
    ): Destino {
        return Destino::create(array_merge([
            'nombre_paquete' => 'Panamá de compras',
            'slug' => 'panama-de-compras',
            'etiqueta' => 'Oferta especial',
            'pais' => 'Panamá',
            'ciudad_destino' => 'Ciudad de Panamá',
            'categoria' => 'Compras',
            'descripcion_corta' => 'Un viaje de prueba.',
            'descripcion' => 'Descripción del paquete de prueba.',
            'ciudad_salida' => 'Quito',
            'fecha_salida' => today()->addMonths(3),
            'fecha_regreso' => today()->addMonths(3)->addDays(4),
            'precio' => 575,
            'moneda' => 'USD',
            'dias' => 5,
            'noches' => 4,
            'capacidad' => 10,
            'condiciones' => 'Documento vigente.',
            'estado_publicacion' => 'publicado',
            'destacado' => false,
            'imagen' => 'paquetes/prueba.jpg',
        ], $cambios));
    }

    private function datosValidos(
        array $cambios = []
    ): array {
        return array_merge([
            'tipo_reserva' => 'individual',
            'cantidad_personas' => 1,
            'nombre_completo' => 'María Pruebas',
            'cedula' => '1710034065',
            'correo' => 'MARIA@EXAMPLE.COM',
            'telefono' => '0991234567',
            'acepta_condiciones' => '1',
        ], $cambios);
    }

    private function ruta(Destino $destino): string
    {
        return route(
            'paquetes.prerreserva.store',
            ['destino' => $destino->slug]
        );
    }
}
