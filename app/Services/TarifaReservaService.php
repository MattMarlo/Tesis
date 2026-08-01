<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Destino;
use App\Models\Reserva;
use Carbon\Carbon;
use InvalidArgumentException;

class TarifaReservaService
{
    public function calcular(
        Cliente $cliente,
        Destino $destino
    ): array {
        if (!$cliente->fecha_nacimiento) {
            throw new InvalidArgumentException(
                "El cliente {$cliente->nombre_completo} no tiene registrada su fecha de nacimiento."
            );
        }

        if (!$destino->fecha_salida) {
            throw new InvalidArgumentException(
                'El paquete seleccionado no tiene fecha de salida.'
            );
        }

        $fechaNacimiento = Carbon::parse(
            $cliente->fecha_nacimiento
        )->startOfDay();

        $fechaViaje = Carbon::parse(
            $destino->fecha_salida
        )->startOfDay();

        if ($fechaNacimiento->greaterThanOrEqualTo($fechaViaje)) {
            throw new InvalidArgumentException(
                "La fecha de nacimiento de {$cliente->nombre_completo} no es válida para este viaje."
            );
        }

        $edad = $fechaNacimiento->diffInYears(
            $fechaViaje
        );

        [$categoria, $porcentaje] =
            $this->determinarCategoria($edad);

        $precioBase = $this->obtenerPrecioBase(
            $destino
        );

        $precioFinal = round(
            $precioBase * ($porcentaje / 100),
            2
        );

        return [
            'edad' => $edad,
            'categoria' => $categoria,
            'porcentaje' => $porcentaje,
            'precio_base' => $precioBase,
            'precio_final' => $precioFinal,
        ];
    }

    public function obtenerPrecioBase(
        Destino $destino
    ): float {
        $precioNormal = (float) $destino->precio;
        $precioPromocional =
            (float) ($destino->precio_promocional ?? 0);

        $precioBase =
            $precioPromocional > 0
                ? $precioPromocional
                : $precioNormal;

        if ($precioBase <= 0) {
            throw new InvalidArgumentException(
                'El paquete seleccionado no tiene un precio válido.'
            );
        }

        return round($precioBase, 2);
    }

    private function determinarCategoria(
        int $edad
    ): array {
        if ($edad < 2) {
            return [
                Reserva::TARIFA_INFANTE,
                0,
            ];
        }

        if ($edad < 12) {
            return [
                Reserva::TARIFA_NINO,
                50,
            ];
        }

        if ($edad > 60) {
            return [
                Reserva::TARIFA_ADULTO_MAYOR,
                50,
            ];
        }

        return [
            Reserva::TARIFA_ADULTO,
            100,
        ];
    }
}