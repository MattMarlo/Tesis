<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Destino;
use App\Models\OperacionViaje;
use App\Models\Reserva;
use App\Models\TareaOperacionViaje;
use App\Models\User;
use App\Services\ProgresoOperacionService;
use App\Services\SincronizarTareasItinerarioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TareasOperacionItinerarioTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    private Cliente $cliente;

    private Destino $destino;

    private Reserva $reserva;

    private OperacionViaje $operacion;

    private string $uuidTraslado =
        '11111111-1111-4111-8111-111111111111';

    private string $uuidEntradas =
        '22222222-2222-4222-8222-222222222222';

    private string $uuidActividadLibre =
        '33333333-3333-4333-8333-333333333333';

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = User::create([
            'nombres' =>
                'Administrador',

            'apellidos' =>
                'Tareas',

            'email' =>
                'administrador-tareas@example.com',

            'telefono' =>
                '0999999901',

            'documento' =>
                'USR-TAREAS-001',

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
                'Itinerario',

            'tipo_documento' =>
                Cliente::DOCUMENTO_PASAPORTE,

            'email' =>
                'cliente-itinerario@example.com',

            'telefono' =>
                '0999999902',

            'documento' =>
                'CLI-TAREAS-001',

            'fecha_nacimiento' =>
                '1990-01-01',

            'nacionalidad' =>
                'Ecuatoriana',

            'estado' =>
                Cliente::ESTADO_ACTIVO,
        ]);

        $this->destino = Destino::create([
            'nombre_paquete' =>
                'Paquete de prueba con itinerario',

            'slug' =>
                'paquete-prueba-tareas-itinerario',

            'etiqueta' =>
                'Prueba',

            'pais' =>
                'Ecuador',

            'ciudad_destino' =>
                'Quito',

            'categoria' =>
                'Cultural',

            'descripcion_corta' =>
                'Paquete utilizado para probar las tareas.',

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

            /*
             * No se incluye alojamiento ni guía para evitar
             * que esas reglas interfieran con estas pruebas.
             */
            'incluye' => [
                'Transporte aéreo',
            ],

            'no_incluye' => [],

            'itinerario' =>
                $this->itinerarioCompleto(),

            'estado_publicacion' =>
                'publicado',

            'destacado' =>
                false,
        ]);

        $this->reserva = Reserva::create([
            'codigo_reserva' =>
                'RES-TAREAS-001',

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
    }

    public function test_solo_crea_tareas_para_actividades_que_requieren_gestion(): void
    {
        $tareas = app(
            SincronizarTareasItinerarioService::class
        )->sincronizar(
            $this->operacion
        );

        /*
         * El itinerario tiene tres actividades, pero solamente
         * dos están marcadas como requiere_gestion.
         */
        $this->assertCount(
            2,
            $tareas
        );

        $this->assertDatabaseHas(
            'tareas_operacion_viaje',
            [
                'operacion_viaje_id' =>
                    $this->operacion->id,

                'actividad_uuid' =>
                    $this->uuidTraslado,

                'dia' =>
                    1,

                'tipo_gestion' =>
                    TareaOperacionViaje::TIPO_TRASLADO,

                'estado' =>
                    TareaOperacionViaje::ESTADO_PENDIENTE,

                'vigente' =>
                    true,
            ]
        );

        $this->assertDatabaseHas(
            'tareas_operacion_viaje',
            [
                'operacion_viaje_id' =>
                    $this->operacion->id,

                'actividad_uuid' =>
                    $this->uuidEntradas,

                'dia' =>
                    2,

                'tipo_gestion' =>
                    TareaOperacionViaje::TIPO_ENTRADA,

                'estado' =>
                    TareaOperacionViaje::ESTADO_PENDIENTE,

                'vigente' =>
                    true,
            ]
        );

        /*
         * La actividad libre no debe convertirse en tarea.
         */
        $this->assertDatabaseMissing(
            'tareas_operacion_viaje',
            [
                'operacion_viaje_id' =>
                    $this->operacion->id,

                'actividad_uuid' =>
                    $this->uuidActividadLibre,
            ]
        );
    }

    public function test_sincronizar_varias_veces_no_duplica_las_tareas(): void
    {
        $servicio = app(
            SincronizarTareasItinerarioService::class
        );

        $servicio->sincronizar(
            $this->operacion
        );

        $servicio->sincronizar(
            $this->operacion->fresh()
        );

        $this->assertSame(
            2,
            TareaOperacionViaje::query()
                ->where(
                    'operacion_viaje_id',
                    $this->operacion->id
                )
                ->count()
        );

        $this->assertSame(
            1,
            TareaOperacionViaje::query()
                ->where(
                    'operacion_viaje_id',
                    $this->operacion->id
                )
                ->where(
                    'actividad_uuid',
                    $this->uuidTraslado
                )
                ->count()
        );

        $this->assertSame(
            1,
            TareaOperacionViaje::query()
                ->where(
                    'operacion_viaje_id',
                    $this->operacion->id
                )
                ->where(
                    'actividad_uuid',
                    $this->uuidEntradas
                )
                ->count()
        );
    }

    public function test_conserva_estado_y_observaciones_al_modificar_itinerario(): void
    {
        $servicio = app(
            SincronizarTareasItinerarioService::class
        );

        $servicio->sincronizar(
            $this->operacion
        );

        $tarea = TareaOperacionViaje::query()
            ->where(
                'operacion_viaje_id',
                $this->operacion->id
            )
            ->where(
                'actividad_uuid',
                $this->uuidTraslado
            )
            ->firstOrFail();

        $tarea->update([
            'estado' =>
                TareaOperacionViaje::ESTADO_COMPLETADA,

            'observaciones' =>
                'Traslado confirmado con el proveedor.',
        ]);

        /*
         * Se modifica la información del itinerario, pero se
         * conserva el mismo UUID de la actividad.
         */
        $itinerario = $this->itinerarioCompleto();

        $itinerario[0]['actividades'][0]['nombre'] =
            'Traslado privado aeropuerto - hotel';

        $itinerario[0]['actividades'][0]['ubicacion'] =
            'Aeropuerto Internacional de Quito';

        $this->destino->update([
            'itinerario' =>
                $itinerario,
        ]);

        $this->reserva->unsetRelation(
            'destino'
        );

        $this->operacion->unsetRelation(
            'reserva'
        );

        $servicio->sincronizar(
            $this->operacion->fresh()
        );

        $tareaActualizada = TareaOperacionViaje::query()
            ->where(
                'operacion_viaje_id',
                $this->operacion->id
            )
            ->where(
                'actividad_uuid',
                $this->uuidTraslado
            )
            ->firstOrFail();

        $this->assertSame(
            $tarea->id,
            $tareaActualizada->id
        );

        $this->assertSame(
            TareaOperacionViaje::ESTADO_COMPLETADA,
            $tareaActualizada->estado
        );

        $this->assertSame(
            'Traslado confirmado con el proveedor.',
            $tareaActualizada->observaciones
        );

        $this->assertTrue(
            (bool) $tareaActualizada->vigente
        );
    }

    public function test_actividad_eliminada_del_itinerario_queda_no_vigente(): void
    {
        $servicio = app(
            SincronizarTareasItinerarioService::class
        );

        $servicio->sincronizar(
            $this->operacion
        );

        /*
         * Dejamos solamente la actividad de entradas.
         * El traslado debe quedar registrado históricamente,
         * pero ya no debe estar vigente.
         */
        $itinerario = $this->itinerarioCompleto();

        $itinerario[0]['actividades'] = [
            $itinerario[0]['actividades'][1],
        ];

        $this->destino->update([
            'itinerario' =>
                $itinerario,
        ]);

        $this->operacion->unsetRelation(
            'reserva'
        );

        $servicio->sincronizar(
            $this->operacion->fresh()
        );

        $tareaEliminada = TareaOperacionViaje::query()
            ->where(
                'operacion_viaje_id',
                $this->operacion->id
            )
            ->where(
                'actividad_uuid',
                $this->uuidTraslado
            )
            ->firstOrFail();

        $tareaVigente = TareaOperacionViaje::query()
            ->where(
                'operacion_viaje_id',
                $this->operacion->id
            )
            ->where(
                'actividad_uuid',
                $this->uuidEntradas
            )
            ->firstOrFail();

        $this->assertFalse(
            (bool) $tareaEliminada->vigente
        );

        $this->assertTrue(
            (bool) $tareaVigente->vigente
        );

        /*
         * La tarea eliminada no debe borrarse porque contiene
         * historial operativo.
         */
        $this->assertDatabaseHas(
            'tareas_operacion_viaje',
            [
                'id' =>
                    $tareaEliminada->id,

                'vigente' =>
                    false,
            ]
        );
    }

    public function test_progreso_reporta_tareas_pendientes(): void
    {
        $progreso = app(
            ProgresoOperacionService::class
        )->calcular(
            $this->operacion
        );

        $this->assertTrue(
            $progreso['tareas_itinerario']['aplica']
        );

        $this->assertSame(
            0,
            $progreso['tareas_itinerario']['actual']
        );

        $this->assertSame(
            2,
            $progreso['tareas_itinerario']['total']
        );

        $this->assertSame(
            2,
            $progreso['tareas_itinerario']['pendientes']
        );

        $this->assertCount(
            2,
            $progreso['tareas_gestion']
        );

        $this->assertContains(
            'Faltan 2 tareas del itinerario por completar.',
            $progreso['motivos_pendientes']
        );

        $this->assertFalse(
            $progreso['puede_completar']
        );
    }

    public function test_tareas_completadas_se_reflejan_en_el_progreso(): void
    {
        $servicioProgreso = app(
            ProgresoOperacionService::class
        );

        /*
         * La primera ejecución crea las tareas.
         */
        $servicioProgreso->calcular(
            $this->operacion
        );

        TareaOperacionViaje::query()
            ->where(
                'operacion_viaje_id',
                $this->operacion->id
            )
            ->where(
                'vigente',
                true
            )
            ->update([
                'estado' =>
                    TareaOperacionViaje::ESTADO_COMPLETADA,

                'observaciones' =>
                    'Gestión finalizada durante la prueba.',
            ]);

        /*
         * La sincronización debe conservar el estado completado.
         */
        $progreso = $servicioProgreso->calcular(
            $this->operacion->fresh()
        );

        $this->assertSame(
            2,
            $progreso['tareas_itinerario']['actual']
        );

        $this->assertSame(
            2,
            $progreso['tareas_itinerario']['total']
        );

        $this->assertSame(
            0,
            $progreso['tareas_itinerario']['pendientes']
        );

        $this->assertNotContains(
            'Faltan 2 tareas del itinerario por completar.',
            $progreso['motivos_pendientes']
        );

        /*
         * Puede seguir siendo false por vuelos o pagos pendientes.
         * Esta prueba solamente verifica que las tareas dejaron
         * de ser un impedimento.
         */
        $this->assertFalse(
            collect(
                $progreso['motivos_pendientes']
            )->contains(
                fn ($motivo) =>
                    str_contains(
                        $motivo,
                        'tareas del itinerario'
                    )
            )
        );
    }

    public function test_sincroniza_todos_los_tipos_especificos_sin_convertirlos_en_otro(): void
    {
        $tipos = TareaOperacionViaje::TIPOS_SELECCIONABLES;

        $itinerario = [
            [
                'dia' => 1,

                'titulo' =>
                    'Coordinaciones especializadas',

                'descripcion' =>
                    'Prueba de todos los tipos de gestión.',

                'actividades' => collect($tipos)
                    ->values()
                    ->map(
                        function (
                            string $tipo,
                            int $indice
                        ) {
                            return [
                                'uuid' =>
                                    sprintf(
                                        'aaaaaaaa-aaaa-4aaa-8aaa-%012d',
                                        $indice + 1
                                    ),

                                'nombre' =>
                                    'Gestión ' . $tipo,

                                'descripcion' =>
                                    'Actividad de prueba.',

                                'hora_inicio' =>
                                    '08:00',

                                'hora_fin' =>
                                    '09:00',

                                'ubicacion' =>
                                    'Ubicación de prueba',

                                'requiere_gestion' =>
                                    true,

                                'tipo_gestion' =>
                                    $tipo,
                            ];
                        }
                    )
                    ->all(),
            ],
        ];

        $this->destino->update([
            'itinerario' => $itinerario,
        ]);

        $this->operacion->unsetRelation('reserva');

        $tareas = app(
            SincronizarTareasItinerarioService::class
        )->sincronizar(
            $this->operacion->fresh()
        );

        $this->assertCount(
            count($tipos),
            $tareas
        );

        $this->assertEqualsCanonicalizing(
            $tipos,
            $tareas
                ->pluck('tipo_gestion')
                ->all()
        );
    }

    private function itinerarioCompleto(): array
    {
        return [
            [
                'dia' =>
                    1,

                'titulo' =>
                    'Llegada y traslado',

                'descripcion' =>
                    'Recepción de los viajeros.',

                'actividades' => [
                    [
                        'uuid' =>
                            $this->uuidTraslado,

                        'nombre' =>
                            'Traslado aeropuerto - hotel',

                        'descripcion' =>
                            'Coordinar el vehículo para los viajeros.',

                        'hora_inicio' =>
                            '08:00',

                        'hora_fin' =>
                            '10:00',

                        'ubicacion' =>
                            'Aeropuerto de Quito',

                        'requiere_gestion' =>
                            true,

                        'tipo_gestion' =>
                            TareaOperacionViaje::TIPO_TRASLADO,
                    ],

                    [
                        'uuid' =>
                            $this->uuidActividadLibre,

                        'nombre' =>
                            'Tiempo libre',

                        'descripcion' =>
                            'Actividad libre para los viajeros.',

                        'hora_inicio' =>
                            '15:00',

                        'hora_fin' =>
                            '18:00',

                        'ubicacion' =>
                            'Centro histórico',

                        'requiere_gestion' =>
                            false,

                        'tipo_gestion' =>
                            null,
                    ],
                ],
            ],

            [
                'dia' =>
                    2,

                'titulo' =>
                    'Visita cultural',

                'descripcion' =>
                    'Recorrido por lugares turísticos.',

                'actividades' => [
                    [
                        'uuid' =>
                            $this->uuidEntradas,

                        'nombre' =>
                            'Comprar entradas',

                        'descripcion' =>
                            'Comprar las entradas para el recorrido.',

                        'hora_inicio' =>
                            '09:00',

                        'hora_fin' =>
                            '11:00',

                        'ubicacion' =>
                            'Museo Nacional',

                        'requiere_gestion' =>
                            true,

                        'tipo_gestion' =>
                            TareaOperacionViaje::TIPO_ENTRADA,
                    ],
                ],
            ],
        ];
    }
}