<?php

namespace Tests\Unit;

use App\Models\Destino;
use App\Services\TarifaReservaService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class TarifaReservaServiceTest extends TestCase
{
    public function test_calcula_familia_por_cantidades(): void
    {
        $destino = new Destino([
            'precio' => 1000,
            'precio_promocional' => 800,
        ]);

        $resultado = (new TarifaReservaService())
            ->calcularPorCantidadesFamiliares($destino, [
                'cantidad_infantes' => 1,
                'cantidad_ninos' => 2,
                'cantidad_adultos' => 2,
                'cantidad_adultos_mayores' => 1,
            ]);

        $this->assertSame(6, $resultado['cantidad_viajeros']);
        $this->assertSame(0.0, $resultado['subtotal_infantes']);
        $this->assertSame(800.0, $resultado['subtotal_ninos']);
        $this->assertSame(1600.0, $resultado['subtotal_adultos']);
        $this->assertSame(
            400.0,
            $resultado['subtotal_adultos_mayores']
        );
        $this->assertSame(2800.0, $resultado['precio_total']);
    }

    public function test_rechaza_cantidades_negativas_o_decimales(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new TarifaReservaService())
            ->calcularPorCantidadesFamiliares(
                new Destino(['precio' => 100]),
                [
                    'cantidad_infantes' => -1,
                    'cantidad_ninos' => 0,
                    'cantidad_adultos' => 1,
                    'cantidad_adultos_mayores' => 0,
                ]
            );
    }

    public function test_rechaza_una_cantidad_decimal(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new TarifaReservaService())
            ->calcularPorCantidadesFamiliares(
                new Destino(['precio' => 100]),
                [
                    'cantidad_infantes' => 0,
                    'cantidad_ninos' => '1.5',
                    'cantidad_adultos' => 1,
                    'cantidad_adultos_mayores' => 0,
                ]
            );
    }
}
