<?php

namespace Tests\Feature;

use App\Models\AlojamientoReserva;
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
use App\Models\VueloReserva;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GestionOperativaModelosTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    private Cliente $cliente;

    private Destino $destino;

    private Reserva $reserva;

    private OperacionViaje $operacion;

    private ViajeroReserva $viajeroTitular;

    private ViajeroReserva $viajeroAcompanante;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = User::create([
            'nombres' =>
                'Administrador',

            'apellidos' =>
                'Operaciones',

            'email' =>
                'admin.gestiones@example.com',

            'telefono' =>
                '0999999801',

            'documento' =>
                'USR-GESTIONES-001',

            'rol' =>
                User::ROL_ADMIN,

            'estado' =>
                User::ESTADO_ACTIVO,

            'password' =>
                'password',
        ]);

        $this->cliente = Cliente::create([
            'nombres' =>
                'Adrián',

            'apellidos' =>
                'Fabara',

            'tipo_documento' =>
                Cliente::DOCUMENTO_PASAPORTE,

            'email' =>
                'adrian.gestiones@example.com',

            'telefono' =>
                '0999999802',

            'documento' =>
                'CLI-GESTIONES-001',

            'fecha_nacimiento' =>
                '1980-05-10',

            'nacionalidad' =>
                'Ecuatoriana',

            'estado' =>
                Cliente::ESTADO_ACTIVO,
        ]);

        $this->destino = Destino::create([
            'nombre_paquete' =>
                'Perú Imperial de prueba',

            'slug' =>
                'peru-imperial-gestion-modelos',

            'etiqueta' =>
                'Prueba operativa',

            'pais' =>
                'Perú',

            'ciudad_destino' =>
                'Cusco',

            'categoria' =>
                'Cultural',

            'descripcion_corta' =>
                'Paquete para probar gestiones operativas.',

            'descripcion' =>
                'Recorrido por Lima, Cusco y Machu Picchu.',

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
                'Vuelos',
                'Alojamiento',
                'Actividades',
            ],

            'no_incluye' => [
                'Gastos personales',
            ],

            'itinerario' => [],

            'estado_publicacion' =>
                'publicado',

            'destacado' =>
                false,
        ]);

        $this->reserva = Reserva::create([
            'codigo_reserva' =>
                'RES-GESTIONES-001',

            'cliente_id' =>
                $this->cliente->id,

            'destino_id' =>
                $this->destino->id,

            'user_id' =>
                $this->usuario->id,

            'tipo' =>
                Reserva::TIPO_GRUPAL,

            'fecha_reserva' =>
                '2026-08-09',

            'fecha_viaje' =>
                '2026-11-15',

            'precio_total_viaje' =>
                3000,

            'moneda' =>
                'USD',

            'cantidad_viajeros' =>
                2,

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

        $this->viajeroTitular =
            ViajeroReserva::create([
                'reserva_id' =>
                    $this->reserva->id,

                'cliente_id' =>
                    $this->cliente->id,

                'nombres' =>
                    'Adrián',

                'apellidos' =>
                    'Fabara',

                'tipo_documento' =>
                    Cliente::DOCUMENTO_PASAPORTE,

                'documento' =>
                    'PAS-GESTIONES-001',

                'fecha_nacimiento' =>
                    '1980-05-10',

                'edad_al_viajar' =>
                    46,

                'categoria_tarifa' =>
                    Reserva::TARIFA_ADULTO,

                'es_titular' =>
                    true,
            ]);

        $this->viajeroAcompanante =
            ViajeroReserva::create([
                'reserva_id' =>
                    $this->reserva->id,

                'cliente_id' =>
                    null,

                'nombres' =>
                    'Adela',

                'apellidos' =>
                    'Porras',

                'tipo_documento' =>
                    Cliente::DOCUMENTO_PASAPORTE,

                'documento' =>
                    'PAS-GESTIONES-002',

                'fecha_nacimiento' =>
                    '1960-04-20',

                'edad_al_viajar' =>
                    66,

                'categoria_tarifa' =>
                    Reserva::TARIFA_ADULTO_MAYOR,

                'es_titular' =>
                    false,
            ]);
    }

    public function test_gestion_generica_pertenece_a_la_operacion(): void
    {
        $gestion = $this->crearGestionTren();

        $this->assertTrue(
            $gestion->operacion->is(
                $this->operacion
            )
        );

        $this->assertTrue(
            $this->operacion
                ->gestionesOperativas
                ->contains(
                    fn (GestionOperativa $registro) =>
                        $registro->is($gestion)
                )
        );

        $this->assertSame(
            GestionOperativa::TIPO_TREN,
            $gestion->tipo
        );

        $this->assertSame(
            'Expedition',
            $gestion->datos_adicionales['clase']
        );
    }

    public function test_una_gestion_puede_vincularse_con_varias_tareas(): void
    {
        $gestion = $this->crearGestionTren();

        $tareaIda = $this->crearTarea([
            'actividad_uuid' =>
                '11111111-1111-4111-8111-111111111111',

            'nombre' =>
                'Tren hacia Machu Picchu',

            'tipo_gestion' =>
                TareaOperacionViaje::TIPO_TREN,
        ]);

        $tareaRegreso = $this->crearTarea([
            'actividad_uuid' =>
                '22222222-2222-4222-8222-222222222222',

            'nombre' =>
                'Tren de regreso a Ollantaytambo',

            'tipo_gestion' =>
                TareaOperacionViaje::TIPO_TREN,
        ]);

        $tareaIda
            ->gestionable()
            ->associate($gestion);

        $tareaIda->save();

        $tareaRegreso
            ->gestionable()
            ->associate($gestion);

        $tareaRegreso->save();

        $tareaIda->refresh();
        $tareaRegreso->refresh();
        $gestion->refresh();

        $this->assertInstanceOf(
            GestionOperativa::class,
            $tareaIda->gestionable
        );

        $this->assertTrue(
            $tareaIda->gestionable->is($gestion)
        );

        $this->assertTrue(
            $tareaRegreso->gestionable->is($gestion)
        );

        $this->assertCount(
            2,
            $gestion->tareas
        );

        $this->assertTrue(
            $tareaIda->tieneGestionVinculada()
        );

        $this->assertSame(
            'Gestionar reserva y boletos',
            $tareaIda->accionContextual()
        );
    }

    public function test_gestion_conserva_datos_individuales_por_viajero(): void
    {
        $gestion = $this->crearGestionTren();

        GestionOperativaViajero::create([
            'gestion_operativa_id' =>
                $gestion->id,

            'viajero_reserva_id' =>
                $this->viajeroTitular->id,

            'numero_documento' =>
                'TRAIN-0001',

            'asiento' =>
                '12A',

            'referencia_individual' =>
                'REF-ADRIAN',

            'estado' =>
                GestionOperativaViajero::ESTADO_CONFIRMADO,

            'restricciones' =>
                null,

            'observaciones' =>
                'Boleto emitido correctamente.',
        ]);

        GestionOperativaViajero::create([
            'gestion_operativa_id' =>
                $gestion->id,

            'viajero_reserva_id' =>
                $this->viajeroAcompanante->id,

            'numero_documento' =>
                'TRAIN-0002',

            'asiento' =>
                '12B',

            'referencia_individual' =>
                'REF-ADELA',

            'estado' =>
                GestionOperativaViajero::ESTADO_CONFIRMADO,

            'restricciones' =>
                'Requiere asistencia para caminar.',

            'observaciones' =>
                'Ubicar cerca de la salida.',
        ]);

        $gestion->refresh();

        $this->assertCount(
            2,
            $gestion->detallesViajeros
        );

        $this->assertCount(
            2,
            $gestion->viajeros
        );

        $detalleTitular =
            $gestion->detallesViajeros
                ->firstWhere(
                    'viajero_reserva_id',
                    $this->viajeroTitular->id
                );

        $this->assertSame(
            'TRAIN-0001',
            $detalleTitular->numero_documento
        );

        $this->assertSame(
            '12A',
            $detalleTitular->asiento
        );

        $this->assertTrue(
            $detalleTitular->estaConfirmado()
        );

        $this->assertTrue(
            $detalleTitular
                ->tieneReferenciaIndividual()
        );

        $this->assertTrue(
            $this->viajeroTitular
                ->estaEnGestionOperativa(
                    $gestion->id
                )
        );

        $this->assertTrue(
            $this->viajeroAcompanante
                ->estaEnGestionOperativa(
                    $gestion->id
                )
        );
    }

    public function test_tareas_pueden_vincular_vuelo_alojamiento_y_guia(): void
    {
        $vuelo = VueloReserva::create([
            'operacion_viaje_id' =>
                $this->operacion->id,

            'tipo_tramo' =>
                VueloReserva::TRAMO_IDA,

            'aerolinea' =>
                'LATAM',

            'numero_vuelo' =>
                'LA1234',

            'ciudad_origen' =>
                'Quito',

            'aeropuerto_origen' =>
                'Mariscal Sucre',

            'ciudad_destino' =>
                'Lima',

            'aeropuerto_destino' =>
                'Jorge Chávez',

            'fecha_hora_salida' =>
                '2026-11-15 06:00:00',

            'fecha_hora_llegada' =>
                '2026-11-15 08:15:00',

            'localizador_reserva' =>
                'VUELO-TEST',

            'moneda' =>
                'USD',

            'estado' =>
                VueloReserva::ESTADO_CONFIRMADO,
        ]);

        $alojamiento = AlojamientoReserva::create([
            'operacion_viaje_id' =>
                $this->operacion->id,

            'nombre_hotel' =>
                'Hotel Miraflores',

            'ciudad' =>
                'Lima',

            'pais' =>
                'Perú',

            'fecha_hora_entrada' =>
                '2026-11-15 15:00:00',

            'fecha_hora_salida' =>
                '2026-11-17 05:00:00',

            'codigo_confirmacion' =>
                'HOTEL-TEST',

            'cantidad_habitaciones' =>
                1,

            'moneda' =>
                'USD',

            'estado' =>
                AlojamientoReserva::ESTADO_CONFIRMADO,
        ]);

        $guia = GuiaReserva::create([
            'operacion_viaje_id' =>
                $this->operacion->id,

            'nombre_completo' =>
                'Guía Machu Picchu',

            'empresa' =>
                'Guías del Perú',

            'ciudad_servicio' =>
                'Machu Picchu',

            'telefono' =>
                '+51999999999',

            'fecha_inicio' =>
                '2026-11-19',

            'fecha_fin' =>
                '2026-11-19',

            'punto_encuentro' =>
                'Acceso al santuario',

            'fecha_hora_encuentro' =>
                '2026-11-19 09:30:00',

            'servicios_incluidos' =>
                'Recorrido cultural guiado.',

            'moneda' =>
                'USD',

            'estado' =>
                GuiaReserva::ESTADO_CONFIRMADO,
        ]);

        $tareaVuelo = $this->crearTarea([
            'actividad_uuid' =>
                '33333333-3333-4333-8333-333333333333',

            'nombre' =>
                'Vuelo Quito - Lima',

            'tipo_gestion' =>
                TareaOperacionViaje::TIPO_VUELO,
        ]);

        $tareaAlojamiento = $this->crearTarea([
            'actividad_uuid' =>
                '44444444-4444-4444-8444-444444444444',

            'nombre' =>
                'Registro en hotel de Lima',

            'tipo_gestion' =>
                TareaOperacionViaje::TIPO_ALOJAMIENTO,
        ]);

        $tareaGuia = $this->crearTarea([
            'actividad_uuid' =>
                '55555555-5555-4555-8555-555555555555',

            'nombre' =>
                'Recorrido guiado por Machu Picchu',

            'tipo_gestion' =>
                TareaOperacionViaje::TIPO_GUIA,
        ]);

        $tareaVuelo
            ->gestionable()
            ->associate($vuelo);

        $tareaVuelo->save();

        $tareaAlojamiento
            ->gestionable()
            ->associate($alojamiento);

        $tareaAlojamiento->save();

        $tareaGuia
            ->gestionable()
            ->associate($guia);

        $tareaGuia->save();

        $this->assertInstanceOf(
            VueloReserva::class,
            $tareaVuelo->fresh()->gestionable
        );

        $this->assertInstanceOf(
            AlojamientoReserva::class,
            $tareaAlojamiento->fresh()->gestionable
        );

        $this->assertInstanceOf(
            GuiaReserva::class,
            $tareaGuia->fresh()->gestionable
        );

        $this->assertCount(
            1,
            $vuelo->fresh()->tareas
        );

        $this->assertCount(
            1,
            $alojamiento->fresh()->tareas
        );

        $this->assertCount(
            1,
            $guia->fresh()->tareas
        );
    }

    private function crearGestionTren(): GestionOperativa
    {
        return GestionOperativa::create([
            'operacion_viaje_id' =>
                $this->operacion->id,

            'tipo' =>
                GestionOperativa::TIPO_TREN,

            'nombre' =>
                'Tren turístico a Machu Picchu',

            'proveedor' =>
                'PeruRail',

            'contacto' =>
                'Central de reservas',

            'telefono' =>
                '+51123456789',

            'correo' =>
                'reservas@example.com',

            'fecha_hora_inicio' =>
                '2026-11-19 06:40:00',

            'fecha_hora_fin' =>
                '2026-11-19 08:20:00',

            'ubicacion_origen' =>
                'Ollantaytambo',

            'destino' =>
                'Machu Picchu Pueblo',

            'cantidad_viajeros' =>
                2,

            'capacidad' =>
                2,

            'referencia_confirmacion' =>
                'PERURAIL-TEST',

            'costo_total' =>
                300,

            'moneda' =>
                'USD',

            'estado' =>
                GestionOperativa::ESTADO_CONFIRMADO,

            'observaciones' =>
                'Reserva confirmada para dos viajeros.',

            'datos_adicionales' => [
                'empresa_ferroviaria' =>
                    'PeruRail',

                'clase' =>
                    'Expedition',

                'ruta' =>
                    'Ollantaytambo - Machu Picchu Pueblo',
            ],

            'creado_por_user_id' =>
                $this->usuario->id,

            'actualizado_por_user_id' =>
                $this->usuario->id,
        ]);
    }

    private function crearTarea(
        array $datos
    ): TareaOperacionViaje {
        return TareaOperacionViaje::create(
            array_merge(
                [
                    'operacion_viaje_id' =>
                        $this->operacion->id,

                    'dia' =>
                        1,

                    'descripcion' =>
                        'Coordinación de prueba.',

                    'hora_inicio' =>
                        '08:00',

                    'hora_fin' =>
                        '09:00',

                    'ubicacion' =>
                        'Ubicación de prueba',

                    'estado' =>
                        TareaOperacionViaje::ESTADO_PENDIENTE,

                    'vigente' =>
                        true,
                ],
                $datos
            )
        );
    }
}