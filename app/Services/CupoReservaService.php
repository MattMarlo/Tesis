<?php

namespace App\Services;

use App\Models\Destino;
use App\Models\Reserva;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CupoReservaService
{
    public function obtenerDisponibles(
        Destino $destino,
        ?int $reservaIgnoradaId = null
    ): int {
        $capacidad = (int) $destino->capacidad;

        if ($capacidad <= 0) {
            return 0;
        }

        $estadosActivos = [
            Reserva::ESTADO_PENDIENTE,
            Reserva::ESTADO_CONFIRMADA,
        ];

        $reservasIndividuales = Reserva::query()
            ->where('destino_id', $destino->id)
            ->where('tipo', Reserva::TIPO_INDIVIDUAL)
            ->whereIn('estado', $estadosActivos)
            ->when(
                $reservaIgnoradaId,
                function ($consulta) use ($reservaIgnoradaId) {
                    $consulta->where(
                        'id',
                        '!=',
                        $reservaIgnoradaId
                    );
                }
            )
            ->count();

        $reservasGrupales = DB::table('reservas as r')
            ->where('r.destino_id', $destino->id)
            ->where('r.tipo', Reserva::TIPO_GRUPAL)
            ->whereIn('r.estado', $estadosActivos)
            ->when(
                $reservaIgnoradaId,
                function ($consulta) use ($reservaIgnoradaId) {
                    $consulta->where(
                        'r.id',
                        '!=',
                        $reservaIgnoradaId
                    );
                }
            )
            ->select([
                'r.id',
                'r.cantidad_viajeros',
            ])
            ->selectSub(
                DB::table('reservas_grupos as rg')
                    ->join(
                        'grupos_clientes as gc',
                        'gc.grupo_id',
                        '=',
                        'rg.grupo_id'
                    )
                    ->whereColumn('rg.reserva_id', 'r.id')
                    ->selectRaw('COUNT(gc.id)'),
                'cantidad_relaciones'
            )
            ->get();

        $integrantesGrupales = $reservasGrupales->sum(
            function ($reserva) {
                $cantidadGuardada =
                    (int) ($reserva->cantidad_viajeros ?? 0);

                return $cantidadGuardada > 0
                    ? $cantidadGuardada
                    : (int) $reserva->cantidad_relaciones;
            }
        );

        return max(
            0,
            $capacidad -
            $reservasIndividuales -
            $integrantesGrupales
        );
    }

    public function validar(
        Destino $destino,
        int $cantidadSolicitada,
        ?int $reservaIgnoradaId = null
    ): void {
        if ((int) $destino->capacidad <= 0) {
            throw new InvalidArgumentException(
                'El paquete no tiene una capacidad configurada.'
            );
        }

        $disponibles = $this->obtenerDisponibles(
            $destino,
            $reservaIgnoradaId
        );

        if ($cantidadSolicitada > $disponibles) {
            throw new InvalidArgumentException(
                "No existen cupos suficientes. Disponibles: {$disponibles}."
            );
        }
    }
}
