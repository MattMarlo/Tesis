<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Destino;
use App\Models\Devolucion;
use App\Models\OperacionViaje;
use App\Models\Pago;
use App\Models\Reserva;
use App\Models\ReservaRiesgo;
use App\Models\User;
use App\Services\CancelacionReservaService;
use App\Services\DevolucionService;
use App\Services\GestionRiesgoReservaService;
use App\Services\PagoService;
use App\Services\PoliticaPagoReservaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class PoliticaPagosReservaTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;
    private Cliente $cliente;
    private Destino $destino;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = User::create([
            'nombres' => 'Agente',
            'apellidos' => 'Prueba',
            'email' => 'agente-pagos@example.com',
            'telefono' => '0999999999',
            'documento' => 'USR-PAGOS',
            'rol' => User::ROL_ADMIN,
            'estado' => User::ESTADO_ACTIVO,
            'password' => 'password',
        ]);

        $this->cliente = Cliente::create([
            'nombres' => 'Cliente',
            'apellidos' => 'Prueba',
            'tipo_documento' =>
                Cliente::DOCUMENTO_PASAPORTE,
            'email' => 'cliente-pagos@example.com',
            'telefono' => '0999999998',
            'documento' => 'CLI-PAGOS',
            'fecha_nacimiento' => '1990-01-01',
            'nacionalidad' => 'Ecuatoriana',
            'estado' => Cliente::ESTADO_ACTIVO,
        ]);

        $this->destino = Destino::create([
            'etiqueta' => 'Prueba',
            'pais' => 'Ecuador',
            'nombre_paquete' => 'Paquete de prueba',
            'slug' => 'paquete-politica-pagos',
            'ciudad_destino' => 'Quito',
            'ciudad_salida' => 'Guayaquil',
            'fecha_salida' => '2026-04-01',
            'fecha_regreso' => '2026-04-05',
            'precio' => 1000,
            'moneda' => 'USD',
            'dias' => 5,
            'capacidad' => 20,
            'estado_publicacion' => 'publicado',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_anticipo_confirma_y_saldo_vence_treinta_dias_antes(): void
    {
        Carbon::setTestNow(
            '2026-01-01 10:00:00'
        );

        $reserva = $this->reserva(
            '2026-04-01',
            1000
        );

        $reserva = app(
            PoliticaPagoReservaService::class
        )->inicializar($reserva);

        $this->assertSame(
            300.0,
            (float) $reserva->monto_anticipo
        );

        $this->assertSame(
            '2026-01-04 23:59:59',
            $reserva
                ->fecha_limite_anticipo
                ->format('Y-m-d H:i:s')
        );

        $this->assertSame(
            '2026-03-02',
            $reserva
                ->fecha_vencimiento_saldo
                ->format('Y-m-d')
        );

        $pagoId = $this->pagar(
            $reserva,
            300
        );

        $reserva->refresh();

        $this->assertSame(
            Reserva::ESTADO_CONFIRMADA,
            $reserva->estado
        );

        $this->assertSame(
            Reserva::PAGO_PARCIAL,
            $reserva->estado_pago
        );

        $this->assertSame(
            Reserva::COBRANZA_AL_DIA,
            $reserva->estado_cobranza
        );

        $this->assertSame(
            Pago::CONCEPTO_ANTICIPO,
            Pago::findOrFail($pagoId)->concepto
        );
    }

    public function test_reserva_dentro_de_treinta_dias_exige_pago_completo(): void
    {
        Carbon::setTestNow(
            '2026-01-01 10:00:00'
        );

        $reserva = app(
            PoliticaPagoReservaService::class
        )->inicializar(
            $this->reserva(
                '2026-01-20',
                1000
            )
        );

        $this->assertSame(
            1000.0,
            (float) $reserva->monto_anticipo
        );

        try {
            $this->pagar(
                $reserva,
                999
            );

            $this->fail(
                'Una reserva cercana no debe aceptar un anticipo incompleto.'
            );
        } catch (InvalidArgumentException $error) {
            $this->assertStringContainsString(
                'anticipo mínimo',
                $error->getMessage()
            );
        }

        $this->pagar(
            $reserva,
            1000
        );

        $this->assertSame(
            Reserva::ESTADO_CONFIRMADA,
            $reserva->fresh()->estado
        );
    }

    public function test_despues_de_siete_dias_en_riesgo_se_cancela_con_o_sin_abonos(): void
    {
        Carbon::setTestNow(
            '2026-01-01 10:00:00'
        );

        $sinPago = $this->aceptar(
            app(
                PoliticaPagoReservaService::class
            )->inicializar(
                $this->reserva(
                    '2026-04-01',
                    1000
                )
            )
        );

        Carbon::setTestNow(
            '2026-01-12 10:00:00'
        );

        $resultado = app(
            GestionRiesgoReservaService::class
        )->evaluarTodas();

        $this->assertSame(
            1,
            $resultado['canceladas']
        );

        $this->assertTrue(
            $sinPago
                ->fresh()
                ->estaCancelada()
        );

        Carbon::setTestNow(
            '2026-02-01 10:00:00'
        );

        $conAbono = $this->aceptar(
            app(
                PoliticaPagoReservaService::class
            )->inicializar(
                $this->reserva(
                    '2026-05-01',
                    1000
                )
            )
        );

        $this->pagar(
            $conAbono,
            300
        );

        Carbon::setTestNow(
            '2026-04-09 10:00:00'
        );

        $resultado = app(
            GestionRiesgoReservaService::class
        )->evaluarTodas();

        $conAbono->refresh();

        $this->assertSame(
            1,
            $resultado['canceladas']
        );

        $this->assertTrue(
            $conAbono->estaCancelada()
        );

        $this->assertSame(
            Reserva::COBRANZA_CANCELADA,
            $conAbono->estado_cobranza
        );

        $this->assertSame(
            300.0,
            (float) $conAbono->monto_reembolsable
        );

        $this->assertDatabaseHas(
            'reservas_riesgo',
            [
                'reserva_id' =>
                    $conAbono->id,

                'estado' =>
                    ReservaRiesgo::ESTADO_CANCELADA,
            ]
        );
    }

    public function test_saldo_pasa_a_riesgo_despues_del_vencimiento(): void
    {
        Carbon::setTestNow(
            '2026-01-01 10:00:00'
        );

        $reserva = $this->aceptar(
            app(
                PoliticaPagoReservaService::class
            )->inicializar(
                $this->reserva(
                    '2026-04-01',
                    1000
                )
            )
        );

        $this->pagar(
            $reserva,
            300
        );

        Carbon::setTestNow(
            '2026-03-02 23:59:59'
        );

        app(
            GestionRiesgoReservaService::class
        )->evaluarTodas();

        $this->assertSame(
            Reserva::COBRANZA_AL_DIA,
            $reserva
                ->fresh()
                ->estado_cobranza
        );

        Carbon::setTestNow(
            '2026-03-03 00:01:00'
        );

        app(
            GestionRiesgoReservaService::class
        )->evaluarTodas();

        $riesgo = ReservaRiesgo::query()
            ->where(
                'reserva_id',
                $reserva->id
            )
            ->firstOrFail();

        $this->assertSame(
            ReservaRiesgo::TIPO_SALDO,
            $riesgo->tipo
        );

        $this->assertSame(
            700.0,
            (float) $riesgo->saldo_al_ingresar
        );

        $this->assertSame(
            '2026-03-09 23:59:59',
            $riesgo
                ->fecha_limite_regularizacion
                ->format('Y-m-d H:i:s')
        );
    }

    public function test_reserva_legacy_sin_aceptacion_no_se_cancela_automaticamente(): void
    {
        Carbon::setTestNow(
            '2026-01-01 10:00:00'
        );

        $reserva = app(
            PoliticaPagoReservaService::class
        )->inicializar(
            $this->reserva(
                '2026-04-01',
                1000
            )
        );

        Carbon::setTestNow(
            '2026-01-20 10:00:00'
        );

        $resultado = app(
            GestionRiesgoReservaService::class
        )->evaluarTodas();

        $this->assertSame(
            1,
            $resultado['omitidas_sin_aceptacion']
        );

        $this->assertFalse(
            $reserva
                ->fresh()
                ->estaCancelada()
        );

        $this->assertDatabaseMissing(
            'reservas_riesgo',
            [
                'reserva_id' =>
                    $reserva->id,
            ]
        );
    }

    public function test_reserva_con_preparacion_tambien_se_cancela_al_vencer_la_gracia(): void
    {
        Carbon::setTestNow(
            '2026-01-01 10:00:00'
        );

        $reserva = $this->aceptar(
            app(
                PoliticaPagoReservaService::class
            )->inicializar(
                $this->reserva(
                    '2026-04-01',
                    1000
                )
            )
        );

        OperacionViaje::create([
            'reserva_id' =>
                $reserva->id,

            'estado' =>
                OperacionViaje::ESTADO_PREPARACION,

            'creado_por_user_id' =>
                $this->usuario->id,
        ]);

        Carbon::setTestNow(
            '2026-01-12 10:00:00'
        );

        $resultado = app(
            GestionRiesgoReservaService::class
        )->evaluarTodas();

        $this->assertSame(
            1,
            $resultado['canceladas']
        );

        $this->assertTrue(
            $reserva
                ->fresh()
                ->estaCancelada()
        );

        $this->assertSame(
            Reserva::COBRANZA_CANCELADA,
            $reserva
                ->fresh()
                ->estado_cobranza
        );
    }

    public function test_cancelacion_liquida_costos_y_limita_la_devolucion(): void
    {
        Carbon::setTestNow(
            '2026-01-01 10:00:00'
        );

        $reserva = app(
            PoliticaPagoReservaService::class
        )->inicializar(
            $this->reserva(
                '2026-04-01',
                1000
            )
        );

        $pagoId = $this->pagar(
            $reserva,
            1000
        );

        app(
            CancelacionReservaService::class
        )->cancelar(
            $reserva,
            [
                'motivo_cancelacion' =>
                    'El cliente sufrió un percance dos días antes del viaje.',

                'tipo_cancelacion' =>
                    'fuerza_mayor',

                'gastos_no_reembolsables' =>
                    800,

                'detalle_gastos_no_reembolsables' =>
                    'Boleto aéreo emitido y hotel con penalidad documentada.',

                'evidencia_cancelacion' =>
                    'Certificado revisado y validado por la agencia.',
            ],
            $this->usuario->id
        );

        $reserva->refresh();

        $this->assertSame(
            200.0,
            (float) $reserva->monto_reembolsable
        );

        $this->assertSame(
            Reserva::REEMBOLSO_PENDIENTE,
            $reserva->estado_reembolso
        );

        $this->expectException(
            InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            'monto autorizado'
        );

        app(
            DevolucionService::class
        )->registrar([
            'pago_id' =>
                $pagoId,

            'monto' =>
                201,

            'metodo' =>
                'efectivo',

            'tipo' =>
                'cancelacion',

            'motivo' =>
                'Liquidación de la cancelación del viaje.',

            'user_id' =>
                $this->usuario->id,
        ]);
    }

    public function test_devolucion_autorizada_actualiza_neto_y_estado(): void
    {
        Carbon::setTestNow(
            '2026-01-01 10:00:00'
        );

        $reserva = app(
            PoliticaPagoReservaService::class
        )->inicializar(
            $this->reserva(
                '2026-04-01',
                1000
            )
        );

        $pagoId = $this->pagar(
            $reserva,
            1000
        );

        app(
            CancelacionReservaService::class
        )->cancelar(
            $reserva,
            [
                'motivo_cancelacion' =>
                    'El cliente solicita cancelar por una causa documentada.',

                'tipo_cancelacion' =>
                    'cliente',

                'gastos_no_reembolsables' =>
                    800,

                'detalle_gastos_no_reembolsables' =>
                    'Penalidades justificadas de aerolínea y alojamiento.',
            ],
            $this->usuario->id
        );

        app(
            DevolucionService::class
        )->registrar([
            'pago_id' =>
                $pagoId,

            'monto' =>
                200,

            'metodo' =>
                'efectivo',

            'tipo' =>
                'cancelacion',

            'motivo' =>
                'Pago del excedente aprobado en la liquidación.',

            'user_id' =>
                $this->usuario->id,
        ]);

        $reserva
            ->refresh()
            ->load([
                'pagos',
                'devoluciones',
            ]);

        $this->assertSame(
            800.0,
            $reserva->total_pagado
        );

        $this->assertSame(
            Reserva::REEMBOLSO_COMPLETADO,
            $reserva->estado_reembolso
        );

        $this->assertSame(
            Reserva::PAGO_PARCIAL,
            $reserva->estado_pago
        );
    }

    public function test_pago_reembolsado_no_se_puede_editar_ni_anular(): void
    {
        Carbon::setTestNow(
            '2026-01-01 10:00:00'
        );

        $reserva = app(
            PoliticaPagoReservaService::class
        )->inicializar(
            $this->reserva(
                '2026-04-01',
                1000
            )
        );

        $pagoId = $this->pagar(
            $reserva,
            300
        );

        app(
            DevolucionService::class
        )->registrar([
            'pago_id' =>
                $pagoId,

            'monto' =>
                50,

            'metodo' =>
                'efectivo',

            'tipo' =>
                'correccion',

            'motivo' =>
                'Corrección parcial del importe recibido.',

            'user_id' =>
                $this->usuario->id,
        ]);

        $this->assertSame(
            250.0,
            $reserva->fresh()->total_pagado
        );

        try {
            app(
                PagoService::class
            )->actualizarPago(
                $pagoId,
                [
                    'monto_depositado' =>
                        250,

                    'metodo_pago' =>
                        Pago::METODO_EFECTIVO,
                ]
            );

            $this->fail(
                'El pago reembolsado no debió poder editarse.'
            );
        } catch (InvalidArgumentException $error) {
            $this->assertStringContainsString(
                'devoluciones procesadas',
                $error->getMessage()
            );
        }

        $this->expectException(
            InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            'devoluciones procesadas'
        );

        app(
            PagoService::class
        )->anularPago(
            $pagoId,
            'Corrección administrativa suficientemente explicada.',
            $this->usuario->id
        );
    }

    public function test_creacion_http_exige_aceptacion_y_redirige_al_anticipo(): void
    {
        Carbon::setTestNow(
            '2026-01-01 10:00:00'
        );

        $this
            ->actingAs($this->usuario)
            ->post(
                route(
                    'reservas_individual.store'
                ),
                [
                    'cliente_id' =>
                        $this->cliente->id,

                    'destino_id' =>
                        $this->destino->id,
                ]
            )
            ->assertSessionHasErrors([
                'politica_aceptada',
                'canal_aceptacion_politica',
                'referencia_aceptacion_politica',
            ]);

        $respuesta = $this
            ->actingAs($this->usuario)
            ->post(
                route(
                    'reservas_individual.store'
                ),
                [
                    'cliente_id' =>
                        $this->cliente->id,

                    'destino_id' =>
                        $this->destino->id,

                    'politica_aceptada' =>
                        '1',

                    'canal_aceptacion_politica' =>
                        'correo',

                    'referencia_aceptacion_politica' =>
                        'Correo confirmado el 01/01/2026',
                ]
            );

        $reserva = Reserva::query()
            ->latest('id')
            ->firstOrFail();

        $respuesta->assertRedirect(
            route(
                'pagos',
                [
                    'reserva_id' =>
                        $reserva->id,

                    'abrir_cobro' =>
                        1,
                ]
            )
        );

        $this->assertNotNull(
            $reserva->politica_aceptada_at
        );

        $this->assertStringContainsString(
            'El anticipo',
            $reserva->politica_pago_aceptada
        );
    }

    public function test_modulos_de_riesgo_y_devoluciones_estan_operativos(): void
    {
        $this
            ->actingAs($this->usuario)
            ->get(
                route('reservas.riesgo')
            )
            ->assertOk()
            ->assertSee(
                'Reservas en riesgo'
            );

        $this
            ->actingAs($this->usuario)
            ->get(
                route('devoluciones.index')
            )
            ->assertOk()
            ->assertSee(
                'Devoluciones'
            );
    }

    private function reserva(
        string $fechaViaje,
        float $total
    ): Reserva {
        return Reserva::create([
            'codigo_reserva' =>
                'RES-' . uniqid(),

            'cliente_id' =>
                $this->cliente->id,

            'destino_id' =>
                $this->destino->id,

            'user_id' =>
                $this->usuario->id,

            'tipo' =>
                Reserva::TIPO_INDIVIDUAL,

            'fecha_reserva' =>
                now()->toDateString(),

            'fecha_viaje' =>
                $fechaViaje,

            'precio_total_viaje' =>
                $total,

            'moneda' =>
                'USD',

            'cantidad_viajeros' =>
                1,

            'estado' =>
                Reserva::ESTADO_PENDIENTE,

            'estado_pago' =>
                Reserva::PAGO_PENDIENTE,
        ]);
    }

    private function pagar(
        Reserva $reserva,
        float $monto
    ): int {
        return app(
            PagoService::class
        )->registrarPago([
            'reserva_id' =>
                $reserva->id,

            'cliente_id' =>
                $this->cliente->id,

            'user_id' =>
                $this->usuario->id,

            'monto_depositado' =>
                $monto,

            'metodo_pago' =>
                Pago::METODO_EFECTIVO,
        ]);
    }

    private function aceptar(
        Reserva $reserva
    ): Reserva {
        return app(
            PoliticaPagoReservaService::class
        )->registrarAceptacion(
            $reserva,
            [
                'canal_aceptacion_politica' =>
                    'correo',

                'referencia_aceptacion_politica' =>
                    'Aceptación registrada para la prueba automatizada.',
            ]
        );
    }
}