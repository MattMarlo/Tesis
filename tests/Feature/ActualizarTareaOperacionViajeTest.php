<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Destino;
use App\Models\OperacionViaje;
use App\Models\Reserva;
use App\Models\TareaOperacionViaje;
use App\Models\User;
use App\Services\SincronizarTareasItinerarioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActualizarTareaOperacionViajeTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    private Cliente $cliente;

    private Destino $destino;

    private Reserva $reserva;

    private OperacionViaje $operacion;

    private TareaOperacionViaje $tarea;

    private string $actividadUuid =
        '44444444-4444-4444-8444-444444444444';

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = User::create([
            'nombres' =>
                'Agente',

            'apellidos' =>
                'Operaciones',

            'email' =>
                'agente-operaciones@example.com',

            'telefono' =>
                '0999999911',

            'documento' =>
                'USR-OPERACIONES-001',

            'rol' =>
                User::ROL_ADMIN,

            'estado' =>
                User::ESTADO_ACTIVO,

            'password' =>
                'password',
        ]);

        $this->cliente = Cliente::create([
            'nombres' =>
                'Cliente',

            'apellidos' =>
                'Operaciones',

            'tipo_documento' =>
                Cliente::DOCUMENTO_PASAPORTE,

            'email' =>
                'cliente-operaciones@example.com',

            'telefono' =>
                '0999999912',

            'documento' =>
                'CLI-OPERACIONES-001',

            'fecha_nacimiento' =>
                '1990-01-01',

            'nacionalidad' =>
                'Ecuatoriana',

            'estado' =>
                Cliente::ESTADO_ACTIVO,
        ]);

        $this->destino = Destino::create([
            'nombre_paquete' =>
                'Paquete de prueba HTTP',

            'slug' =>
                'paquete-prueba-tareas-http',

            'etiqueta' =>
                'Prueba',

            'pais' =>
                'Ecuador',

            'ciudad_destino' =>
                'Quito',

            'categoria' =>
                'Cultural',

            'descripcion_corta' =>
                'Paquete para probar la actualización de tareas.',

            'descripcion' =>
                'Descripción completa del paquete de prueba.',

            'ciudad_salida' =>
                'Guayaquil',

            'fecha_salida' =>
                '2026-12-01',

            'fecha_regreso' =>
                '2026-12-05',

            'precio' =>
                1000,

            'moneda' =>
                'USD',

            'dias' =>
                5,

            'noches' =>
                4,

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
                        'Visita cultural',

                    'descripcion' =>
                        'Primer día del recorrido.',

                    'actividades' => [
                        [
                            'uuid' =>
                                $this->actividadUuid,

                            'nombre' =>
                                'Comprar entradas',

                            'descripcion' =>
                                'Comprar entradas para el museo.',

                            'hora_inicio' =>
                                '09:00',

                            'hora_fin' =>
                                '11:00',

                            'ubicacion' =>
                                'Museo Nacional',

                            'requiere_gestion' =>
                                true,

                            'tipo_gestion' =>
                                TareaOperacionViaje::TIPO_ALIMENTACION,
                        ],
                    ],
                ],
            ],

            'estado_publicacion' =>
                'publicado',

            'destacado' =>
                false,
        ]);

        $this->reserva = $this->crearReserva(
            'RES-OPERACIONES-001'
        );

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

    public function test_usuario_autenticado_puede_completar_tarea(): void
    {
        $respuesta = $this
            ->actingAs($this->usuario)
            ->patch(
                route(
                    'operaciones.tareas.update',
                    [
                        'operacion' =>
                            $this->operacion->id,

                        'tarea' =>
                            $this->tarea->id,
                    ]
                ),
                [
                    'estado' =>
                        TareaOperacionViaje::ESTADO_COMPLETADA,

                    'observaciones' =>
                        'Entradas compradas y confirmadas.',
                ]
            );

        $respuesta
            ->assertSessionHasNoErrors()
            ->assertRedirect(
                route(
                    'operaciones.show',
                    [
                        'id' =>
                            $this->reserva->id,
                    ]
                )
            );

        $tareaActualizada =
            $this->tarea->fresh();

        $this->assertSame(
            TareaOperacionViaje::ESTADO_COMPLETADA,
            $tareaActualizada->estado
        );

        $this->assertSame(
            'Entradas compradas y confirmadas.',
            $tareaActualizada->observaciones
        );

        $this->assertNotNull(
            $tareaActualizada->completada_at
        );

        $this->assertSame(
            $this->usuario->id,
            $tareaActualizada->completada_por_user_id
        );
    }

    public function test_omitir_tarea_exige_una_justificacion(): void
    {
        $respuesta = $this
            ->actingAs($this->usuario)
            ->from(
                route(
                    'operaciones.show',
                    [
                        'id' =>
                            $this->reserva->id,
                    ]
                )
            )
            ->patch(
                route(
                    'operaciones.tareas.update',
                    [
                        'operacion' =>
                            $this->operacion->id,

                        'tarea' =>
                            $this->tarea->id,
                    ]
                ),
                [
                    'estado' =>
                        TareaOperacionViaje::ESTADO_OMITIDA,

                    'observaciones' =>
                        '',
                ]
            );

        $respuesta
            ->assertRedirect(
                route(
                    'operaciones.show',
                    [
                        'id' =>
                            $this->reserva->id,
                    ]
                )
            )
            ->assertSessionHasErrors([
                'observaciones',
            ]);

        $tareaSinCambios =
            $this->tarea->fresh();

        $this->assertSame(
            TareaOperacionViaje::ESTADO_PENDIENTE,
            $tareaSinCambios->estado
        );

        $this->assertNull(
            $tareaSinCambios->completada_at
        );

        $this->assertNull(
            $tareaSinCambios->completada_por_user_id
        );
    }

    public function test_no_permite_modificar_tarea_de_otra_operacion(): void
    {
        $otraReserva = $this->crearReserva(
            'RES-OPERACIONES-002'
        );

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
            ->patch(
                route(
                    'operaciones.tareas.update',
                    [
                        /*
                         * Operación diferente, pero se intenta
                         * utilizar la tarea original.
                         */
                        'operacion' =>
                            $otraOperacion->id,

                        'tarea' =>
                            $this->tarea->id,
                    ]
                ),
                [
                    'estado' =>
                        TareaOperacionViaje::ESTADO_COMPLETADA,

                    'observaciones' =>
                        'Intento inválido.',
                ]
            )
            ->assertNotFound();

        $tareaSinCambios =
            $this->tarea->fresh();

        $this->assertSame(
            TareaOperacionViaje::ESTADO_PENDIENTE,
            $tareaSinCambios->estado
        );

        $this->assertNull(
            $tareaSinCambios->completada_at
        );
    }

    public function test_reabrir_tarea_limpia_datos_de_finalizacion(): void
    {
        $this
            ->actingAs($this->usuario)
            ->patch(
                route(
                    'operaciones.tareas.update',
                    [
                        'operacion' =>
                            $this->operacion->id,

                        'tarea' =>
                            $this->tarea->id,
                    ]
                ),
                [
                    'estado' =>
                        TareaOperacionViaje::ESTADO_COMPLETADA,

                    'observaciones' =>
                        'Gestión completada.',
                ]
            )
            ->assertRedirect();

        $this
            ->actingAs($this->usuario)
            ->patch(
                route(
                    'operaciones.tareas.update',
                    [
                        'operacion' =>
                            $this->operacion->id,

                        'tarea' =>
                            $this->tarea->id,
                    ]
                ),
                [
                    'estado' =>
                        TareaOperacionViaje::ESTADO_EN_PROCESO,

                    'observaciones' =>
                        'Se requiere una comprobación adicional.',
                ]
            )
            ->assertRedirect();

        $tareaReabierta =
            $this->tarea->fresh();

        $this->assertSame(
            TareaOperacionViaje::ESTADO_EN_PROCESO,
            $tareaReabierta->estado
        );

        $this->assertSame(
            'Se requiere una comprobación adicional.',
            $tareaReabierta->observaciones
        );

        $this->assertNull(
            $tareaReabierta->completada_at
        );

        $this->assertNull(
            $tareaReabierta->completada_por_user_id
        );
    }

    public function test_usuario_no_autenticado_no_puede_actualizar_tarea(): void
    {
        $this
            ->patch(
                route(
                    'operaciones.tareas.update',
                    [
                        'operacion' =>
                            $this->operacion->id,

                        'tarea' =>
                            $this->tarea->id,
                    ]
                ),
                [
                    'estado' =>
                        TareaOperacionViaje::ESTADO_COMPLETADA,

                    'observaciones' =>
                        'Intento sin autenticación.',
                ]
            )
            ->assertRedirect(
                route('login')
            );

        $this->assertSame(
            TareaOperacionViaje::ESTADO_PENDIENTE,
            $this->tarea->fresh()->estado
        );
    }

    private function crearReserva(
        string $codigo
    ): Reserva {
        return Reserva::create([
            'codigo_reserva' =>
                $codigo,

            'cliente_id' =>
                $this->cliente->id,

            'destino_id' =>
                $this->destino->id,

            'user_id' =>
                $this->usuario->id,

            'tipo' =>
                Reserva::TIPO_INDIVIDUAL,

            'fecha_reserva' =>
                '2026-08-09',

            'fecha_viaje' =>
                '2026-12-01',

            'precio_total_viaje' =>
                1000,

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
    }
}
