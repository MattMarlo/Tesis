<?php

namespace Tests\Feature;

use App\Models\Destino;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelegramReservaApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_api_es_accesible_sin_secreto(): void
    {
        $this->getJson('/api/telegram/destinos')->assertOk();
    }

    public function test_lista_y_cotiza_usando_las_reglas_del_sistema(): void
    {
        $destino = $this->destino();

        $this->getJson('/api/telegram/destinos')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('destinos.0.id', $destino->id)
            ->assertJsonPath('destinos.0.precio_desde', 800);

        $this->postJson('/api/telegram/cotizar', [
                'destino_id' => $destino->id,
                'viajeros' => [
                    ['fecha_nacimiento' => now()->subYears(30)->format('Y-m-d')],
                    ['fecha_nacimiento' => now()->subYears(8)->format('Y-m-d')],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('precio_total', 1200)
            ->assertJsonPath('detalle.1.categoria', 'nino');
    }

    public function test_crea_prerreserva_individual_detallada_e_idempotente(): void
    {
        $destino = $this->destino();
        $payload = [
            'tipo_reserva' => 'individual',
            'destino_id' => $destino->id,
            'telegram_chat_id' => '123456',
            'referencia_externa' => 'tg-123456-100',
            'acepta_condiciones' => true,
            'cliente' => [
                'nombres' => 'Maria',
                'apellidos' => 'Perez',
                'tipo_documento' => 'pasaporte',
                'documento' => 'PA123456',
                'fecha_nacimiento' => now()->subYears(30)->format('Y-m-d'),
                'fecha_caducidad_documento' => now()->addYears(3)->format('Y-m-d'),
                'nacionalidad' => 'Ecuatoriana',
                'email' => 'maria@example.com',
                'telefono' => '0999999999',
                'contacto_emergencia' => 'Carlos Perez',
                'telefono_emergencia' => '0988888888',
            ],
        ];

        $this->postJson('/api/telegram/prerreservas', $payload)
            ->assertCreated()
            ->assertJsonPath('precio_estimado', 800);

        $this->postJson('/api/telegram/prerreservas', $payload)
            ->assertOk()
            ->assertJsonPath('duplicada', true);

        $this->assertDatabaseCount('pre_reservas', 1);
        $this->assertDatabaseCount('pre_reserva_integrantes', 1);
        $this->assertDatabaseHas('pre_reservas', [
            'referencia_externa' => 'tg-123456-100',
            'tipo_reserva' => 'individual',
            'acepta_condiciones' => true,
        ]);
    }

    public function test_rechaza_grupo_cuyo_lider_es_menor(): void
    {
        $destino = $this->destino();
        $viajero = fn (string $documento, int $edad) => [
            'nombres' => 'Viajero',
            'apellidos' => 'Prueba',
            'tipo_documento' => 'pasaporte',
            'documento' => $documento,
            'fecha_nacimiento' => now()->subYears($edad)->format('Y-m-d'),
            'nacionalidad' => 'Ecuatoriana',
            'email' => 'viajero@example.com',
            'telefono' => '0999999999',
            'contacto_emergencia' => 'Contacto Prueba',
            'telefono_emergencia' => '0988888888',
        ];

        $this->postJson('/api/telegram/prerreservas', [
                'tipo_reserva' => 'grupal',
                'tipo_grupo' => 'familiar',
                'nombre_grupo' => 'Familia Prueba',
                'destino_id' => $destino->id,
                'telegram_chat_id' => '789',
                'referencia_externa' => 'tg-789-1',
                'acepta_condiciones' => true,
                'lider_indice' => 0,
                'responsable_pago_indice' => 1,
                'integrantes' => [
                    $viajero('MENOR123', 10),
                    $viajero('ADULTO123', 35),
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('lider_indice');
    }

    private function destino(): Destino
    {
        return Destino::create([
            'nombre_paquete' => 'Cartagena 5 dias',
            'slug' => 'cartagena-5-dias',
            'etiqueta' => 'Oferta',
            'pais' => 'Colombia',
            'ciudad_destino' => 'Cartagena',
            'categoria' => 'Playa',
            'descripcion_corta' => 'Viaje de prueba',
            'descripcion' => 'Descripcion del viaje de prueba',
            'ciudad_salida' => 'Quito',
            'fecha_salida' => now()->addYear()->format('Y-m-d'),
            'fecha_regreso' => now()->addYear()->addDays(5)->format('Y-m-d'),
            'precio' => 1000,
            'precio_promocional' => 800,
            'moneda' => 'USD',
            'dias' => 5,
            'noches' => 4,
            'capacidad' => 20,
            'incluye' => ['Vuelo'],
            'no_incluye' => ['Gastos personales'],
            'itinerario' => [['dia' => 1, 'titulo' => 'Llegada', 'descripcion' => 'Recepcion']],
            'estado_publicacion' => 'publicado',
            'destacado' => false,
        ]);
    }
}
