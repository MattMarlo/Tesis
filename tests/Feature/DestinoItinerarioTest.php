<?php

namespace Tests\Feature;

use App\Models\Destino;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class DestinoItinerarioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $usuario = User::create([
            'nombres' => 'Administrador',
            'apellidos' => 'Pruebas',
            'email' => 'admin.destinos@example.com',
            'telefono' => '0999999999',
            'documento' => 'ADMIN-DESTINOS',
            'rol' => User::ROL_ADMIN,
            'estado' => User::ESTADO_ACTIVO,
            'password' => 'password',
        ]);

        $this->actingAs($usuario);
    }

    public function test_registra_itinerario_con_actividades_y_gestion(): void
    {
        $respuesta = $this->post(
            route('destinos.store'),
            $this->datosValidos()
        );

        $respuesta
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('destinos'));

        $this->assertDatabaseCount('destinos', 1);

        $destino = Destino::firstOrFail();
        $itinerario = $destino->itinerario;

        $this->assertCount(2, $itinerario);

        $this->assertSame(
            1,
            $itinerario[0]['dia']
        );

        $this->assertSame(
            2,
            $itinerario[1]['dia']
        );

        $this->assertCount(
            2,
            $itinerario[0]['actividades']
        );

        $actividadGestionable =
            $itinerario[0]['actividades'][0];

        $this->assertTrue(
            Str::isUuid(
                $actividadGestionable['uuid']
            )
        );

        $this->assertSame(
            'Traslado al hotel',
            $actividadGestionable['nombre']
        );

        $this->assertSame(
            '08:00',
            $actividadGestionable['hora_inicio']
        );

        $this->assertSame(
            '09:30',
            $actividadGestionable['hora_fin']
        );

        $this->assertTrue(
            $actividadGestionable['requiere_gestion']
        );

        $this->assertSame(
            'reserva',
            $actividadGestionable['tipo_gestion']
        );

        $actividadInformativa =
            $itinerario[0]['actividades'][1];

        $this->assertFalse(
            $actividadInformativa['requiere_gestion']
        );

        $this->assertNull(
            $actividadInformativa['tipo_gestion']
        );

        Storage::disk('public')->assertExists(
            $destino->imagen
        );
    }

    public function test_rechaza_hora_final_anterior_o_igual_a_inicial(): void
    {
        $itinerario = $this->itinerarioValido();

        $itinerario[0]
            ['actividades'][0]
            ['hora_inicio'] = '10:00';

        $itinerario[0]
            ['actividades'][0]
            ['hora_fin'] = '09:00';

        $respuesta = $this->post(
            route('destinos.store'),
            $this->datosValidos([
                'itinerario' => $itinerario,
            ])
        );

        $respuesta->assertSessionHasErrors(
            'itinerario.0.actividades.0.hora_fin'
        );

        $this->assertDatabaseCount(
            'destinos',
            0
        );
    }

    public function test_exige_tipo_cuando_actividad_requiere_gestion(): void
    {
        $itinerario = $this->itinerarioValido();

        $itinerario[0]
            ['actividades'][0]
            ['requiere_gestion'] = '1';

        $itinerario[0]
            ['actividades'][0]
            ['tipo_gestion'] = '';

        $respuesta = $this->post(
            route('destinos.store'),
            $this->datosValidos([
                'itinerario' => $itinerario,
            ])
        );

        $respuesta->assertSessionHasErrors(
            'itinerario.0.actividades.0.tipo_gestion'
        );

        $this->assertDatabaseCount(
            'destinos',
            0
        );
    }

    public function test_rechaza_dia_que_supera_duracion_del_paquete(): void
    {
        $itinerario = $this->itinerarioValido();

        $itinerario[1]['dia'] = 3;

        $respuesta = $this->post(
            route('destinos.store'),
            $this->datosValidos([
                'dias' => 2,
                'itinerario' => $itinerario,
            ])
        );

        $respuesta->assertSessionHasErrors(
            'itinerario.1.dia'
        );

        $this->assertDatabaseCount(
            'destinos',
            0
        );
    }

    public function test_genera_slug_unico_para_nombres_repetidos(): void
    {
        $this->post(
            route('destinos.store'),
            $this->datosValidos()
        )->assertSessionHasNoErrors();

        $this->post(
            route('destinos.store'),
            $this->datosValidos()
        )->assertSessionHasNoErrors();

        $this->assertDatabaseHas(
            'destinos',
            [
                'slug' => 'panama-aereo-2027',
            ]
        );

        $this->assertDatabaseHas(
            'destinos',
            [
                'slug' =>
                    'panama-aereo-2027-2',
            ]
        );
    }

    public function test_actualiza_itinerario_sin_eliminar_imagen_actual(): void
    {
        $this->post(
            route('destinos.store'),
            $this->datosValidos()
        )->assertSessionHasNoErrors();

        $destino = Destino::firstOrFail();
        $imagenAnterior = $destino->imagen;

        $uuid =
            '550e8400-e29b-41d4-a716-446655440000';

        $itinerario = $this->itinerarioValido();

        $itinerario[0]
            ['actividades'][0]
            ['uuid'] = $uuid;

        $itinerario[0]
            ['actividades'][0]
            ['nombre'] =
                'Traslado actualizado';

        $datos = $this->datosValidos([
            'nombre_paquete' =>
                'Panamá actualizado',

            'itinerario' =>
                $itinerario,
        ]);

        unset($datos['imagen']);

        $respuesta = $this->put(
            route(
                'destinos.update',
                $destino->id
            ),
            $datos
        );

        $respuesta
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('destinos'));

        $destino->refresh();

        $this->assertSame(
            $imagenAnterior,
            $destino->imagen
        );

        $this->assertSame(
            $uuid,
            $destino
                ->itinerario[0]
                ['actividades'][0]
                ['uuid']
        );

        $this->assertSame(
            'Traslado actualizado',
            $destino
                ->itinerario[0]
                ['actividades'][0]
                ['nombre']
        );

        Storage::disk('public')->assertExists(
            $imagenAnterior
        );
    }

    public function test_detalle_publico_solo_muestra_publicados(): void
    {
        $this->post(
            route('destinos.store'),
            $this->datosValidos()
        )->assertSessionHasNoErrors();

        $destino = Destino::firstOrFail();

        $this->get(
            route(
                'paquetes.detalle',
                $destino->slug
            )
        )->assertOk();

        $destino->update([
            'estado_publicacion' =>
                'borrador',
        ]);

        $this->get(
            route(
                'paquetes.detalle',
                $destino->slug
            )
        )->assertNotFound();
    }

    private function datosValidos(
        array $cambios = []
    ): array {
        return array_merge(
            [
                'nombre_paquete' =>
                    'Panamá aéreo 2027',

                'etiqueta' =>
                    'Oferta aérea',

                'pais' =>
                    'Panamá',

                'ciudad_destino' =>
                    'Ciudad de Panamá',

                'categoria' =>
                    'Cultural',

                'descripcion_corta' =>
                    'Paquete aéreo para conocer Ciudad de Panamá.',

                'descripcion' =>
                    'Incluye vuelos, alojamiento y recorridos turísticos.',

                'ciudad_salida' =>
                    'Quito',

                'fecha_salida' => now()
                    ->addMonths(3)
                    ->format('Y-m-d'),

                'fecha_regreso' => now()
                    ->addMonths(3)
                    ->addDays(4)
                    ->format('Y-m-d'),

                'precio' =>
                    '1200.00',

                'moneda' =>
                    'USD',

                'precio_promocional' =>
                    '1100.00',

                'dias' =>
                    2,

                'noches' =>
                    1,

                'aerolinea' =>
                    'Copa Airlines',

                'hotel' =>
                    'Hotel Panamá',

                'capacidad' =>
                    20,

                'incluye' => [
                    'Boleto aéreo de ida y vuelta',
                    'Alojamiento',
                ],

                'no_incluye' => [
                    'Gastos personales',
                ],

                'itinerario' =>
                    $this->itinerarioValido(),

                'condiciones' =>
                    'Aplican términos y condiciones.',

                'estado_publicacion' =>
                    'publicado',

                'destacado' =>
                    '1',

                'imagen' =>
                    $this->imagenFalsa(),
            ],
            $cambios
        );
    }

    private function itinerarioValido(): array
    {
        return [
            [
                'dia' => 1,

                'titulo' =>
                    'Llegada a Panamá',

                'descripcion' =>
                    'Recepción y traslado al alojamiento.',

                'actividades' => [
                    [
                        'uuid' =>
                            null,

                        'nombre' =>
                            'Traslado al hotel',

                        'descripcion' =>
                            'Recepción en el aeropuerto.',

                        'hora_inicio' =>
                            '08:00',

                        'hora_fin' =>
                            '09:30',

                        'ubicacion' =>
                            'Aeropuerto Internacional de Tocumen',

                        'requiere_gestion' =>
                            '1',

                        'tipo_gestion' =>
                            'reserva',
                    ],
                    [
                        'uuid' =>
                            null,

                        'nombre' =>
                            'Tiempo libre',

                        'descripcion' =>
                            null,

                        'hora_inicio' =>
                            null,

                        'hora_fin' =>
                            null,

                        'ubicacion' =>
                            null,

                        'requiere_gestion' =>
                            '0',

                        /*
                         * El controlador debe
                         * convertir este valor a null
                         * porque no requiere gestión.
                         */
                        'tipo_gestion' =>
                            'otro',
                    ],
                ],
            ],
            [
                'dia' => 2,

                'titulo' =>
                    'Recorrido por la ciudad',

                'descripcion' =>
                    'Visita a los principales atractivos.',

                'actividades' => [
                    [
                        'uuid' =>
                            null,

                        'nombre' =>
                            'Visita al Canal de Panamá',

                        'descripcion' =>
                            'Recorrido por el centro de visitantes.',

                        'hora_inicio' =>
                            '10:00',

                        'hora_fin' =>
                            '12:00',

                        'ubicacion' =>
                            'Esclusas de Miraflores',

                        'requiere_gestion' =>
                            '1',

                        'tipo_gestion' =>
                            'entrada',
                    ],
                ],
            ],
        ];
    }

    /**
     * Genera una imagen PNG real sin utilizar GD.
     */
    private function imagenFalsa(): UploadedFile
    {
        $imagenBase64 =
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwC'
            . 'AAAAC0lEQVR42mP8/x8AAusB9Y9ZpK0AAAAASUVORK5CYII=';

        return UploadedFile::fake()
            ->createWithContent(
                'panama.png',
                base64_decode($imagenBase64)
            );
    }
}