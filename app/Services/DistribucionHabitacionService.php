<?php

namespace App\Services;

use App\Models\AlojamientoReserva;
use App\Models\AsignacionHabitacion;
use App\Models\HabitacionAlojamiento;
use App\Models\OperacionViaje;
use App\Models\ViajeroReserva;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DistribucionHabitacionService
{
    public function __construct(
        private readonly EstadoTareaContextualService $estadoTareaContextual
    ) {
    }

    public function guardarHabitacion(
        AlojamientoReserva $alojamiento,
        array $datos,
        ?HabitacionAlojamiento $habitacion = null
    ): HabitacionAlojamiento {
        return DB::transaction(function () use ($alojamiento, $datos, $habitacion) {
            $alojamiento = AlojamientoReserva::query()
                ->with('operacion.reserva')
                ->lockForUpdate()
                ->findOrFail($alojamiento->id);
            $this->validarEditable($alojamiento->operacion);

            $tipo = strtolower((string) $datos['tipo']);
            $capacidad = HabitacionAlojamiento::CAPACIDADES[$tipo] ?? null;
            if (!$capacidad) {
                throw new InvalidArgumentException('El tipo de habitación no es válido.');
            }

            if ($habitacion) {
                $habitacion = HabitacionAlojamiento::query()
                    ->withCount('asignaciones')
                    ->lockForUpdate()
                    ->findOrFail($habitacion->id);
                if ((int) $habitacion->alojamiento_reserva_id !== (int) $alojamiento->id) {
                    throw new InvalidArgumentException('La habitación no pertenece al alojamiento.');
                }
                if ($habitacion->asignaciones_count > $capacidad) {
                    throw new InvalidArgumentException(
                        'La capacidad del nuevo tipo es menor que sus viajeros asignados.'
                    );
                }
            } else {
                $habitacion = new HabitacionAlojamiento();
            }

            $habitacion->fill([
                'alojamiento_reserva_id' => $alojamiento->id,
                'tipo' => $tipo,
                'capacidad' => $capacidad,
                'referencia' => filled($datos['referencia'] ?? null)
                    ? trim((string) $datos['referencia']) : null,
                'observaciones' => filled($datos['observaciones'] ?? null)
                    ? trim((string) $datos['observaciones']) : null,
            ]);
            $habitacion->save();
            $this->marcarPreparacion($alojamiento->operacion);
            $this->sincronizarTareas($alojamiento);

            return $habitacion->fresh('asignaciones');
        });
    }

    public function eliminarHabitacion(HabitacionAlojamiento $habitacion): void
    {
        DB::transaction(function () use ($habitacion) {
            $habitacion = HabitacionAlojamiento::query()
                ->with(['alojamiento.operacion.reserva', 'asignaciones'])
                ->lockForUpdate()
                ->findOrFail($habitacion->id);
            $this->validarEditable($habitacion->alojamiento->operacion);
            if ($habitacion->asignaciones->isNotEmpty()) {
                throw new InvalidArgumentException(
                    'Retira las asignaciones antes de eliminar la habitación.'
                );
            }
            $operacion = $habitacion->alojamiento->operacion;
            $habitacion->delete();
            $this->marcarPreparacion($operacion);
            $this->sincronizarTareas($habitacion->alojamiento);
        });
    }

    public function asignar(HabitacionAlojamiento $habitacion, array $datos): AsignacionHabitacion
    {
        return DB::transaction(function () use ($habitacion, $datos) {
            $habitacion = HabitacionAlojamiento::query()
                ->with([
                    'alojamiento.operacion.reserva.cliente',
                    'alojamiento.operacion.reserva.grupo.clientes',
                ])
                ->withCount('asignaciones')
                ->lockForUpdate()
                ->findOrFail($habitacion->id);
            $alojamiento = $habitacion->alojamiento;
            $operacion = $alojamiento->operacion;
            $reserva = $operacion->reserva;
            $this->validarEditable($operacion);

            $viajeroId = filled($datos['viajero_reserva_id'] ?? null)
                ? (int) $datos['viajero_reserva_id'] : null;
            $clienteId = filled($datos['cliente_id'] ?? null)
                ? (int) $datos['cliente_id'] : null;

            if (($viajeroId === null) === ($clienteId === null)) {
                throw new InvalidArgumentException(
                    'Selecciona exactamente una persona para la habitación.'
                );
            }

            $familiaNueva = $reserva->grupo?->usaCategoriasFamiliares() ?? false;
            if ($familiaNueva && !$viajeroId) {
                throw new InvalidArgumentException('La familia nueva debe utilizar un viajero de reserva.');
            }
            if (!$familiaNueva && !$clienteId) {
                throw new InvalidArgumentException('Esta reserva debe utilizar un cliente relacionado.');
            }

            $viajero = $viajeroId
                ? ViajeroReserva::query()->whereKey($viajeroId)
                    ->where('reserva_id', $reserva->id)->first()
                : null;
            if ($viajeroId && !$viajero) {
                throw new InvalidArgumentException('El viajero no pertenece a esta reserva.');
            }
            if ($viajero?->categoria_tarifa === \App\Models\Reserva::TARIFA_INFANTE) {
                throw new InvalidArgumentException(
                    'Los infantes menores de 2 años no requieren una plaza individual en la habitación.'
                );
            }
            if ($clienteId && !$this->clientePertenece($reserva, $clienteId)) {
                throw new InvalidArgumentException('El cliente no pertenece a esta reserva.');
            }
            if ($clienteId) {
                $cliente = $reserva->esIndividual()
                    ? $reserva->cliente
                    : $reserva->grupo->clientes->firstWhere('id', $clienteId);
                $categoriaCliente = $reserva->esIndividual()
                    ? $reserva->categoria_tarifa
                    : $cliente?->pivot?->categoria_tarifa;
                if ($categoriaCliente === \App\Models\Reserva::TARIFA_INFANTE) {
                    throw new InvalidArgumentException(
                        'Los infantes menores de 2 años no requieren una plaza individual en la habitación.'
                    );
                }
            }
            if ($habitacion->asignaciones_count >= $habitacion->capacidad) {
                throw new InvalidArgumentException('La habitación alcanzó su capacidad máxima.');
            }

            $duplicada = AsignacionHabitacion::query()
                ->where('alojamiento_reserva_id', $alojamiento->id)
                ->when($viajeroId, fn ($q) => $q->where('viajero_reserva_id', $viajeroId))
                ->when($clienteId, fn ($q) => $q->where('cliente_id', $clienteId))
                ->lockForUpdate()
                ->exists();
            if ($duplicada) {
                throw new InvalidArgumentException(
                    'La persona ya ocupa una habitación de este alojamiento.'
                );
            }

            $asignacion = AsignacionHabitacion::create([
                'alojamiento_reserva_id' => $alojamiento->id,
                'habitacion_alojamiento_id' => $habitacion->id,
                'viajero_reserva_id' => $viajeroId,
                'cliente_id' => $clienteId,
            ]);
            $this->marcarPreparacion($operacion);
            $this->sincronizarTareas($alojamiento);

            return $asignacion;
        });
    }

    public function retirar(AsignacionHabitacion $asignacion): void
    {
        DB::transaction(function () use ($asignacion) {
            $asignacion = AsignacionHabitacion::query()
                ->with('alojamiento.operacion.reserva')
                ->lockForUpdate()
                ->findOrFail($asignacion->id);
            $this->validarEditable($asignacion->alojamiento->operacion);
            $operacion = $asignacion->alojamiento->operacion;
            $alojamiento = $asignacion->alojamiento;
            $asignacion->delete();
            $this->marcarPreparacion($operacion);
            $this->sincronizarTareas($alojamiento);
        });
    }

    private function clientePertenece($reserva, int $clienteId): bool
    {
        if ($reserva->esIndividual()) {
            return (int) $reserva->cliente_id === $clienteId;
        }

        return $reserva->grupo?->clientes->contains('id', $clienteId) ?? false;
    }

    private function validarEditable(OperacionViaje $operacion): void
    {
        if ($operacion->fueNotificada()) {
            throw new InvalidArgumentException('El expediente notificado no puede modificarse.');
        }
        if ($operacion->reserva->estaCancelada()) {
            throw new InvalidArgumentException('No se puede modificar una reserva cancelada.');
        }
    }

    private function marcarPreparacion(OperacionViaje $operacion): void
    {
        if ($operacion->estado === OperacionViaje::ESTADO_PENDIENTE) {
            $operacion->estado = OperacionViaje::ESTADO_PREPARACION;
        }
        $operacion->actualizado_por_user_id = Auth::id();
        $operacion->save();
    }

    private function sincronizarTareas(AlojamientoReserva $alojamiento): void
    {
        $alojamiento->tareas()->vigentes()->get()->each(
            fn ($tarea) => $this->estadoTareaContextual->sincronizar(
                $tarea,
                Auth::user()
            )
        );
    }
}
