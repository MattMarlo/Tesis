<?php

namespace Tests\Feature;

use App\Models\BoletoVuelo;
use App\Models\Cliente;
use App\Models\Destino;
use App\Models\OperacionViaje;
use App\Models\Reserva;
use App\Models\TareaOperacionViaje;
use App\Models\User;
use App\Models\VueloReserva;
use App\Services\SincronizarTareasItinerarioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GestionBoletosVueloPaginaTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    private Cliente $cliente;

    private Destino $destino;

    private Reserva $reserva;

    private OperacionViaje $operacion;

    private TareaOperacionViaje $tarea;

    private string $actividadUuid =
        '55555555-5555-4555-8555-555555555555';

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = User::create([
            'nombres' =>
                'Agente',

            'apellidos' =>
                'Vuelos',

            'email' =>
                'agente-vuelos@example.com',

            'telefono' =>
                '0999999801',

            'documento' =>
                'USR-VUELOS-001',

            'rol' =>
                User::ROL_ADMIN,

            'estado' =>
                User::ESTADO_ACTIVO,

            'password' =>
                'password',
        ]);

        $this->cliente = Cliente::create([
            'nombres' =>
                'Viajero',

            'apellidos' =>
                'Principal',

            'tipo_documento' =>
                Cliente::DOCUMENTO_PASAPORTE,

            'documento' =>
                'PASS-VUELO-001',

            'fecha_nacimiento' =>
                '1990-05-10',

            'nacionalidad' =>
                'Ecuatoriana',

            'email' =>
                'viajero-vuelo@example.com',

            'telefono' =>
                '0999999802',

            'estado' =>
                Cliente::ESTADO_ACTIVO,
        ]);

        $this->destino = Destino::create([
            'nombre_paquete' =>
                'Perú con vuelo contextual',

            'slug' =>
                'peru-vuelo-contextual',

            'etiqueta' =>
                'Internacional',

            'pais' =>
                'Perú',

            'ciudad_destino' =>
                'Lima',

            'categoria' =>
                'Cultural',

            'descripcion_corta' =>
                'Paquete para probar vuelos vinculados.',

            'descripcion' =>
                'Viaje internacional desde Quito hacia Lima.',

            'ciudad_salida' =>
                'Quito',

            'fecha_salida' =>
                '2026-11-15',

            'fecha_regreso' =>
                '2026-11-21',

            'precio' =>
                1500,

            'moneda' =>
                'USD',

            'dias' =>
                7,

            'noches' =>
                6,

            'capacidad' =>
                20,

            'incluye' => [
                'Transporte aéreo',
            ],

            'no_incluye' => [],

            'itinerario' => [
                [
                    'dia' =>
                        1,

                    'titulo' =>
                        'Llegada a Lima',

                    'descripcion' =>
                        'Primer día del viaje.',

                    'actividades' => [
                        [
                            'uuid' =>
                                $this->actividadUuid,

                            'nombre' =>
                                'Vuelo Quito - Lima',

                            'descripcion' =>
                                'Vuelo internacional desde Quito hacia Lima.',

                            'hora_inicio' =>
                                '06:00',

                            'hora_fin' =>
                                '08:15',

                            'ubicacion' =>
                                'Quito - Lima',

                            'requiere_gestion' =>
                                true,

                            'tipo_gestion' =>
                                TareaOperacionViaje::TIPO_VUELO,
                        ],
                    ],
                ],
            ],

            'estado_publicacion' =>
                'publicado',

            'destacado' =>
                false,
        ]);

        $this->reserva = Reserva::create([
            'codigo_reserva' =>
                'RES-VUELO-CONTEXTUAL-001',

            'cliente_id' =>
                $this->cliente->id,

            'destino_id' =>
                $this->destino->id,

            'user_id' =>
                $this->usuario->id,

            'tipo' =>
                Reserva::TIPO_INDIVIDUAL,

            'fecha_reserva' =>
                '2026-08-10',

            'fecha_viaje' =>
                '2026-11-15',

            'precio_total_viaje' =>
                1500,

            'moneda' =>
                'USD',

            'cantidad_viajeros' =>
                1,

            'edad_viajero' =>
                36,

            'categoria_tarifa' =>
                Reserva::TARIFA_ADULTO,

            'estado' =>
                Reserva::ESTADO_CONFIRMADA,

            'estado_pago' =>
                Reserva::PAGO_PENDIENTE,
        ]);

        $this->operacion = OperacionViaje::create([
            'reserva_id' =>
                $this->reserva->id,

            'estado' =>
                OperacionViaje::ESTADO_PREPARACION,

            'creado_por_user_id' =>
                $this->usuario->id,

            'actualizado_por_user_id' =>
                $this->usuario->id,
        ]);

        app(
            SincronizarTareasItinerarioService::class
        )->sincronizar(
            $this->operacion
        );

        $this->tarea = TareaOperacionViaje::query()
            ->where(
                'operacion_viaje_id',
                $this->operacion->id
            )
            ->where(
                'actividad_uuid',
                $this->actividadUuid
            )
            ->firstOrFail();
    }

    public function test_crear_vuelo_desde_tarea_lo_vincula_y_deja_en_proceso(): void
    {
        $respuesta = $this
            ->actingAs($this->usuario)
            ->post(
                route(
                    'operaciones.vuelos.store',
                    $this->operacion
                ),
                $this->datosVuelo([
                    'tarea_id' =>
                        $this->tarea->id,
                ])
            );

        $respuesta
            ->assertSessionHasNoErrors()
            ->assertSessionHas(
                'success'
            );

        $vuelo = VueloReserva::firstOrFail();

        $tareaActualizada =
            $this->tarea->fresh([
                'gestionable',
            ]);

        $this->assertSame(
            $vuelo->id,
            $tareaActualizada->gestionable_id
        );

        $this->assertInstanceOf(
            VueloReserva::class,
            $tareaActualizada->gestionable
        );

        $this->assertSame(
            TareaOperacionViaje::ESTADO_EN_PROCESO,
            $tareaActualizada->estado
        );

        $this->assertNull(
            $tareaActualizada->completada_at
        );
    }

    public function test_emitir_boleto_completa_tarea_y_eliminarlo_la_reabre(): void
    {
        config([
            'services.n8n.flight_ticket_notification_url' =>
                'https://n8n.example.test/webhook/boleto-avion-emitido',
        ]);

        Http::fake([
            '*' => Http::response([], 500),
        ]);

        $this
            ->actingAs($this->usuario)
            ->post(
                route(
                    'operaciones.vuelos.store',
                    $this->operacion
                ),
                $this->datosVuelo([
                    'tarea_id' =>
                        $this->tarea->id,
                ])
            )
            ->assertSessionHasNoErrors();

        $vuelo = VueloReserva::firstOrFail();

        $respuestaBoleto = $this
            ->actingAs($this->usuario)
            ->post(
                route(
                    'operaciones.boletos.store',
                    $vuelo
                ),
                [
                    'cliente_id' =>
                        $this->cliente->id,

                    'viajero_reserva_id' =>
                        null,

                    'numero_boleto' =>
                        'r1223',

                    'asiento' =>
                        '12A',

                    'clase' =>
                        'Económica',

                    'estado_emision' =>
                        BoletoVuelo::ESTADO_EMITIDO,

                    'observaciones' =>
                        'Boleto emitido para el titular.',
                ]
            );

        $respuestaBoleto
            ->assertSessionHasNoErrors()
            ->assertSessionHas(
                'success'
            );

        $boleto = BoletoVuelo::firstOrFail();

        Http::assertSent(
            function (HttpRequest $request) use (
                $boleto
            ): bool {
                return $request->url() === config(
                    'services.n8n.flight_ticket_notification_url'
                )
                    && $request['event'] ===
                        'boleto.avion.emitido'
                    && (int) data_get(
                        $request->data(),
                        'data.boleto_id'
                    ) === $boleto->id
                    && data_get(
                        $request->data(),
                        'data.numero_boleto'
                    ) === 'r1223'
                    && data_get(
                        $request->data(),
                        'data.email'
                    ) === $this->cliente->email;
            }
        );

        $this->actingAs($this->usuario)
            ->post(
                route(
                    'operaciones.boletos.store',
                    $vuelo
                ),
                [
                    'cliente_id' => $this->cliente->id,
                    'numero_boleto' => 'r1223',
                    'asiento' => '12A',
                    'clase' => 'Económica',
                    'estado_emision' =>
                        BoletoVuelo::ESTADO_EMITIDO,
                    'observaciones' =>
                        'Boleto emitido para el titular.',
                ]
            )
            ->assertSessionHasNoErrors();

        Http::assertSentCount(1);

        $tareaCompletada =
            $this->tarea->fresh();

        $this->assertSame(
            TareaOperacionViaje::ESTADO_COMPLETADA,
            $tareaCompletada->estado
        );

        $this->assertNotNull(
            $tareaCompletada->completada_at
        );

        $this->assertSame(
            $this->usuario->id,
            $tareaCompletada->completada_por_user_id
        );

        $respuestaEliminar = $this
            ->actingAs($this->usuario)
            ->delete(
                route(
                    'operaciones.boletos.destroy',
                    $boleto
                )
            );

        $respuestaEliminar
            ->assertSessionHasNoErrors()
            ->assertSessionHas(
                'success'
            );

        $this->assertDatabaseMissing(
            'boletos_vuelo',
            [
                'id' =>
                    $boleto->id,
            ]
        );

        $tareaReabierta =
            $this->tarea->fresh();

        $this->assertSame(
            TareaOperacionViaje::ESTADO_EN_PROCESO,
            $tareaReabierta->estado
        );

        $this->assertNull(
            $tareaReabierta->completada_at
        );

        $this->assertNull(
            $tareaReabierta->completada_por_user_id
        );
    }

    public function test_no_permite_vincular_tarea_que_no_es_de_vuelo(): void
    {
        $this->tarea->update([
            'tipo_gestion' =>
                TareaOperacionViaje::TIPO_TRASLADO,
        ]);

        $respuesta = $this
            ->actingAs($this->usuario)
            ->post(
                route(
                    'operaciones.vuelos.store',
                    $this->operacion
                ),
                $this->datosVuelo([
                    'tarea_id' =>
                        $this->tarea->id,
                ])
            );

        $respuesta->assertSessionHasErrors([
            'tarea_id',
        ]);

        $this->assertDatabaseCount(
            'vuelos_reserva',
            0
        );

        $this->assertNull(
            $this->tarea
                ->fresh()
                ->gestionable_id
        );
    }

    public function test_no_permite_guardar_un_vuelo_cuya_salida_no_sea_anterior_a_la_llegada(): void
    {
        $respuesta = $this
            ->actingAs($this->usuario)
            ->post(
                route(
                    'operaciones.vuelos.store',
                    $this->operacion
                ),
                $this->datosVuelo([
                    'fecha_hora_salida' =>
                        '2026-11-15 10:00:00',
                    'fecha_hora_llegada' =>
                        '2026-11-15 08:00:00',
                ])
            );

        $respuesta->assertSessionHasErrors([
            'fecha_hora_llegada',
        ]);

        $this->assertDatabaseCount(
            'vuelos_reserva',
            0
        );
    }

    public function test_rechaza_datos_de_vuelo_con_formatos_incorrectos(): void
    {
        $respuesta = $this
            ->actingAs($this->usuario)
            ->post(
                route(
                    'operaciones.vuelos.store',
                    $this->operacion
                ),
                $this->datosVuelo([
                    'aerolinea' => '1a',
                    'numero_vuelo' => '1qqw1l',
                    'ciudad_origen' => '2',
                    'aeropuerto_origen' => '2',
                    'terminal_salida' => 'q',
                    'localizador_reserva' => 'A',
                    'proveedor' => '1',
                ])
            );

        $respuesta->assertSessionHasErrors([
            'aerolinea',
            'numero_vuelo',
            'ciudad_origen',
            'aeropuerto_origen',
            'terminal_salida',
            'localizador_reserva',
            'proveedor',
        ]);

        $this->assertDatabaseCount(
            'vuelos_reserva',
            0
        );
    }

    public function test_rechaza_datos_de_boleto_con_formatos_incorrectos(): void
    {
        $vuelo = $this->crearVueloVinculado();

        $respuesta = $this
            ->actingAs($this->usuario)
            ->post(
                route(
                    'operaciones.boletos.store',
                    $vuelo
                ),
                [
                    'cliente_id' => $this->cliente->id,
                    'numero_boleto' => '@',
                    'asiento' => 'ABC',
                    'clase' => '1',
                    'estado_emision' =>
                        BoletoVuelo::ESTADO_EMITIDO,
                    'observaciones' => 'x',
                ]
            );

        $respuesta->assertSessionHasErrors([
            'numero_boleto',
            'asiento',
            'clase',
            'observaciones',
        ]);

        $this->assertDatabaseCount(
            'boletos_vuelo',
            0
        );
    }

    public function test_rechaza_datos_de_hospedaje_con_formatos_y_fechas_incorrectos(): void
    {
        $respuesta = $this
            ->actingAs($this->usuario)
            ->post(
                route(
                    'operaciones.alojamientos.store',
                    $this->operacion
                ),
                [
                    'nombre_hotel' => '1',
                    'estado' => 'confirmado',
                    'ciudad' => '2',
                    'pais' => '3',
                    'fecha_hora_entrada' =>
                        '2026-11-17 15:00:00',
                    'fecha_hora_salida' =>
                        '2026-11-17 14:00:00',
                    'codigo_confirmacion' => 'A',
                    'tipo_habitacion' => '1',
                    'cantidad_habitaciones' => 0,
                    'telefono_hotel' => '1',
                    'moneda' => 'ABC',
                ]
            );

        $respuesta->assertSessionHasErrors([
            'nombre_hotel',
            'ciudad',
            'pais',
            'fecha_hora_salida',
            'codigo_confirmacion',
            'tipo_habitacion',
            'cantidad_habitaciones',
            'telefono_hotel',
            'moneda',
        ]);

        $this->assertDatabaseCount(
            'alojamientos_reserva',
            0
        );
    }

    public function test_pagina_muestra_vuelo_viajero_y_tarea_contextual(): void
    {
        $vuelo = $this->crearVueloVinculado();

        $respuesta = $this
            ->actingAs($this->usuario)
            ->get(
                route(
                    'operaciones.vuelos.boletos.index',
                    [
                        'operacion' =>
                            $this->operacion,

                        'vuelo' =>
                            $vuelo,

                        'tarea_id' =>
                            $this->tarea->id,
                    ]
                )
            );

        $respuesta
            ->assertOk()
            ->assertViewIs(
                'modules.operaciones.boletos.index'
            )
            ->assertViewHas(
                'operacion',
                fn ($operacion) =>
                    (int) $operacion->id ===
                    (int) $this->operacion->id
            )
            ->assertViewHas(
                'vuelo',
                fn ($vueloVista) =>
                    (int) $vueloVista->id ===
                    (int) $vuelo->id
            )
            ->assertViewHas(
                'tarea',
                fn ($tareaVista) =>
                    (int) $tareaVista->id ===
                    (int) $this->tarea->id
            )
            ->assertViewHas(
                'progresoVuelo',
                fn ($progresoVista) =>
                    (int) ($progresoVista['actual'] ?? -1) === 0 &&
                    (int) ($progresoVista['total'] ?? -1) === 1
            )
            ->assertSeeText(
                'Gestión de boletos'
            )
            ->assertSeeText(
                'Vuelo Quito - Lima'
            )
            ->assertSeeText(
                'Viajero Principal'
            )
            ->assertSeeText(
                'Asignar'
            );
    }

    public function test_expediente_enlaza_la_pagina_de_boletos_con_la_tarea(): void
    {
        $vuelo = $this->crearVueloVinculado();

        $urlGestion = route(
            'operaciones.vuelos.boletos.index',
            [
                'operacion' =>
                    $this->operacion,

                'vuelo' =>
                    $vuelo,

                'tarea_id' =>
                    $this->tarea->id,
            ]
        );

        $this
            ->actingAs($this->usuario)
            ->get(
                route(
                    'operaciones.show',
                    $this->reserva->id
                )
            )
            ->assertOk()
            ->assertSee(
                $urlGestion
            )
            ->assertSee(
                'id="tarea-itinerario-' .
                $this->tarea->id .
                '"',
                false
            );
    }

    public function test_pagina_refleja_boleto_emitido_y_asiento(): void
    {
        $vuelo = $this->crearVueloVinculado();

        $this
            ->actingAs($this->usuario)
            ->post(
                route(
                    'operaciones.boletos.store',
                    $vuelo
                ),
                [
                    'cliente_id' =>
                        $this->cliente->id,

                    'viajero_reserva_id' =>
                        null,

                    'numero_boleto' =>
                        'ETKT-PAGINA-001',

                    'asiento' =>
                        '14A',

                    'clase' =>
                        'Económica',

                    'estado_emision' =>
                        BoletoVuelo::ESTADO_EMITIDO,

                    'observaciones' =>
                        'Boleto verificado desde la página.',
                ]
            )
            ->assertSessionHasNoErrors();

        $this
            ->actingAs($this->usuario)
            ->get(
                route(
                    'operaciones.vuelos.boletos.index',
                    [
                        'operacion' =>
                            $this->operacion,

                        'vuelo' =>
                            $vuelo,

                        'tarea_id' =>
                            $this->tarea->id,
                    ]
                )
            )
            ->assertOk()
            ->assertViewHas(
                'progresoVuelo',
                fn ($progresoVista) =>
                    (int) ($progresoVista['actual'] ?? -1) === 1 &&
                    (int) ($progresoVista['total'] ?? -1) === 1
            )
            ->assertViewHas(
                'asientosAsignados',
                1
            )
            ->assertSeeText(
                'ETKT-PAGINA-001'
            )
            ->assertSeeText(
                '14A'
            )
            ->assertSeeText(
                'Emitido'
            )
            ->assertSeeText(
                'Editar'
            );
    }

    public function test_no_permite_consultar_vuelo_de_otra_operacion(): void
    {
        $vuelo = $this->crearVueloVinculado();

        $otroCliente = Cliente::create([
            'nombres' =>
                'Otro',

            'apellidos' =>
                'Viajero',

            'tipo_documento' =>
                Cliente::DOCUMENTO_PASAPORTE,

            'documento' =>
                'PASS-VUELO-OTRO',

            'fecha_nacimiento' =>
                '1992-03-15',

            'nacionalidad' =>
                'Ecuatoriana',

            'email' =>
                'otro-viajero@example.com',

            'telefono' =>
                '0999999803',

            'estado' =>
                Cliente::ESTADO_ACTIVO,
        ]);

        $otraReserva = Reserva::create([
            'codigo_reserva' =>
                'RES-VUELO-OTRA-002',

            'cliente_id' =>
                $otroCliente->id,

            'destino_id' =>
                $this->destino->id,

            'user_id' =>
                $this->usuario->id,

            'tipo' =>
                Reserva::TIPO_INDIVIDUAL,

            'fecha_reserva' =>
                '2026-08-10',

            'fecha_viaje' =>
                '2026-11-15',

            'precio_total_viaje' =>
                1500,

            'moneda' =>
                'USD',

            'cantidad_viajeros' =>
                1,

            'edad_viajero' =>
                34,

            'categoria_tarifa' =>
                Reserva::TARIFA_ADULTO,

            'estado' =>
                Reserva::ESTADO_CONFIRMADA,

            'estado_pago' =>
                Reserva::PAGO_PENDIENTE,
        ]);

        $otraOperacion = OperacionViaje::create([
            'reserva_id' =>
                $otraReserva->id,

            'estado' =>
                OperacionViaje::ESTADO_PREPARACION,

            'creado_por_user_id' =>
                $this->usuario->id,

            'actualizado_por_user_id' =>
                $this->usuario->id,
        ]);

        $this
            ->actingAs($this->usuario)
            ->get(
                route(
                    'operaciones.vuelos.boletos.index',
                    [
                        'operacion' =>
                            $otraOperacion,

                        'vuelo' =>
                            $vuelo,
                    ]
                )
            )
            ->assertNotFound();
    }

    public function test_usuario_no_autenticado_no_puede_abrir_pagina(): void
    {
        $vuelo = VueloReserva::create(
            $this->datosVuelo([
                'operacion_viaje_id' =>
                    $this->operacion->id,
            ])
        );

        $this->get(
            route(
                'operaciones.vuelos.boletos.index',
                [
                    'operacion' =>
                        $this->operacion,

                    'vuelo' =>
                        $vuelo,
                ]
            )
        )->assertRedirect(
            route('login')
        );
    }

    private function crearVueloVinculado(): VueloReserva
    {
        $this
            ->actingAs($this->usuario)
            ->post(
                route(
                    'operaciones.vuelos.store',
                    $this->operacion
                ),
                $this->datosVuelo([
                    'tarea_id' =>
                        $this->tarea->id,
                ])
            )
            ->assertSessionHasNoErrors();

        return VueloReserva::query()
            ->firstOrFail();
    }

    private function datosVuelo(
        array $cambios = []
    ): array {
        return array_merge([
            'tipo_tramo' =>
                VueloReserva::TRAMO_IDA,

            'aerolinea' =>
                'LATAM Airlines',

            'numero_vuelo' =>
                'LA1447',

            'ciudad_origen' =>
                'Quito',

            'aeropuerto_origen' =>
                'Aeropuerto Internacional Mariscal Sucre',

            'ciudad_destino' =>
                'Lima',

            'aeropuerto_destino' =>
                'Aeropuerto Internacional Jorge Chávez',

            'fecha_hora_salida' =>
                '2026-11-15 06:00:00',

            'fecha_hora_llegada' =>
                '2026-11-15 08:15:00',

            'terminal_salida' =>
                'Internacional',

            'terminal_llegada' =>
                'Internacional',

            'localizador_reserva' =>
                'PERU01',

            'equipaje_incluido' =>
                'Equipaje de mano y una maleta de 23 kg',

            'proveedor' =>
                'LATAM',

            'fecha_compra' =>
                '2026-08-10',

            'costo_total' =>
                450,

            'moneda' =>
                'USD',

            'estado' =>
                VueloReserva::ESTADO_CONFIRMADO,

            'observaciones' =>
                'Vuelo confirmado para el itinerario.',
        ], $cambios);
    }
}
