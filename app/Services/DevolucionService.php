<?php

namespace App\Services;

use App\Models\Devolucion;
use App\Models\Pago;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DevolucionService
{
    public function __construct(
        private PagoService $pagoService
    ) {}

    public function registrar(array $datos): Devolucion
    {
        return DB::transaction(function () use ($datos) {
            $pago = Pago::query()->lockForUpdate()->findOrFail($datos['pago_id']);

            if ($pago->estaAnulado()) {
                throw new InvalidArgumentException('No se puede devolver un pago anulado.');
            }

            $monto = round((float) $datos['monto'], 2);
            $devuelto = (float) Devolucion::query()
                ->procesadas()->where('pago_id', $pago->id)->sum('monto');
            $disponible = round((float) $pago->monto_depositado - $devuelto, 2);

            if ($monto <= 0 || $monto > $disponible) {
                throw new InvalidArgumentException(
                    'La devolución no puede superar el saldo reembolsable de '.
                    number_format($disponible, 2, '.', '').' USD.'
                );
            }

            $devolucion = Devolucion::create([
                'pago_id' => $pago->id,
                'reserva_id' => $pago->reserva_id,
                'cliente_id' => $pago->cliente_id,
                'user_id' => $datos['user_id'],
                'monto' => $monto,
                'metodo' => $datos['metodo'],
                'referencia' => $datos['referencia'] ?? null,
                'tipo' => $datos['tipo'],
                'motivo' => trim($datos['motivo']),
                'estado' => Devolucion::ESTADO_PROCESADA,
                'fecha_devolucion' => now(),
            ]);

            $this->pagoService->sincronizarEstadoPagoReserva(
                (int) $pago->reserva_id
            );

            return $devolucion;
        });
    }

    public function anular(Devolucion $devolucion, string $motivo, int $usuarioId): void
    {
        DB::transaction(function () use ($devolucion, $motivo, $usuarioId) {
            $registro = Devolucion::query()->lockForUpdate()->findOrFail($devolucion->id);

            if ($registro->estaAnulada()) {
                throw new InvalidArgumentException('La devolución ya está anulada.');
            }

            $registro->update([
                'estado' => Devolucion::ESTADO_ANULADA,
                'motivo_anulacion' => trim($motivo),
                'fecha_anulacion' => now(),
                'anulada_por_user_id' => $usuarioId,
            ]);

            $this->pagoService->sincronizarEstadoPagoReserva(
                (int) $registro->reserva_id
            );
        });
    }
}
