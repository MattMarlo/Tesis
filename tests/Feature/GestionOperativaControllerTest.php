<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Destino;
use App\Models\GestionOperativa;
use App\Models\GestionOperativaViajero;
use App\Models\GuiaReserva;
use App\Models\OperacionViaje;
use App\Models\Reserva;
use App\Models\TareaOperacionViaje;
use App\Models\User;
use App\Models\ViajeroReserva;
use App\Services\EstadoTareaContextualService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class GestionOperativaControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    private Cliente $cliente;

    private Destino $destino;

    private Reserva $reserva;

    private OperacionViaje $operacion;

    private TareaOperacionViaje $tarea;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = User::create([
            'nombres' => 'Agente',
            'apellidos' => 'Gestiones',
            'email' => 'agente-gestiones@example.com',
            'telefono' => '0999999801',
            'documento' => 'USR-GESTIONES-001',
            'rol' => User::ROL_ADMIN,
            'estado' => User::ESTADO_ACTIVO,
            'password' => 'password',
        ]);

        $this->cliente = Cliente::create([
            'nombres' => 'Cliente',
            'apellidos' => 'Gestiones',
            'tipo_documento' =>
                Cliente::DOCUMENTO_PASAPORTE,
            'email' => 'cliente-gestiones@example.com',
            'telefono' => '0999999802',
            'documento' => 'CLI-GESTIONES-001',
            'fecha_nacimiento' => '1990-01-01',
            'nacionalidad' => 'Ecuatoriana',
            'estado' => Cliente::ESTADO_ACTIVO,
        ]);

        $this->destino = Destino::create([
            'nombre_paquete' =>
                'Paquete para gestiones operativas',

            'slug' =>
                'paquete-gestiones-operativas',

            'etiqueta' => 'Prueba',
            'pais' => 'Perú',
            'ciudad_destino' => 'Lima',
            'categoria' => 'Cultural',

            'descripcion_corta' =>
                'Paquete para probar gestiones.',

            'descripcion' =>
                'Paquete utilizado por las pruebas automáticas.',

            'ciudad_salida' => 'Quito',
            'fecha_salida' => '2026-12-01',
            'fecha_regreso' => '2026-12-05',
            'precio' => 1000,
            'moneda' => 'USD',
            'dias' => 5,
            'noches' => 4,
            'capacidad' => 20,
            'incluye' => [],
            'no_incluye' => [],
            'itinerario' => [],
            'estado_publicacion' => 'publicado',
            'destacado' => false,
        ]);

        $this->reserva = $this->crearReserva(
            'RES-GESTIONES-001'
        );

        $this->operacion = OperacionViaje::create([
            'reserva_id' => $this->reserva->id,
            'estado' =>
                OperacionViaje::ESTADO_PREPARACION,
            'creado_por_user_id' =>
                $this->usuario->id,
            'actualizado_por_user_id' =>
                $this->usuario->id,
        ]);

        $this->tarea = $this->crearTarea(
            $this->operacion
        );
    }

    public function test_crea_gestion_confirmada_y_completa_tarea(): void
    {
        $respuesta = $this
            ->actingAs($this->usuario)
            ->post(
                route(
                    'operaciones.tareas.gestiones.store',
                    [
                        'operacion' =>
                            $this->operacion->id,

                        'tarea' =>
                            $this->tarea->id,
                    ]
                ),
                $this->datosGestion()
            );

        $respuesta
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $gestion = GestionOperativa::query()
            ->where(
                'operacion_viaje_id',
                $this->operacion->id
            )
            ->firstOrFail();

        $this->assertSame(
            TareaOperacionViaje::TIPO_TRASLADO,
            $gestion->tipo
        );

        $this->assertSame(
            'Traslados Turísticos Perú',
            $gestion->proveedor
        );

        $this->assertSame(
            1,
            (int) $gestion->cantidad_viajeros
        );

        $tareaActualizada =
            $this->tarea->fresh();

        $this->assertSame(
            GestionOperativa::class,
            $tareaActualizada->gestionable_type
        );

        $this->assertSame(
            $gestion->id,
            $tareaActualizada->gestionable_id
        );

        $this->assertSame(
            TareaOperacionViaje::ESTADO_COMPLETADA,
            $tareaActualizada->estado
        );

        $this->assertNotNull(
            $tareaActualizada->completada_at
        );
    }

    public function test_crea_guia_contextual_y_lo_vincula_con_la_tarea(): void
    {
        $this->tarea->update([
            'tipo_gestion' => TareaOperacionViaje::TIPO_GUIA,
            'nombre' => 'Recorrido histórico por Lima',
        ]);

        $respuesta = $this
            ->actingAs($this->usuario)
            ->post(
                route('operaciones.guias.store', $this->operacion),
                $this->datosGuia([
                    'tarea_id' => $this->tarea->id,
                ])
            );

        $respuesta
            ->assertSessionHasNoErrors()
            ->assertSessionHas(
                'success',
                'Guía registrado y vinculado correctamente con la actividad.'
            );

        $guia = GuiaReserva::query()->firstOrFail();
        $tarea = $this->tarea->fresh();

        $this->assertSame(GuiaReserva::class, $tarea->gestionable_type);
        $this->assertSame($guia->id, $tarea->gestionable_id);
        $this->assertSame(
            TareaOperacionViaje::ESTADO_COMPLETADA,
            $tarea->estado
        );
    }

    public function test_asignar_guia_notifica_una_sola_vez_a_n8n_sin_bloquear_la_asignacion(): void
    {
        config([
            'services.n8n.guide_assignment_notification_url' =>
                'https://n8n.example.test/webhook/guia-viaje-asignado',
        ]);

        Http::fake([
            '*' => Http::response([], 500),
        ]);

        $this->tarea->update([
            'tipo_gestion' => TareaOperacionViaje::TIPO_GUIA,
            'nombre' => 'Recorrido histórico por Lima',
        ]);

        $this
            ->actingAs($this->usuario)
            ->post(
                route('operaciones.guias.store', $this->operacion),
                $this->datosGuia([
                    'tarea_id' => $this->tarea->id,
                ])
            )
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $guia = GuiaReserva::query()->firstOrFail();

        Http::assertSent(
            function (HttpRequest $request) use ($guia): bool {
                return $request->url() === config(
                    'services.n8n.guide_assignment_notification_url'
                )
                    && $request['event'] ===
                        'guia.viaje.asignado'
                    && data_get(
                        $request->data(),
                        'data.guia_id'
                    ) === $guia->id
                    && data_get(
                        $request->data(),
                        'data.codigo_reserva'
                    ) === $this->reserva->codigo_reserva
                    && data_get(
                        $request->data(),
                        'data.cliente'
                    ) === $this->cliente->nombre_completo
                    && data_get(
                        $request->data(),
                        'data.telefono'
                    ) === $this->cliente->telefono
                    && data_get(
                        $request->data(),
                        'data.destino'
                    ) === $this->destino->ciudad_destino
                    && data_get(
                        $request->data(),
                        'data.nombre_guia'
                    ) === $guia->nombre_completo
                    && data_get(
                        $request->data(),
                        'data.telefono_guia'
                    ) === $guia->telefono
                    && data_get(
                        $request->data(),
                        'data.actividad'
                    ) === 'Recorrido histórico por Lima'
                    && data_get(
                        $request->data(),
                        'data.descripcion_actividad'
                    ) === 'Recepción y traslado de los viajeros.'
                    && data_get(
                        $request->data(),
                        'data.fecha_viaje'
                    ) === '2026-12-01'
                    && data_get(
                        $request->data(),
                        'data.fecha_actividad'
                    ) === '2026-12-01'
                    && data_get(
                        $request->data(),
                        'data.fecha_hora_inicio_actividad'
                    ) === '2026-12-01T08:30:00-05:00'
                    && data_get(
                        $request->data(),
                        'data.ubicacion_actividad'
                    ) === 'Aeropuerto de Lima';
            }
        );

        Http::assertSentCount(1);

        $this->assertSame(
            $guia->id,
            $this->tarea->fresh()->gestionable_id
        );
    }

    public function test_reutiliza_guia_y_lo_desvincula_antes_de_eliminarlo(): void
    {
        config([
            'services.n8n.guide_assignment_notification_url' =>
                'https://n8n.example.test/webhook/guia-viaje-asignado',
        ]);

        Http::fake();

        $this->tarea->update([
            'tipo_gestion' => TareaOperacionViaje::TIPO_GUIA,
        ]);

        $guia = $this->operacion->guias()->create(
            $this->datosGuia()
        );

        $this
            ->actingAs($this->usuario)
            ->post(
                route('operaciones.guias.store', $this->operacion),
                [
                    'tarea_id' => $this->tarea->id,
                    'guia_existente_id' => $guia->id,
                ]
            )
            ->assertSessionHasNoErrors();

        $this->assertSame(
            $guia->id,
            $this->tarea->fresh()->gestionable_id
        );

        $this
            ->actingAs($this->usuario)
            ->delete(route('operaciones.guias.destroy', $guia))
            ->assertSessionHasNoErrors();

        $tarea = $this->tarea->fresh();

        $this->assertNull($tarea->gestionable_type);
        $this->assertNull($tarea->gestionable_id);
        $this->assertSame(
            TareaOperacionViaje::ESTADO_PENDIENTE,
            $tarea->estado
        );

        Http::assertSentCount(1);
    }

    public function test_actualizar_gestion_sincroniza_estado_de_tarea(): void
    {
        $gestion = $this->crearGestion(
            GestionOperativa::ESTADO_PENDIENTE
        );

        app(
            EstadoTareaContextualService::class
        )->vincular(
            $this->tarea,
            $gestion,
            $this->usuario
        );

        $this->assertSame(
            TareaOperacionViaje::ESTADO_PENDIENTE,
            $this->tarea->fresh()->estado
        );

        $respuesta = $this
            ->actingAs($this->usuario)
            ->put(
                route(
                    'operaciones.gestiones.update',
                    [
                        'gestion' =>
                            $gestion->id,
                    ]
                ),
                $this->datosGestion([
                    'proveedor' =>
                        'Proveedor actualizado',

                    'estado' =>
                        GestionOperativa::ESTADO_CONFIRMADO,
                ])
            );

        $respuesta
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame(
            'Proveedor actualizado',
            $gestion->fresh()->proveedor
        );

        $this->assertSame(
            TareaOperacionViaje::ESTADO_COMPLETADA,
            $this->tarea->fresh()->estado
        );
    }

    public function test_eliminar_gestion_desvincula_y_reabre_tarea(): void
    {
        $gestion = $this->crearGestion(
            GestionOperativa::ESTADO_CONFIRMADO
        );

        app(
            EstadoTareaContextualService::class
        )->vincular(
            $this->tarea,
            $gestion,
            $this->usuario
        );

        $this->assertSame(
            TareaOperacionViaje::ESTADO_COMPLETADA,
            $this->tarea->fresh()->estado
        );

        $respuesta = $this
            ->actingAs($this->usuario)
            ->delete(
                route(
                    'operaciones.gestiones.destroy',
                    [
                        'gestion' =>
                            $gestion->id,
                    ]
                )
            );

        $respuesta
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseMissing(
            'gestiones_operativas',
            [
                'id' => $gestion->id,
            ]
        );

        $tareaActualizada =
            $this->tarea->fresh();

        $this->assertNull(
            $tareaActualizada->gestionable_type
        );

        $this->assertNull(
            $tareaActualizada->gestionable_id
        );

        $this->assertSame(
            TareaOperacionViaje::ESTADO_PENDIENTE,
            $tareaActualizada->estado
        );

        $this->assertNull(
            $tareaActualizada->completada_at
        );
    }

    public function test_no_permite_usar_tarea_de_otra_operacion(): void
    {
        $otraReserva = $this->crearReserva(
            'RES-GESTIONES-002'
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
            ->post(
                route(
                    'operaciones.tareas.gestiones.store',
                    [
                        'operacion' =>
                            $otraOperacion->id,

                        /*
                         * Esta tarea pertenece a la operación
                         * original, no a la nueva.
                         */
                        'tarea' =>
                            $this->tarea->id,
                    ]
                ),
                $this->datosGestion()
            )
            ->assertNotFound();

        $this->assertDatabaseCount(
            'gestiones_operativas',
            0
        );

        $this->assertNull(
            $this->tarea->fresh()
                ->gestionable_id
        );
    }

    public function test_usuario_no_autenticado_no_puede_crear_gestion(): void
    {
        $this
            ->post(
                route(
                    'operaciones.tareas.gestiones.store',
                    [
                        'operacion' =>
                            $this->operacion->id,

                        'tarea' =>
                            $this->tarea->id,
                    ]
                ),
                $this->datosGestion()
            )
            ->assertRedirect(
                route('login')
            );

        $this->assertDatabaseCount(
            'gestiones_operativas',
            0
        );
    }

    public function test_alimentacion_valida_campos_y_orden_de_fechas_sin_perder_el_contexto(): void
    {
        $this->tarea->update([
            'tipo_gestion' =>
                TareaOperacionViaje::TIPO_ALIMENTACION,
            'nombre' => 'Desayuno en el hotel',
        ]);

        $respuesta = $this
            ->actingAs($this->usuario)
            ->post(
                route(
                    'operaciones.tareas.gestiones.store',
                    [
                        'operacion' => $this->operacion->id,
                        'tarea' => $this->tarea->id,
                    ]
                ),
                $this->datosGestion([
                    'tipo' =>
                        TareaOperacionViaje::TIPO_ALIMENTACION,
                    'proveedor' => '1',
                    'fecha_hora_inicio' =>
                        '2026-12-03 10:00:00',
                    'fecha_hora_fin' =>
                        '2026-12-03 09:00:00',
                    'datos_adicionales' => [
                        'restaurante' => 'x',
                        'tipo_menu' => '1',
                    ],
                ])
            );

        $respuesta->assertSessionHasErrors([
            'proveedor',
            'fecha_hora_fin',
            'datos_adicionales.restaurante',
            'datos_adicionales.tipo_menu',
        ]);

        $this->assertDatabaseCount(
            'gestiones_operativas',
            0
        );
    }

    public function test_tren_se_muestra_y_registra_con_detalle_confirmado_del_viajero(): void
    {
        $this->tarea->update([
            'tipo_gestion' => TareaOperacionViaje::TIPO_TREN,
            'nombre' => 'Tren de Lima a Cusco',
            'descripcion' => 'Traslado ferroviario turístico.',
            'ubicacion' => 'Lima - Cusco',
        ]);

        $viajero = ViajeroReserva::create([
            'reserva_id' => $this->reserva->id,
            'cliente_id' => $this->cliente->id,
            'nombres' => $this->cliente->nombres,
            'apellidos' => $this->cliente->apellidos,
            'tipo_documento' => 'pasaporte',
            'documento' => 'PA123456',
            'fecha_nacimiento' => '1990-01-01',
            'edad_al_viajar' => 36,
            'categoria_tarifa' => Reserva::TARIFA_ADULTO,
            'es_titular' => true,
        ]);

        $this->destino->update([
            'itinerario' => [[
                'dia' => 1,
                'titulo' => 'Trayecto ferroviario',
                'actividades' => [[
                    'uuid' => $this->tarea->actividad_uuid,
                    'nombre' => 'Tren de Lima a Cusco',
                    'descripcion' => 'Traslado ferroviario turístico.',
                    'hora_inicio' => '07:00',
                    'hora_fin' => '11:30',
                    'ubicacion' => 'Lima - Cusco',
                    'requiere_gestion' => true,
                    'tipo_gestion' => TareaOperacionViaje::TIPO_TREN,
                ]],
            ]],
        ]);

        $this->actingAs($this->usuario)
            ->get(route('operaciones.show', $this->reserva))
            ->assertOk()
            ->assertSee('Gestionar reserva de tren')
            ->assertSee('Información ferroviaria');

        $respuesta = $this
            ->actingAs($this->usuario)
            ->post(
                route(
                    'operaciones.tareas.gestiones.store',
                    [
                        'operacion' => $this->operacion->id,
                        'tarea' => $this->tarea->id,
                    ]
                ),
                $this->datosGestion([
                    'tipo' => TareaOperacionViaje::TIPO_TREN,
                    'nombre' => 'Tren de Lima a Cusco',
                    'proveedor' => 'PeruRail',
                    'fecha_hora_inicio' => '2026-12-02 07:00:00',
                    'fecha_hora_fin' => '2026-12-02 11:30:00',
                    'ubicacion_origen' => 'Estación Lima',
                    'destino' => 'Estación Cusco',
                    'cantidad_viajeros' => 1,
                    'referencia_confirmacion' => 'TREN-2026-01',
                    'estado' => GestionOperativa::ESTADO_CONFIRMADO,
                    'datos_adicionales' => [
                        'empresa_ferroviaria' => 'PeruRail',
                        'ruta' => 'Lima - Cusco',
                        'clase' => 'Turista',
                    ],
                ])
            );

        $respuesta->assertRedirect()->assertSessionHasNoErrors();

        $gestion = GestionOperativa::query()
            ->where('tipo', GestionOperativa::TIPO_TREN)
            ->firstOrFail();

        $this->assertSame(
            $gestion->id,
            $this->tarea->fresh()->gestionable_id
        );
        $this->assertSame(
            TareaOperacionViaje::ESTADO_EN_PROCESO,
            $this->tarea->fresh()->estado
        );
        $this->assertDatabaseHas('gestion_operativa_viajeros', [
            'gestion_operativa_id' => $gestion->id,
            'viajero_reserva_id' => $viajero->id,
            'asiento' => null,
            'estado' => GestionOperativaViajero::ESTADO_PENDIENTE,
        ]);

        $pasaje = $gestion->detallesViajeros()
            ->firstOrFail();

        $this->actingAs($this->usuario)
            ->get(route(
                'operaciones.trenes.pasajes.index',
                [
                    'operacion' => $this->operacion->id,
                    'gestion' => $gestion->id,
                    'tarea_id' => $this->tarea->id,
                ]
            ))
            ->assertOk()
            ->assertSee('Gestión de pasajes de tren')
            ->assertSee($viajero->nombre_completo);

        $this->actingAs($this->usuario)
            ->put(route(
                'operaciones.trenes.pasajes.update',
                $pasaje->id
            ), [
                'pasaje_id' => $pasaje->id,
                'numero_documento' => 'TR123456',
                'referencia_individual' => 'REF-001',
                'asiento' => '12A',
                'estado' => GestionOperativaViajero::ESTADO_CONFIRMADO,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('gestion_operativa_viajeros', [
            'id' => $pasaje->id,
            'numero_documento' => 'TR123456',
            'asiento' => '12A',
            'estado' => GestionOperativaViajero::ESTADO_CONFIRMADO,
        ]);

        $this->assertSame(
            TareaOperacionViaje::ESTADO_COMPLETADA,
            $this->tarea->fresh()->estado
        );
    }

    public function test_tren_gestiona_al_titular_registrado_en_una_reserva_tradicional(): void
    {
        $this->tarea->update([
            'tipo_gestion' => TareaOperacionViaje::TIPO_TREN,
            'nombre' => 'Tren histórico',
        ]);

        $this->assertDatabaseCount('viajeros_reserva', 0);

        $respuesta = $this
            ->actingAs($this->usuario)
            ->post(
                route(
                    'operaciones.tareas.gestiones.store',
                    [
                        'operacion' => $this->operacion->id,
                        'tarea' => $this->tarea->id,
                    ]
                ),
                $this->datosGestion([
                    'tipo' => TareaOperacionViaje::TIPO_TREN,
                    'nombre' => 'Tren histórico',
                    'proveedor' => 'Ferrocarriles Ecuador',
                    'fecha_hora_inicio' => '2026-12-02 07:00:00',
                    'fecha_hora_fin' => '2026-12-02 10:00:00',
                    'ubicacion_origen' => 'Estación Alausí',
                    'destino' => 'Estación Sibambe',
                    'cantidad_viajeros' => 1,
                    'referencia_confirmacion' => 'TREN-HIST-01',
                    'estado' => GestionOperativa::ESTADO_CONFIRMADO,
                    'datos_adicionales' => [
                        'empresa_ferroviaria' => 'Ferrocarriles Ecuador',
                        'ruta' => 'Alausí - Sibambe',
                        'clase' => 'Turista',
                    ],
                ])
            );

        $respuesta->assertRedirect()->assertSessionHasNoErrors();

        $gestion = GestionOperativa::query()
            ->where('tipo', GestionOperativa::TIPO_TREN)
            ->firstOrFail();

        $this->assertDatabaseHas(
            'gestion_operativa_viajeros',
            [
                'gestion_operativa_id' => $gestion->id,
                'cliente_id' => $this->cliente->id,
                'viajero_reserva_id' => null,
                'asiento' => null,
            ]
        );

        $this->actingAs($this->usuario)
            ->get(route(
                'operaciones.trenes.pasajes.index',
                [
                    'operacion' => $this->operacion->id,
                    'gestion' => $gestion->id,
                    'tarea_id' => $this->tarea->id,
                ]
            ))
            ->assertOk()
            ->assertSee($this->cliente->nombre_completo)
            ->assertSee('Gestionar');

        $this->assertSame(
            TareaOperacionViaje::ESTADO_EN_PROCESO,
            $this->tarea->fresh()->estado
        );
    }

    public function test_tren_rechaza_un_cliente_ajeno_a_la_reserva(): void
    {
        $this->tarea->update([
            'tipo_gestion' => TareaOperacionViaje::TIPO_TREN,
            'nombre' => 'Tren reservado',
        ]);

        $clienteAjeno = Cliente::create([
            'nombres' => 'Persona',
            'apellidos' => 'Ajena',
            'tipo_documento' =>
                Cliente::DOCUMENTO_PASAPORTE,
            'documento' => 'AJENO123',
            'fecha_nacimiento' => '1992-01-01',
            'email' => 'persona-ajena@example.com',
            'telefono' => '0999999810',
            'estado' => Cliente::ESTADO_ACTIVO,
        ]);

        $respuesta = $this
            ->actingAs($this->usuario)
            ->post(
                route(
                    'operaciones.tareas.gestiones.store',
                    [
                        'operacion' => $this->operacion->id,
                        'tarea' => $this->tarea->id,
                    ]
                ),
                $this->datosGestion([
                    'tipo' => TareaOperacionViaje::TIPO_TREN,
                    'nombre' => 'Tren reservado',
                    'proveedor' => 'Empresa Ferroviaria',
                    'fecha_hora_inicio' => '2026-12-02 07:00:00',
                    'fecha_hora_fin' => '2026-12-02 10:00:00',
                    'ubicacion_origen' => 'Estacion origen',
                    'destino' => 'Estacion destino',
                    'cantidad_viajeros' => 1,
                    'referencia_confirmacion' => 'TREN-001',
                    'estado' => GestionOperativa::ESTADO_CONFIRMADO,
                    'datos_adicionales' => [
                        'empresa_ferroviaria' => 'Empresa Ferroviaria',
                        'ruta' => 'Origen - Destino',
                        'clase' => 'Turista',
                    ],
                    'viajeros' => [[
                        'cliente_id' => $clienteAjeno->id,
                        'estado' => GestionOperativaViajero::ESTADO_CONFIRMADO,
                        'numero_documento' => 'TR-001',
                        'asiento' => '4A',
                    ]],
                ])
            );

        $respuesta->assertSessionHasErrors([
            'viajeros.0.cliente_id',
        ]);

        $this->assertDatabaseCount(
            'gestiones_operativas',
            0
        );
    }

    private function crearReserva(
        string $codigo
    ): Reserva {
        return Reserva::create([
            'codigo_reserva' => $codigo,
            'cliente_id' => $this->cliente->id,
            'destino_id' => $this->destino->id,
            'user_id' => $this->usuario->id,
            'tipo' => Reserva::TIPO_INDIVIDUAL,
            'fecha_reserva' => '2026-08-09',
            'fecha_viaje' => '2026-12-01',
            'precio_total_viaje' => 1000,
            'moneda' => 'USD',
            'cantidad_viajeros' => 1,
            'edad_viajero' => 36,
            'categoria_tarifa' =>
                Reserva::TARIFA_ADULTO,
            'estado' =>
                Reserva::ESTADO_CONFIRMADA,
            'estado_pago' =>
                Reserva::PAGO_PENDIENTE,
        ]);
    }

    private function crearTarea(
        OperacionViaje $operacion
    ): TareaOperacionViaje {
        return TareaOperacionViaje::create([
            'operacion_viaje_id' =>
                $operacion->id,

            'actividad_uuid' =>
                (string) Str::uuid(),

            'dia' => 1,

            'nombre' =>
                'Traslado del aeropuerto al hotel',

            'descripcion' =>
                'Recepción y traslado de los viajeros.',

            'hora_inicio' => '08:30',
            'hora_fin' => '09:30',

            'ubicacion' =>
                'Aeropuerto de Lima',

            'tipo_gestion' =>
                TareaOperacionViaje::TIPO_TRASLADO,

            'estado' =>
                TareaOperacionViaje::ESTADO_PENDIENTE,

            'vigente' => true,
        ]);
    }

    private function crearGestion(
        string $estado
    ): GestionOperativa {
        return GestionOperativa::create([
            'operacion_viaje_id' =>
                $this->operacion->id,

            'tipo' =>
                TareaOperacionViaje::TIPO_TRASLADO,

            'nombre' =>
                'Traslado del aeropuerto al hotel',

            'proveedor' =>
                'Traslados Turísticos Perú',

            'contacto' =>
                'Carlos Transportista',

            'telefono' =>
                '+51999999999',

            'correo' =>
                'traslados@example.com',

            'fecha_hora_inicio' =>
                '2026-12-01 08:30:00',

            'fecha_hora_fin' =>
                '2026-12-01 09:30:00',

            'ubicacion_origen' =>
                'Aeropuerto de Lima',

            'destino' =>
                'Hotel en Miraflores',

            'cantidad_viajeros' => 1,
            'capacidad' => 4,

            'referencia_confirmacion' =>
                'TRASLADO-001',

            'costo_total' => 45.50,
            'moneda' => 'USD',
            'estado' => $estado,

            'observaciones' =>
                'Vehículo privado confirmado.',

            'datos_adicionales' => [
                'tipo_vehiculo' =>
                    'Automóvil',

                'conductor' =>
                    'Carlos Transportista',
            ],

            'creado_por_user_id' =>
                $this->usuario->id,

            'actualizado_por_user_id' =>
                $this->usuario->id,
        ]);
    }

    private function datosGestion(
        array $cambios = []
    ): array {
        return array_replace_recursive(
            [
                'tipo' =>
                    TareaOperacionViaje::TIPO_TRASLADO,

                'nombre' =>
                    'Traslado del aeropuerto al hotel',

                'proveedor' =>
                    'Traslados Turísticos Perú',

                'contacto' =>
                    'Carlos Transportista',

                'telefono' =>
                    '+51999999999',

                'correo' =>
                    'traslados@example.com',

                'fecha_hora_inicio' =>
                    '2026-12-01 08:30:00',

                'fecha_hora_fin' =>
                    '2026-12-01 09:30:00',

                'ubicacion_origen' =>
                    'Aeropuerto de Lima',

                'destino' =>
                    'Hotel en Miraflores',

                'cantidad_viajeros' => 1,
                'capacidad' => 4,

                'referencia_confirmacion' =>
                    'TRASLADO-001',

                'costo_total' => 45.50,
                'moneda' => 'USD',

                'estado' =>
                    GestionOperativa::ESTADO_CONFIRMADO,

                'observaciones' =>
                    'Vehículo privado confirmado.',

                'datos_adicionales' => [
                    'tipo_vehiculo' =>
                        'Automóvil',

                    'conductor' =>
                        'Carlos Transportista',
                ],
            ],
            $cambios
        );
    }

    private function datosGuia(array $cambios = []): array
    {
        return array_replace([
            'nombre_completo' => 'Lucía Guía Cultural',
            'empresa' => 'Rutas del Perú',
            'ciudad_servicio' => 'Lima',
            'telefono' => '+51999999888',
            'correo' => 'lucia.guia@example.com',
            'idiomas' => 'Español e inglés',
            'fecha_inicio' => '2026-12-01',
            'fecha_fin' => '2026-12-05',
            'punto_encuentro' => 'Plaza Mayor de Lima',
            'fecha_hora_encuentro' => '2026-12-01 08:00:00',
            'servicios_incluidos' => 'Recorrido cultural guiado.',
            'contacto_emergencia' => '+51999999777',
            'costo_total' => 180,
            'moneda' => 'USD',
            'estado' => GuiaReserva::ESTADO_CONFIRMADO,
            'observaciones' => 'Guía confirmado para el recorrido.',
        ], $cambios);
    }
}
