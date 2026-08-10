<?php

namespace Tests\Unit;

use App\Models\Reserva;
use App\Models\TareaOperacionViaje;
use App\Services\ProgramacionTareaContextualService;
use Tests\TestCase;

class ProgramacionTareaContextualServiceTest extends TestCase
{
    public function test_calcula_fecha_horas_y_ruta_desde_la_actividad(): void
    {
        $reserva = new Reserva([
            'fecha_viaje' => '2026-11-15',
        ]);

        $tarea = new TareaOperacionViaje([
            'dia' => 3,
            'hora_inicio' => '08:45',
            'hora_fin' => '10:00',
            'ubicacion' => 'Aeropuerto de Lima - Hotel Miraflores',
        ]);

        $programacion = (new ProgramacionTareaContextualService())
            ->resolver($tarea, $reserva);

        $this->assertSame('2026-11-17T08:45', $programacion['inicio_input']);
        $this->assertSame('2026-11-17T10:00', $programacion['fin_input']);
        $this->assertSame('Aeropuerto de Lima', $programacion['origen']);
        $this->assertSame('Hotel Miraflores', $programacion['destino']);
    }

    public function test_conserva_fecha_aunque_la_actividad_no_tenga_hora_inicial(): void
    {
        $reserva = new Reserva([
            'fecha_viaje' => '2026-11-15',
        ]);

        $tarea = new TareaOperacionViaje([
            'dia' => 1,
            'hora_inicio' => null,
            'hora_fin' => '18:00',
            'ubicacion' => 'Cusco',
        ]);

        $programacion = (new ProgramacionTareaContextualService())
            ->resolver($tarea, $reserva);

        $this->assertSame('2026-11-15T00:00', $programacion['inicio_input']);
        $this->assertSame('2026-11-15T18:00', $programacion['fin_input']);
        $this->assertSame('Cusco', $programacion['origen']);
        $this->assertNull($programacion['destino']);
    }
}
