<?php

namespace App\Services;

use App\Models\GestionOperativa;
use App\Models\GestionOperativaViajero;
use App\Models\Reserva;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PasajesTrenService
{
    public function integrantesReserva(
        Reserva $reserva
    ): Collection {
        $reserva->loadMissing([
            'cliente',
            'grupo.clientes',
            'viajerosReserva',
        ]);

        if ($reserva->viajerosReserva->isNotEmpty()) {
            return $reserva->viajerosReserva
                ->map(fn ($viajero) => [
                    'tipo' => 'viajero',
                    'id' => (int) $viajero->id,
                    'nombre' => $viajero->nombre_completo,
                    'documento' =>
                        $viajero->documento_enmascarado,
                ])
                ->values();
        }

        $clientes = $reserva->esIndividual()
            ? collect([$reserva->cliente])->filter()
            : ($reserva->grupo?->clientes ?? collect());

        return $clientes
            ->map(fn ($cliente) => [
                'tipo' => 'cliente',
                'id' => (int) $cliente->id,
                'nombre' => $cliente->nombre_completo,
                'documento' => $this->enmascararDocumento(
                    $cliente->documento
                ),
            ])
            ->values();
    }

    public function sincronizarIntegrantes(
        GestionOperativa $gestion
    ): Collection {
        $gestion->loadMissing([
            'operacion.reserva.cliente',
            'operacion.reserva.grupo.clientes',
            'operacion.reserva.viajerosReserva',
        ]);

        if (
            $gestion->tipo !==
                GestionOperativa::TIPO_TREN
            || !$gestion->operacion?->reserva
        ) {
            throw ValidationException::withMessages([
                'gestion' =>
                    'La gestión seleccionada no corresponde a un tren válido.',
            ]);
        }

        $integrantes = $this->integrantesReserva(
            $gestion->operacion->reserva
        );

        foreach ($integrantes as $integrante) {
            $clave = $integrante['tipo'] === 'viajero'
                ? [
                    'viajero_reserva_id' =>
                        $integrante['id'],
                ]
                : [
                    'cliente_id' =>
                        $integrante['id'],
                ];

            $gestion->detallesViajeros()
                ->firstOrCreate(
                    $clave,
                    [
                        'viajero_reserva_id' =>
                            $integrante['tipo'] === 'viajero'
                                ? $integrante['id']
                                : null,
                        'cliente_id' =>
                            $integrante['tipo'] === 'cliente'
                                ? $integrante['id']
                                : null,
                        'estado' =>
                            GestionOperativaViajero::ESTADO_PENDIENTE,
                    ]
                );
        }

        $clavesValidas = $integrantes
            ->map(
                fn ($integrante) =>
                    $integrante['tipo'].':'
                    .$integrante['id']
            );

        $gestion->detallesViajeros()
            ->get()
            ->reject(function ($detalle) use (
                $clavesValidas
            ) {
                $clave = $detalle->viajero_reserva_id
                    ? 'viajero:'.(int) $detalle
                        ->viajero_reserva_id
                    : 'cliente:'.(int) $detalle
                        ->cliente_id;

                return $clavesValidas->contains($clave);
            })
            ->each->delete();

        return $gestion->detallesViajeros()
            ->with(['viajero', 'cliente'])
            ->get();
    }

    private function enmascararDocumento(
        ?string $documento
    ): string {
        $documento = (string) $documento;

        if ($documento === '') {
            return 'Sin documento';
        }

        if (mb_strlen($documento) <= 4) {
            return $documento;
        }

        return str_repeat(
            '*',
            mb_strlen($documento) - 4
        ).mb_substr($documento, -4);
    }
}
