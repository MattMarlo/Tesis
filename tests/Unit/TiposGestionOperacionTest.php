<?php

namespace Tests\Unit;

use App\Models\TareaOperacionViaje;
use PHPUnit\Framework\TestCase;

class TiposGestionOperacionTest extends TestCase
{
    public function test_define_los_diez_tipos_especificos(): void
    {
        $this->assertSame(
            [
                'vuelo',
                'alojamiento',
                'guia',
                'tren',
                'traslado',
                'entrada',
                'alimentacion',
                'actividad_reservada',
                'seguro',
                'otro',
            ],
            TareaOperacionViaje::TIPOS_SELECCIONABLES
        );
    }

    public function test_conserva_los_tipos_anteriores_por_compatibilidad(): void
    {
        $tiposPermitidos =
            TareaOperacionViaje::tiposPermitidos();

        $this->assertContains(
            TareaOperacionViaje::TIPO_RESERVA,
            $tiposPermitidos
        );

        $this->assertContains(
            TareaOperacionViaje::TIPO_ACTIVIDAD,
            $tiposPermitidos
        );
    }

    public function test_cada_tipo_tiene_una_accion_contextual(): void
    {
        $acciones =
            TareaOperacionViaje::accionesContextuales();

        foreach (
            TareaOperacionViaje::TIPOS_SELECCIONABLES
            as $tipo
        ) {
            $this->assertArrayHasKey(
                $tipo,
                $acciones
            );

            $this->assertNotSame(
                '',
                trim($acciones[$tipo])
            );
        }

        $this->assertSame(
            'Gestionar vuelo y boletos',
            $acciones[
                TareaOperacionViaje::TIPO_VUELO
            ]
        );

        $this->assertSame(
            'Gestionar hotel y habitaciones',
            $acciones[
                TareaOperacionViaje::TIPO_ALOJAMIENTO
            ]
        );

        $this->assertSame(
            'Gestionar guía',
            $acciones[
                TareaOperacionViaje::TIPO_GUIA
            ]
        );
    }
}