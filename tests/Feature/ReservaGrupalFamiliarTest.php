<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Destino;
use App\Models\Grupo;
use App\Models\Pago;
use App\Models\Reserva;
use App\Models\OperacionViaje;
use App\Models\VueloReserva;
use App\Models\BoletoVuelo;
use App\Models\AlojamientoReserva;
use App\Models\HabitacionAlojamiento;
use App\Models\ViajeroReserva;
use App\Models\User;
use App\Services\CupoReservaService;
use App\Services\PagoService;
use App\Services\ReservaGrupalService;
use App\Services\ViajeroReservaService;
use App\Services\DistribucionHabitacionService;
use App\Services\ProgresoOperacionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ReservaGrupalFamiliarTest extends TestCase
{
    use RefreshDatabase;

    public function test_crea_familia_con_un_titular_y_total_backend(): void
    {
        $destino = $this->destino();
        $titular = $this->cliente(40);

        $datos = $this->datosFamilia($destino, $titular, [
            'cantidad_infantes' => 1,
            'cantidad_ninos' => 1,
            'cantidad_adultos' => 2,
            'cantidad_adultos_mayores' => 1,
            'precio_total_viaje' => 1,
        ]);

        $reserva = app(ReservaGrupalService::class)
            ->guardar($datos, $this->usuario()->id);
        $reserva->load('grupo.clientes');

        $this->assertSame(5, $reserva->cantidad_viajeros);
        $this->assertSame(2400.0, (float) $reserva->precio_total_viaje);
        $this->assertSame($titular->id, $reserva->cliente_id);
        $this->assertTrue($reserva->grupo->usaCategoriasFamiliares());
        $this->assertSame($titular->id, $reserva->grupo->responsable_pago_id);
        $this->assertCount(1, $reserva->grupo->clientes);
        $this->assertTrue(
            (bool) $reserva->grupo->clientes->first()->pivot->es_lider
        );
        $this->assertSame(
            2400.0,
            (float) $reserva->grupo->clientes->first()->pivot->monto_asignado
        );
    }

    public function test_formulario_de_creacion_muestra_categorias_familiares(): void
    {
        $this->actingAs($this->usuario())
            ->get(route('reservas_grupal.create'))
            ->assertOk()
            ->assertSee('Titular y composición familiar')
            ->assertSee('cantidad_adultos', false);
    }

    public function test_titular_adulto_debe_estar_en_adultos(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cantidad de adultos');

        $destino = $this->destino();
        app(ReservaGrupalService::class)->guardar(
            $this->datosFamilia($destino, $this->cliente(30), [
                'cantidad_infantes' => 2,
                'cantidad_adultos' => 0,
            ]),
            $this->usuario()->id
        );
    }

    public function test_titular_mayor_debe_estar_en_adultos_mayores(): void
    {
        $destino = $this->destino();
        $titular = $this->cliente(61);

        $reserva = app(ReservaGrupalService::class)->guardar(
            $this->datosFamilia($destino, $titular, [
                'cantidad_ninos' => 1,
                'cantidad_adultos' => 0,
                'cantidad_adultos_mayores' => 1,
            ]),
            $this->usuario()->id
        );

        $this->assertSame(800.0, (float) $reserva->precio_total_viaje);
    }

    public function test_rechaza_titular_menor_y_revierte_transaccion(): void
    {
        try {
            $destino = $this->destino();
            app(ReservaGrupalService::class)->guardar(
                $this->datosFamilia($destino, $this->cliente(17)),
                $this->usuario()->id
            );
            $this->fail('La reserva debió ser rechazada.');
        } catch (InvalidArgumentException $error) {
            $this->assertStringContainsString(
                'mayor de edad',
                $error->getMessage()
            );
        }

        $this->assertDatabaseCount('reservas', 0);
        $this->assertDatabaseCount('grupos', 0);
        $this->assertDatabaseCount('grupos_clientes', 0);
    }

    public function test_rechaza_familia_de_una_persona(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('al menos dos viajeros');

        $destino = $this->destino();
        app(ReservaGrupalService::class)->guardar(
            $this->datosFamilia($destino, $this->cliente(35), [
                'cantidad_ninos' => 0,
                'cantidad_adultos' => 1,
            ]),
            $this->usuario()->id
        );
    }

    public function test_todos_los_viajeros_consumen_cupo(): void
    {
        $destino = $this->destino(5);
        $reserva = app(ReservaGrupalService::class)->guardar(
            $this->datosFamilia($destino, $this->cliente(35), [
                'cantidad_infantes' => 3,
                'cantidad_ninos' => 0,
                'cantidad_adultos' => 2,
            ]),
            $this->usuario()->id
        );

        $this->assertSame(
            0,
            app(CupoReservaService::class)->obtenerDisponibles($destino)
        );
        $this->assertSame(
            5,
            app(CupoReservaService::class)->obtenerDisponibles(
                $destino,
                $reserva->id
            )
        );
    }

    public function test_cupos_historicos_usan_relaciones_si_no_hay_cantidad(): void
    {
        $destino = $this->destino(5);
        $reserva = app(ReservaGrupalService::class)->guardar(
            $this->datosIntegrantes(
                $destino,
                $this->cliente(35),
                $this->cliente(12),
                Grupo::TIPO_FAMILIAR
            ),
            $this->usuario()->id
        );

        $reserva->update(['cantidad_viajeros' => null]);

        $this->assertSame(
            3,
            app(CupoReservaService::class)->obtenerDisponibles($destino)
        );
    }

    public function test_edita_familia_nueva_y_excluye_sus_cupos(): void
    {
        $destino = $this->destino(5);
        $titular = $this->cliente(35);
        $servicio = app(ReservaGrupalService::class);
        $reserva = $servicio->guardar(
            $this->datosFamilia($destino, $titular),
            $this->usuario()->id
        );

        $actualizada = $servicio->actualizar(
            $reserva->id,
            $this->datosFamilia($destino, $titular, [
                'cantidad_infantes' => 2,
                'cantidad_ninos' => 1,
                'cantidad_adultos' => 2,
            ])
        );

        $this->assertSame(5, $actualizada->cantidad_viajeros);
        $this->assertSame(2000.0, (float) $actualizada->precio_total_viaje);
        $this->assertDatabaseCount('grupos_clientes', 1);

        $this->actingAs(User::findOrFail($reserva->user_id))
            ->get(route('reservas_grupal.edit', $reserva->id))
            ->assertOk()
            ->assertSee('Titular y composición familiar');
    }

    public function test_familia_historica_conserva_integrantes_al_editar(): void
    {
        $destino = $this->destino();
        $lider = $this->cliente(35);
        $integrante = $this->cliente(10);
        $servicio = app(ReservaGrupalService::class);
        $datos = $this->datosIntegrantes(
            $destino,
            $lider,
            $integrante,
            Grupo::TIPO_FAMILIAR
        );
        $reserva = $servicio->guardar(
            $datos,
            $this->usuario()->id
        );

        $servicio->actualizar($reserva->id, $datos);
        $reserva->refresh()->load('grupo.clientes');

        $this->assertFalse($reserva->grupo->usaCategoriasFamiliares());
        $this->assertCount(2, $reserva->grupo->clientes);
    }

    public function test_independiente_conserva_tarifas_y_dos_clientes(): void
    {
        $destino = $this->destino();
        $adulto = $this->cliente(35);
        $nino = $this->cliente(10);

        $reserva = app(ReservaGrupalService::class)->guardar(
            $this->datosIntegrantes(
                $destino,
                $adulto,
                $nino,
                Grupo::TIPO_INDEPENDIENTE
            ),
            $this->usuario()->id
        );
        $reserva->load('grupo.clientes');

        $this->assertCount(2, $reserva->grupo->clientes);
        $this->assertSame(1200.0, (float) $reserva->precio_total_viaje);
        $this->assertSame(
            [400.0, 800.0],
            $reserva->grupo->clientes
                ->pluck('pivot.monto_asignado')
                ->map(fn ($monto) => (float) $monto)
                ->sort()
                ->values()
                ->all()
        );
    }

    public function test_pago_familiar_se_asigna_siempre_al_titular(): void
    {
        $destino = $this->destino();
        $titular = $this->cliente(35);
        $ajeno = $this->cliente(30);
        $usuario = $this->usuario();
        $reserva = app(ReservaGrupalService::class)->guardar(
            $this->datosFamilia($destino, $titular),
            $usuario->id
        );

        $pagoId = app(PagoService::class)->registrarPago([
            'reserva_id' => $reserva->id,
            'cliente_id' => $ajeno->id,
            'user_id' => $usuario->id,
            'monto_depositado' => 300,
            'metodo_pago' => Pago::METODO_EFECTIVO,
        ]);

        $this->assertDatabaseHas('pagos', [
            'id' => $pagoId,
            'cliente_id' => $titular->id,
            'monto_depositado' => 300,
        ]);
    }

    public function test_pago_familiar_no_supera_el_saldo_total(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('no puede superar');

        $destino = $this->destino();
        $titular = $this->cliente(35);
        $usuario = $this->usuario();
        $reserva = app(ReservaGrupalService::class)->guardar(
            $this->datosFamilia($destino, $titular),
            $usuario->id
        );

        app(PagoService::class)->registrarPago([
            'reserva_id' => $reserva->id,
            'cliente_id' => $titular->id,
            'user_id' => $usuario->id,
            'monto_depositado' =>
                (float) $reserva->precio_total_viaje + 1,
            'metodo_pago' => Pago::METODO_EFECTIVO,
        ]);
    }

    public function test_http_no_elimina_integrantes_de_familia_historica(): void
    {
        $destino = $this->destino();
        $lider = $this->cliente(35);
        $segundo = $this->cliente(20);
        $tercero = $this->cliente(10);
        $usuario = $this->usuario();
        $datos = $this->datosIntegrantes(
            $destino,
            $lider,
            $segundo,
            Grupo::TIPO_FAMILIAR
        );
        $reserva = app(ReservaGrupalService::class)
            ->guardar($datos, $usuario->id);
        $reserva->grupo->clientes()->attach($tercero->id, [
            'es_lider' => false,
            'monto_asignado' => 400,
            'edad_al_viajar' => 10,
            'categoria_tarifa' => Reserva::TARIFA_NINO,
            'porcentaje_tarifa' => 50,
        ]);

        $respuesta = $this->actingAs($usuario)->put(
            route('reservas_grupal.update', $reserva->id),
            $datos
        );

        $respuesta->assertSessionHasErrors('integrantes');
        $this->assertSame(3, $reserva->grupo->clientes()->count());
        $this->assertTrue($reserva->grupo->clientes()->whereKey($tercero->id)->exists());
    }

    public function test_http_rechaza_independiente_con_un_integrante(): void
    {
        $destino = $this->destino();
        $cliente = $this->cliente(35);

        $this->actingAs($this->usuario())->post(
            route('reservas_grupal.store'),
            [
                'nombre_grupo' => 'Grupo corto',
                'tipo_grupo' => Grupo::TIPO_INDEPENDIENTE,
                'destino_id' => $destino->id,
                'integrantes' => [[
                    'cliente_id' => $cliente->id,
                    'es_lider' => true,
                ]],
            ]
        )->assertSessionHasErrors('integrantes');

        $this->assertDatabaseCount('reservas', 0);
    }

    public function test_http_rechaza_cantidad_familiar_decimal(): void
    {
        $destino = $this->destino();
        $titular = $this->cliente(35);

        $this->actingAs($this->usuario())->post(
            route('reservas_grupal.store'),
            $this->datosFamilia($destino, $titular, [
                'cantidad_ninos' => '1.5',
            ])
        )->assertSessionHasErrors('cantidad_ninos');

        $this->assertDatabaseCount('reservas', 0);
    }

    public function test_http_rechaza_cambio_de_modalidad_historica(): void
    {
        $destino = $this->destino();
        $usuario = $this->usuario();
        $reserva = app(ReservaGrupalService::class)->guardar(
            $this->datosIntegrantes(
                $destino,
                $this->cliente(35),
                $this->cliente(20),
                Grupo::TIPO_FAMILIAR
            ),
            $usuario->id
        );

        $this->actingAs($usuario)->put(
            route('reservas_grupal.update', $reserva->id),
            ['tipo_grupo' => Grupo::TIPO_INDEPENDIENTE]
        )->assertSessionHas('error');

        $this->assertSame(
            Grupo::TIPO_FAMILIAR,
            $reserva->grupo->fresh()->tipo_grupo
        );
    }

    public function test_rechaza_titular_inactivo(): void
    {
        $destino = $this->destino();
        $titular = $this->cliente(35);
        $titular->update(['estado' => Cliente::ESTADO_INACTIVO]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('activo');

        app(ReservaGrupalService::class)->guardar(
            $this->datosFamilia($destino, $titular),
            $this->usuario()->id
        );
    }

    public function test_detalle_familiar_separa_titular_y_desglose_economico(): void
    {
        $destino = $this->destino();
        $titular = $this->cliente(35);
        $usuario = $this->usuario();
        $reserva = app(ReservaGrupalService::class)->guardar(
            $this->datosFamilia($destino, $titular),
            $usuario->id
        );

        $this->actingAs($usuario)
            ->getJson(route('reservas.detalle', $reserva->id))
            ->assertOk()
            ->assertJsonPath('data.viajeros.0.categoria', 'Titular y responsable del pago')
            ->assertJsonPath('data.viajeros.0.precio', null)
            ->assertJsonPath('data.grupo.desglose_familiar.precio_total', 1200)
            ->assertJsonPath('data.grupo.desglose_familiar.subtotal_ninos', 400);
    }

    public function test_familia_nueva_no_puede_completar_expediente_solo_con_titular(): void
    {
        $destino = $this->destino();
        $usuario = $this->usuario();
        $reserva = app(ReservaGrupalService::class)->guardar(
            $this->datosFamilia($destino, $this->cliente(35)),
            $usuario->id
        );

        $this->actingAs($usuario)->post(
            route('operaciones.iniciar', $reserva->id)
        )->assertRedirect(route('operaciones.show', $reserva->id));

        $this->actingAs($usuario)
            ->get(route('operaciones.show', $reserva->id))
            ->assertOk()
            ->assertViewHas('totalViajerosEsperados', 2);

        $operacion = OperacionViaje::where('reserva_id', $reserva->id)->firstOrFail();
        $this->actingAs($usuario)->put(
            route('operaciones.update', $operacion),
            ['estado' => OperacionViaje::ESTADO_COMPLETO]
        )->assertSessionHas(
            'error',
            'Faltan los datos personales de los acompañantes antes de completar la documentación del viaje.'
        );

        $this->assertSame(OperacionViaje::ESTADO_PENDIENTE, $operacion->fresh()->estado);
    }

    public function test_edicion_cambia_paquete_y_recalcula_cupos(): void
    {
        $origen = $this->destino(5);
        $nuevo = $this->destino(4);
        $titular = $this->cliente(35);
        $servicio = app(ReservaGrupalService::class);
        $reserva = $servicio->guardar(
            $this->datosFamilia($origen, $titular),
            $this->usuario()->id
        );

        $servicio->actualizar(
            $reserva->id,
            $this->datosFamilia($nuevo, $titular, [
                'cantidad_infantes' => 1,
                'cantidad_ninos' => 1,
                'cantidad_adultos' => 2,
            ])
        );

        $cupos = app(CupoReservaService::class);
        $this->assertSame(5, $cupos->obtenerDisponibles($origen));
        $this->assertSame(0, $cupos->obtenerDisponibles($nuevo));
    }

    public function test_get_operacion_no_crea_operacion_ni_viajeros(): void
    {
        $usuario = $this->usuario();
        $reserva = app(ReservaGrupalService::class)->guardar(
            $this->datosFamilia($this->destino(), $this->cliente(35)),
            $usuario->id
        );
        $this->assertDatabaseCount('viajeros_reserva', 1);
        $this->assertDatabaseCount('operaciones_viaje', 0);

        $this->actingAs($usuario)->get(route('operaciones.show', $reserva->id))
            ->assertRedirect(route('operaciones.index'));

        $this->assertDatabaseCount('viajeros_reserva', 1);
        $this->assertDatabaseCount('operaciones_viaje', 0);
    }

    public function test_acompanante_opcional_no_crea_cliente_y_categoria_manipulada_se_ignora(): void
    {
        $usuario = $this->usuario();
        $destino = $this->destino();
        $reserva = app(ReservaGrupalService::class)->guardar(
            $this->datosFamilia($destino, $this->cliente(35)),
            $usuario->id
        );
        $operacion = $this->iniciarOperacion($reserva, $usuario);
        $clientesAntes = Cliente::count();

        $this->actingAs($usuario)->post(
            route('operaciones.viajeros.store', $operacion),
            [
                'nombres' => 'Niña',
                'apellidos' => 'Temporal',
                'fecha_nacimiento' => now()->addYear()->subYears(10)->format('Y-m-d'),
                'edad_al_viajar' => 40,
                'categoria_tarifa' => Reserva::TARIFA_ADULTO,
            ]
        )->assertSessionHas('success');

        $viajero = ViajeroReserva::where('reserva_id', $reserva->id)
            ->where('es_titular', false)->firstOrFail();
        $this->assertNull($viajero->cliente_id);
        $this->assertNull($viajero->documento);
        $this->assertSame(Reserva::TARIFA_NINO, $viajero->categoria_tarifa);
        $this->assertSame($clientesAntes, Cliente::count());
    }

    public function test_rechaza_categoria_excedida_y_titular_no_eliminable(): void
    {
        $reserva = app(ReservaGrupalService::class)->guardar(
            $this->datosFamilia($this->destino(), $this->cliente(35)),
            $this->usuario()->id
        );

        app(ViajeroReservaService::class)->guardar($reserva, [
            'nombres' => 'Primer', 'apellidos' => 'Niño',
            'fecha_nacimiento' => now()->addYear()->subYears(10)->format('Y-m-d'),
        ]);

        try {
            app(ViajeroReservaService::class)->guardar($reserva, [
                'nombres' => 'Segundo', 'apellidos' => 'Niño',
                'fecha_nacimiento' => now()->addYear()->subYears(9)->format('Y-m-d'),
            ]);
            $this->fail('Debió rechazar la categoría excedida.');
        } catch (\Illuminate\Validation\ValidationException $error) {
            $this->assertArrayHasKey('fecha_nacimiento', $error->errors());
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('titular');
        app(ViajeroReservaService::class)->eliminar(
            $reserva->viajerosReserva()->where('es_titular', true)->firstOrFail()
        );
    }

    public function test_documentos_se_validan_y_son_obligatorios_para_emitir_boleto(): void
    {
        $usuario = $this->usuario();
        $reserva = app(ReservaGrupalService::class)->guardar(
            $this->datosFamilia($this->destino(), $this->cliente(35)),
            $usuario->id
        );
        $operacion = $this->iniciarOperacion($reserva, $usuario);
        $viajero = app(ViajeroReservaService::class)->guardar($reserva, [
            'nombres' => 'Sin', 'apellidos' => 'Documento',
            'fecha_nacimiento' => now()->addYear()->subYears(10)->format('Y-m-d'),
        ]);
        $vuelo = $this->vuelo($operacion);

        $this->actingAs($usuario)->post(
            route('operaciones.boletos.store', $vuelo),
            [
                'viajero_reserva_id' => $viajero->id,
                'numero_boleto' => 'B-1',
                'estado_emision' => BoletoVuelo::ESTADO_EMITIDO,
            ]
        )->assertSessionHas('error');

        $this->assertDatabaseMissing('boletos_vuelo', ['viajero_reserva_id' => $viajero->id]);

        try {
            app(ViajeroReservaService::class)->actualizar($viajero, [
                'nombres' => 'Sin', 'apellidos' => 'Documento',
                'fecha_nacimiento' => $viajero->fecha_nacimiento->format('Y-m-d'),
                'tipo_documento' => 'cedula', 'documento' => '1234567890',
            ]);
            $this->fail('Debió rechazar la cédula.');
        } catch (\Illuminate\Validation\ValidationException $error) {
            $this->assertArrayHasKey('documento', $error->errors());
        }
    }

    public function test_infante_no_puede_tener_boleto(): void
    {
        $usuario = $this->usuario();
        $reserva = app(ReservaGrupalService::class)->guardar(
            $this->datosFamilia($this->destino(), $this->cliente(35), [
                'cantidad_infantes' => 1, 'cantidad_ninos' => 0,
            ]),
            $usuario->id
        );
        $operacion = $this->iniciarOperacion($reserva, $usuario);
        $infante = app(ViajeroReservaService::class)->guardar($reserva, [
            'nombres' => 'Bebé', 'apellidos' => 'Viajero',
            'fecha_nacimiento' => now()->addYear()->subYear()->format('Y-m-d'),
            'tipo_documento' => 'pasaporte', 'documento' => 'PASS1234',
        ]);
        $vuelo = $this->vuelo($operacion);

        $this->actingAs($usuario)->post(route('operaciones.boletos.store', $vuelo), [
            'viajero_reserva_id' => $infante->id,
            'numero_boleto' => 'INF-1',
            'estado_emision' => BoletoVuelo::ESTADO_EMITIDO,
            'asiento' => '',
        ])->assertSessionHasErrors('viajero_reserva_id');

        $this->assertDatabaseMissing('boletos_vuelo', [
            'viajero_reserva_id' => $infante->id,
        ]);
        $this->actingAs($usuario)
            ->get(route('operaciones.show', $reserva))
            ->assertOk()
            ->assertSee('Infante — no requiere boleto ni habitación')
            ->assertDontSee('data-persona-id="' . $infante->id . '"', false);
    }

    public function test_habitaciones_respetan_capacidad_y_evitan_duplicados(): void
    {
        $usuario = $this->usuario();
        $reserva = app(ReservaGrupalService::class)->guardar(
            $this->datosFamilia($this->destino(), $this->cliente(35)),
            $usuario->id
        );
        $operacion = $this->iniciarOperacion($reserva, $usuario);
        $nino = app(ViajeroReservaService::class)->guardar($reserva, [
            'nombres' => 'Niño', 'apellidos' => 'Habitación',
            'fecha_nacimiento' => now()->addYear()->subYears(10)->format('Y-m-d'),
        ]);
        $alojamiento = $this->alojamiento($operacion);
        $servicio = app(DistribucionHabitacionService::class);
        $habitacion = $servicio->guardarHabitacion($alojamiento, ['tipo' => 'individual']);
        $this->assertSame(1, $habitacion->capacidad);
        $servicio->asignar($habitacion, ['viajero_reserva_id' => $nino->id]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('capacidad');
        $servicio->asignar($habitacion, [
            'viajero_reserva_id' => $reserva->viajerosReserva()->where('es_titular', true)->value('id'),
        ]);
    }

    public function test_saldo_bloquea_completar_pero_no_preparacion(): void
    {
        $usuario = $this->usuario();
        $reserva = app(ReservaGrupalService::class)->guardar(
            $this->datosFamilia($this->destino(), $this->cliente(35)),
            $usuario->id
        );
        $operacion = $this->iniciarOperacion($reserva, $usuario);

        $progreso = app(ProgresoOperacionService::class)->calcular($operacion);
        $this->assertFalse($progreso['puede_completar']);
        $this->assertFalse($progreso['puede_notificar']);
        $this->assertGreaterThan(0, $progreso['saldo_pendiente']);

        $this->actingAs($usuario)->put(route('operaciones.update', $operacion), [
            'estado' => OperacionViaje::ESTADO_PREPARACION,
        ])->assertSessionHas('success');
        $this->assertSame(OperacionViaje::ESTADO_PREPARACION, $operacion->fresh()->estado);
    }

    public function test_progreso_con_varios_vuelos_excluye_infante_de_asiento(): void
    {
        $usuario = $this->usuario();
        $destino = $this->destino();
        $destino->update(['incluye' => ['Vuelo', 'Hotel']]);
        $reserva = app(ReservaGrupalService::class)->guardar(
            $this->datosFamilia($destino, $this->cliente(35), [
                'cantidad_infantes' => 1, 'cantidad_ninos' => 0,
            ]),
            $usuario->id
        );
        $operacion = $this->iniciarOperacion($reserva, $usuario);
        $infante = app(ViajeroReservaService::class)->guardar($reserva, [
            'nombres' => 'Infante', 'apellidos' => 'Completo',
            'fecha_nacimiento' => now()->addYear()->subYear()->format('Y-m-d'),
            'tipo_documento' => 'pasaporte', 'documento' => 'INF9999',
        ]);
        $titular = $reserva->viajerosReserva()->where('es_titular', true)->firstOrFail();
        $vuelos = collect([$this->vuelo($operacion), $this->vuelo($operacion)]);

        foreach ($vuelos as $indice => $vuelo) {
            BoletoVuelo::create([
                'vuelo_reserva_id' => $vuelo->id,
                'viajero_reserva_id' => $titular->id,
                'numero_boleto' => "T-{$indice}", 'asiento' => '10A',
                'estado_emision' => BoletoVuelo::ESTADO_EMITIDO,
            ]);
        }

        $alojamiento = $this->alojamiento($operacion);
        $distribucion = app(DistribucionHabitacionService::class);
        $habitacion = $distribucion->guardarHabitacion(
            $alojamiento,
            ['tipo' => 'individual']
        );
        $distribucion->asignar($habitacion, [
            'viajero_reserva_id' => $titular->id,
        ]);

        app(PagoService::class)->registrarPago([
            'reserva_id' => $reserva->id, 'cliente_id' => $reserva->cliente_id,
            'user_id' => $usuario->id,
            'monto_depositado' => (float) $reserva->precio_total_viaje,
            'metodo_pago' => Pago::METODO_EFECTIVO,
        ]);

        $progreso = app(ProgresoOperacionService::class)->calcular($operacion->fresh());
        $this->assertSame(['actual' => 1, 'total' => 1, 'aplica' => true], $progreso['boletos_emitidos']);
        $this->assertSame(['actual' => 1, 'total' => 1, 'aplica' => true], $progreso['asientos_asignados']);
        $this->assertSame(['actual' => 1, 'total' => 1, 'aplica' => true], $progreso['viajeros_con_habitacion']);
        $this->assertTrue($progreso['puede_completar']);
        $this->assertSame(100, $progreso['porcentaje_general']);
    }

    public function test_boleto_y_habitacion_historicos_conservan_cliente_id(): void
    {
        $usuario = $this->usuario();
        $lider = $this->cliente(35);
        $integrante = $this->cliente(20);
        $reserva = app(ReservaGrupalService::class)->guardar(
            $this->datosIntegrantes(
                $this->destino(), $lider, $integrante, Grupo::TIPO_INDEPENDIENTE
            ),
            $usuario->id
        );
        $operacion = $this->iniciarOperacion($reserva, $usuario);
        $vuelo = $this->vuelo($operacion);

        $this->actingAs($usuario)->post(route('operaciones.boletos.store', $vuelo), [
            'cliente_id' => $integrante->id,
            'numero_boleto' => 'HIST-1', 'asiento' => '2B',
            'estado_emision' => BoletoVuelo::ESTADO_EMITIDO,
        ])->assertSessionHas('success');

        $alojamiento = $this->alojamiento($operacion);
        $servicio = app(DistribucionHabitacionService::class);
        $habitacion = $servicio->guardarHabitacion($alojamiento, ['tipo' => 'doble']);
        $servicio->asignar($habitacion, ['cliente_id' => $integrante->id]);

        $this->assertDatabaseHas('boletos_vuelo', [
            'cliente_id' => $integrante->id, 'viajero_reserva_id' => null,
        ]);
        $this->assertDatabaseHas('asignaciones_habitacion', [
            'cliente_id' => $integrante->id, 'viajero_reserva_id' => null,
        ]);
    }

    public function test_rollback_de_boletos_se_protege_si_hay_viajero_exclusivo(): void
    {
        $usuario = $this->usuario();
        $reserva = app(ReservaGrupalService::class)->guardar(
            $this->datosFamilia($this->destino(), $this->cliente(35)),
            $usuario->id
        );
        $operacion = $this->iniciarOperacion($reserva, $usuario);
        $viajero = $reserva->viajerosReserva()->firstOrFail();
        BoletoVuelo::create([
            'vuelo_reserva_id' => $this->vuelo($operacion)->id,
            'viajero_reserva_id' => $viajero->id,
            'numero_boleto' => 'PROTEGIDO',
            'estado_emision' => BoletoVuelo::ESTADO_EMITIDO,
        ]);

        $migracion = require database_path(
            'migrations/2026_08_02_140100_add_viajero_reserva_to_boletos_vuelo_table.php'
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('existen boletos vinculados exclusivamente');
        $migracion->down();
    }

    public function test_documento_valido_se_normaliza_y_referencias_ajenas_se_rechazan(): void
    {
        $servicioViajeros = app(ViajeroReservaService::class);
        $this->assertSame(
            ['tipo_documento' => 'cedula', 'documento' => '1710034065'],
            $servicioViajeros->validarYNormalizarDocumento([
                'tipo_documento' => 'CEDULA',
                'documento' => '1710034065',
            ])
        );

        $usuario = $this->usuario();
        $reservaA = app(ReservaGrupalService::class)->guardar(
            $this->datosFamilia($this->destino(), $this->cliente(35)),
            $usuario->id
        );
        $reservaB = app(ReservaGrupalService::class)->guardar(
            $this->datosFamilia($this->destino(), $this->cliente(36)),
            $usuario->id
        );
        $operacionA = $this->iniciarOperacion($reservaA, $usuario);
        $this->iniciarOperacion($reservaB, $usuario);
        $ajeno = $reservaB->viajerosReserva()->firstOrFail();
        $vuelo = $this->vuelo($operacionA);

        $this->actingAs($usuario)->post(route('operaciones.boletos.store', $vuelo), [
            'viajero_reserva_id' => $ajeno->id,
            'numero_boleto' => 'AJENO',
            'estado_emision' => BoletoVuelo::ESTADO_EMITIDO,
        ])->assertSessionHas('error');

        $alojamiento = $this->alojamiento($operacionA);
        $habitacion = app(DistribucionHabitacionService::class)
            ->guardarHabitacion($alojamiento, ['tipo' => 'doble']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('no pertenece');
        app(DistribucionHabitacionService::class)->asignar(
            $habitacion,
            ['viajero_reserva_id' => $ajeno->id]
        );
    }

    public function test_boleto_rechaza_dos_identificadores_a_la_vez(): void
    {
        $usuario = $this->usuario();
        $reserva = app(ReservaGrupalService::class)->guardar(
            $this->datosFamilia($this->destino(), $this->cliente(35)),
            $usuario->id
        );
        $operacion = $this->iniciarOperacion($reserva, $usuario);
        $viajero = $reserva->viajerosReserva()->firstOrFail();

        $this->actingAs($usuario)->post(
            route('operaciones.boletos.store', $this->vuelo($operacion)),
            [
                'cliente_id' => $reserva->cliente_id,
                'viajero_reserva_id' => $viajero->id,
                'estado_emision' => BoletoVuelo::ESTADO_PENDIENTE,
            ]
        )->assertSessionHas('error');

        $this->assertDatabaseCount('boletos_vuelo', 0);
    }

    public function test_infante_no_puede_asignarse_a_habitacion_y_no_consume_capacidad(): void
    {
        $usuario = $this->usuario();
        $reserva = app(ReservaGrupalService::class)->guardar(
            $this->datosFamilia($this->destino(), $this->cliente(35), [
                'cantidad_infantes' => 1, 'cantidad_ninos' => 0,
            ]),
            $usuario->id
        );
        $operacion = $this->iniciarOperacion($reserva, $usuario);
        $infante = app(ViajeroReservaService::class)->guardar($reserva, [
            'nombres' => 'Infante', 'apellidos' => 'Sin plaza',
            'fecha_nacimiento' => now()->addYear()->subYear()->format('Y-m-d'),
        ]);
        $habitacion = app(DistribucionHabitacionService::class)
            ->guardarHabitacion($this->alojamiento($operacion), ['tipo' => 'individual']);

        try {
            app(DistribucionHabitacionService::class)->asignar(
                $habitacion,
                ['viajero_reserva_id' => $infante->id]
            );
            $this->fail('El infante no debe ocupar una plaza.');
        } catch (InvalidArgumentException $error) {
            $this->assertSame(
                'Los infantes menores de 2 años no requieren una plaza individual en la habitación.',
                $error->getMessage()
            );
        }

        $this->assertDatabaseCount('asignaciones_habitacion', 0);
        $this->assertSame(0, $habitacion->fresh()->asignaciones()->count());
    }

    public function test_nino_desde_dos_anos_requiere_boleto_y_habitacion(): void
    {
        $usuario = $this->usuario();
        $reserva = app(ReservaGrupalService::class)->guardar(
            $this->datosFamilia($this->destino(), $this->cliente(35)),
            $usuario->id
        );
        $operacion = $this->iniciarOperacion($reserva, $usuario);
        app(ViajeroReservaService::class)->guardar($reserva, [
            'nombres' => 'Niño', 'apellidos' => 'Dos años',
            'fecha_nacimiento' => now()->addYear()->subYears(2)->format('Y-m-d'),
        ]);

        $progreso = app(ProgresoOperacionService::class)->calcular($operacion);
        $this->assertSame(2, $progreso['boletos_emitidos']['total']);
        $this->assertTrue(
            $progreso['personas']->where('categoria', Reserva::TARIFA_NINO)
                ->every(fn ($persona) => $persona['requiere_boleto'] && $persona['requiere_habitacion'])
        );
    }

    public function test_boleto_historico_de_infante_se_conserva_y_muestra_advertencia(): void
    {
        $usuario = $this->usuario();
        $adulto = $this->cliente(35);
        $infante = $this->cliente(1);
        $reserva = app(ReservaGrupalService::class)->guardar(
            $this->datosIntegrantes(
                $this->destino(),
                $adulto,
                $infante,
                Grupo::TIPO_INDEPENDIENTE
            ),
            $usuario->id
        );
        $operacion = $this->iniciarOperacion($reserva, $usuario);
        $boleto = BoletoVuelo::create([
            'vuelo_reserva_id' => $this->vuelo($operacion)->id,
            'cliente_id' => $infante->id,
            'numero_boleto' => 'HIST-INF',
            'estado_emision' => BoletoVuelo::ESTADO_EMITIDO,
        ]);

        $this->actingAs($usuario)
            ->get(route('operaciones.show', $reserva))
            ->assertOk()
            ->assertSee('Existe un boleto o asignación histórica; se conserva sin modificar.');

        $this->assertDatabaseHas('boletos_vuelo', ['id' => $boleto->id]);
    }

    private function iniciarOperacion(Reserva $reserva, User $usuario): OperacionViaje
    {
        $this->actingAs($usuario)->post(route('operaciones.iniciar', $reserva));
        return OperacionViaje::where('reserva_id', $reserva->id)->firstOrFail();
    }

    private function vuelo(OperacionViaje $operacion): VueloReserva
    {
        return VueloReserva::create([
            'operacion_viaje_id' => $operacion->id, 'tipo_tramo' => 'ida',
            'aerolinea' => 'Aerolínea', 'numero_vuelo' => 'AR1',
            'ciudad_origen' => 'Quito', 'ciudad_destino' => 'Guayaquil',
            'fecha_hora_salida' => now()->addYear(),
            'fecha_hora_llegada' => now()->addYear()->addHours(2),
            'moneda' => 'USD', 'estado' => 'confirmado',
        ]);
    }

    private function alojamiento(OperacionViaje $operacion): AlojamientoReserva
    {
        return AlojamientoReserva::create([
            'operacion_viaje_id' => $operacion->id, 'nombre_hotel' => 'Hotel',
            'ciudad' => 'Guayaquil', 'fecha_hora_entrada' => now()->addYear(),
            'fecha_hora_salida' => now()->addYear()->addDays(2),
            'tipo_habitacion' => 'doble', 'cantidad_habitaciones' => 1,
            'moneda' => 'USD', 'estado' => 'confirmado',
        ]);
    }

    private function datosFamilia(
        Destino $destino,
        Cliente $titular,
        array $cambios = []
    ): array {
        return array_merge([
            'nombre_grupo' => 'Familia Prueba',
            'tipo_grupo' => Grupo::TIPO_FAMILIAR,
            'destino_id' => $destino->id,
            'titular_id' => $titular->id,
            'cantidad_infantes' => 0,
            'cantidad_ninos' => 1,
            'cantidad_adultos' => 1,
            'cantidad_adultos_mayores' => 0,
            'usa_categorias_familiares' => true,
        ], $cambios);
    }

    private function datosIntegrantes(
        Destino $destino,
        Cliente $lider,
        Cliente $integrante,
        string $tipo
    ): array {
        return [
            'nombre_grupo' => 'Grupo Prueba',
            'tipo_grupo' => $tipo,
            'responsable_pago_id' =>
                $tipo === Grupo::TIPO_FAMILIAR
                    ? $lider->id
                    : null,
            'destino_id' => $destino->id,
            'usa_categorias_familiares' => false,
            'integrantes' => [
                ['cliente_id' => $lider->id, 'es_lider' => true],
                ['cliente_id' => $integrante->id, 'es_lider' => false],
            ],
        ];
    }

    private function cliente(int $edad): Cliente
    {
        static $numero = 0;
        $numero++;

        return Cliente::create([
            'nombres' => 'Cliente',
            'apellidos' => "Prueba {$numero}",
            'tipo_documento' => 'pasaporte',
            'documento' => "DOC{$numero}",
            'fecha_nacimiento' => now()
                ->addYear()
                ->subYears($edad)
                ->subDay()
                ->format('Y-m-d'),
            'nacionalidad' => 'Ecuatoriana',
            'email' => "cliente{$numero}@example.com",
            'telefono' => '0999999999',
            'estado' => Cliente::ESTADO_ACTIVO,
        ]);
    }

    private function destino(int $capacidad = 20): Destino
    {
        return Destino::create([
            'nombre_paquete' => 'Paquete familiar',
            'slug' => 'paquete-familiar-' . uniqid(),
            'etiqueta' => 'Oferta',
            'pais' => 'Ecuador',
            'ciudad_destino' => 'Galápagos',
            'categoria' => 'Familiar',
            'descripcion_corta' => 'Viaje de prueba',
            'descripcion' => 'Descripción del viaje',
            'ciudad_salida' => 'Quito',
            'fecha_salida' => now()->addYear()->format('Y-m-d'),
            'fecha_regreso' => now()->addYear()->addDays(5)->format('Y-m-d'),
            'precio' => 1000,
            'precio_promocional' => 800,
            'moneda' => 'USD',
            'dias' => 5,
            'noches' => 4,
            'capacidad' => $capacidad,
            'incluye' => ['Vuelo'],
            'no_incluye' => [],
            'itinerario' => [],
            'estado_publicacion' => 'publicado',
            'destacado' => false,
        ]);
    }

    private function usuario(): User
    {
        static $numero = 0;
        $numero++;

        return User::create([
            'nombres' => 'Usuario',
            'apellidos' => 'Prueba',
            'email' => "usuario{$numero}@example.com",
            'telefono' => '0999999999',
            'documento' => "USR{$numero}",
            'rol' => User::ROL_ADMIN,
            'estado' => User::ESTADO_ACTIVO,
            'password' => 'password',
        ]);
    }
}
