<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Destino;
use App\Models\Grupo;
use App\Models\Pago;
use App\Models\Reserva;
use App\Models\OperacionViaje;
use App\Models\User;
use App\Services\CupoReservaService;
use App\Services\PagoService;
use App\Services\ReservaGrupalService;
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
