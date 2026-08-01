<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Destino;
use App\Models\Reserva;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ReservaIndividualService
{
    public function __construct(
        private TarifaReservaService $tarifaService,
        private CupoReservaService $cupoService
    ) {
    }

    public function guardar(
        int $clienteId,
        int $destinoId,
        int $usuarioId
    ): Reserva {
        return DB::transaction(function () use (
            $clienteId,
            $destinoId,
            $usuarioId
        ) {
            $cliente = Cliente::query()
                ->lockForUpdate()
                ->findOrFail($clienteId);

            if (!$cliente->estaActivo()) {
                throw new InvalidArgumentException(
                    'El cliente seleccionado está inactivo.'
                );
            }

            $destino = Destino::query()
                ->lockForUpdate()
                ->findOrFail($destinoId);

            if ($destino->estado_publicacion !== 'publicado') {
                throw new InvalidArgumentException(
                    'El paquete seleccionado no está disponible para reservas.'
                );
            }

            if (
                !$destino->fecha_salida ||
                Carbon::parse($destino->fecha_salida)->isPast()
            ) {
                throw new InvalidArgumentException(
                    'La fecha de salida del paquete ya pasó o no está registrada.'
                );
            }

            $this->cupoService->validar(
                $destino,
                1
            );

            $tarifa = $this->tarifaService->calcular(
                $cliente,
                $destino
            );

            $duplicada = Reserva::query()
                ->where('cliente_id', $cliente->id)
                ->where('destino_id', $destino->id)
                ->whereIn('estado', [
                    Reserva::ESTADO_PENDIENTE,
                    Reserva::ESTADO_CONFIRMADA,
                ])
                ->exists();

            if ($duplicada) {
                throw new InvalidArgumentException(
                    'El cliente ya tiene una reserva activa para este paquete.'
                );
            }

            return Reserva::create([
                'codigo_reserva' => $this->generarCodigo(),
                'cliente_id' => $cliente->id,
                'destino_id' => $destino->id,
                'user_id' => $usuarioId,
                'tipo' => Reserva::TIPO_INDIVIDUAL,
                'fecha_reserva' => now()->toDateString(),
                'fecha_viaje' => Carbon::parse(
                    $destino->fecha_salida
                )->toDateString(),
                'precio_total_viaje' =>
                    $tarifa['precio_final'],
                'moneda' => strtoupper(
                    $destino->moneda ?: 'USD'
                ),
                'precio_base_persona' =>
                    $tarifa['precio_base'],
                'cantidad_viajeros' => 1,
                'edad_viajero' => $tarifa['edad'],
                'categoria_tarifa' =>
                    $tarifa['categoria'],
                'porcentaje_tarifa' =>
                    $tarifa['porcentaje'],
                'estado' => Reserva::ESTADO_PENDIENTE,
                'estado_pago' => Reserva::PAGO_PENDIENTE,
            ]);
        });
    }

    public function actualizar(
        int $reservaId,
        int $clienteId,
        int $destinoId
    ): Reserva {
        return DB::transaction(function () use (
            $reservaId,
            $clienteId,
            $destinoId
        ) {
            $reserva = Reserva::query()
                ->lockForUpdate()
                ->findOrFail($reservaId);

            if (!$reserva->esIndividual()) {
                throw new InvalidArgumentException(
                    'Esta opción solo permite editar reservas individuales.'
                );
            }

            if ($reserva->estaCancelada()) {
                throw new InvalidArgumentException(
                    'Las reservas canceladas no se pueden editar.'
                );
            }

            if (
                $reserva->estado !==
                Reserva::ESTADO_PENDIENTE
            ) {
                throw new InvalidArgumentException(
                    'Solo se pueden editar reservas que estén pendientes.'
                );
            }

            if ($reserva->pagos()->exists()) {
                throw new InvalidArgumentException(
                    'La reserva tiene pagos registrados y no puede cambiarse.'
                );
            }

            $cliente = Cliente::query()
                ->lockForUpdate()
                ->findOrFail($clienteId);

            if (!$cliente->estaActivo()) {
                throw new InvalidArgumentException(
                    'El cliente seleccionado está inactivo.'
                );
            }

            $destino = Destino::query()
                ->lockForUpdate()
                ->findOrFail($destinoId);

            if (
                $destino->estado_publicacion !==
                'publicado'
            ) {
                throw new InvalidArgumentException(
                    'El paquete seleccionado no está disponible para reservas.'
                );
            }

            if (
                !$destino->fecha_salida ||
                Carbon::parse($destino->fecha_salida)->isPast()
            ) {
                throw new InvalidArgumentException(
                    'La fecha de salida del paquete ya pasó o no está registrada.'
                );
            }

            $this->cupoService->validar(
                $destino,
                1,
                $reserva->id
            );

            $duplicada = Reserva::query()
                ->where('cliente_id', $cliente->id)
                ->where('destino_id', $destino->id)
                ->whereIn('estado', [
                    Reserva::ESTADO_PENDIENTE,
                    Reserva::ESTADO_CONFIRMADA,
                ])
                ->where('id', '!=', $reserva->id)
                ->exists();

            if ($duplicada) {
                throw new InvalidArgumentException(
                    'El cliente ya tiene otra reserva activa para este paquete.'
                );
            }

            $tarifa = $this->tarifaService->calcular(
                $cliente,
                $destino
            );

            $reserva->update([
                'cliente_id' => $cliente->id,
                'destino_id' => $destino->id,
                'fecha_viaje' => Carbon::parse(
                    $destino->fecha_salida
                )->toDateString(),
                'precio_total_viaje' =>
                    $tarifa['precio_final'],
                'moneda' => strtoupper(
                    $destino->moneda ?: 'USD'
                ),
                'precio_base_persona' =>
                    $tarifa['precio_base'],
                'cantidad_viajeros' => 1,
                'edad_viajero' => $tarifa['edad'],
                'categoria_tarifa' =>
                    $tarifa['categoria'],
                'porcentaje_tarifa' =>
                    $tarifa['porcentaje'],
                'estado_pago' =>
                    Reserva::PAGO_PENDIENTE,
            ]);

            return $reserva->fresh([
                'cliente',
                'destino',
            ]);
        });
    }

    private function generarCodigo(): string
    {
        do {
            $codigo = 'RES-' .
                now()->format('Ymd') .
                '-' .
                Str::upper(Str::random(6));
        } while (
            Reserva::where(
                'codigo_reserva',
                $codigo
            )->exists()
        );

        return $codigo;
    }
}