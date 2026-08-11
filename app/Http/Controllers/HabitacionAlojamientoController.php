<?php

namespace App\Http\Controllers;

use App\Models\AlojamientoReserva;
use App\Models\AsignacionHabitacion;
use App\Models\HabitacionAlojamiento;
use App\Services\DistribucionHabitacionService;
use App\Services\NotificacionAlojamientoN8nService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class HabitacionAlojamientoController extends Controller
{
    public function __construct(
        private readonly DistribucionHabitacionService $service,
        private readonly NotificacionAlojamientoN8nService
            $notificacionAlojamientoN8n
    ) {
    }

    public function store(Request $request, AlojamientoReserva $alojamiento)
    {
        return $this->guardar($request, $alojamiento);
    }

    public function update(Request $request, HabitacionAlojamiento $habitacion)
    {
        $habitacion->load('alojamiento');
        return $this->guardar($request, $habitacion->alojamiento, $habitacion);
    }

    public function destroy(HabitacionAlojamiento $habitacion)
    {
        try {
            $this->service->eliminarHabitacion($habitacion);
            return back()->with('success', 'Habitación eliminada correctamente.');
        } catch (InvalidArgumentException $error) {
            return back()->with('error', $error->getMessage());
        }
    }

    public function asignar(Request $request, HabitacionAlojamiento $habitacion)
    {
        $datos = $request->validate([
            'viajero_reserva_id' => ['nullable', 'integer', 'exists:viajeros_reserva,id'],
            'cliente_id' => ['nullable', 'integer', 'exists:clientes,id'],
        ]);
        try {
            if (!filled($habitacion->referencia)) {
                throw new InvalidArgumentException(
                    'Registra el número o nombre de la habitación antes de asignar un viajero.'
                );
            }

            $asignacion = $this->service->asignar(
                $habitacion,
                $datos
            );

            $this->notificacionAlojamientoN8n
                ->enviar($asignacion);

            return back()->with('success', 'Viajero asignado correctamente.');
        } catch (InvalidArgumentException $error) {
            return back()->with('error', $error->getMessage());
        }
    }

    public function retirar(AsignacionHabitacion $asignacion)
    {
        try {
            $this->service->retirar($asignacion);
            return back()->with('success', 'Asignación retirada correctamente.');
        } catch (InvalidArgumentException $error) {
            return back()->with('error', $error->getMessage());
        }
    }

    private function guardar(
        Request $request,
        AlojamientoReserva $alojamiento,
        ?HabitacionAlojamiento $habitacion = null
    ) {
        $datos = $request->validate([
            'tipo' => ['required', Rule::in(array_keys(HabitacionAlojamiento::CAPACIDADES))],
            'referencia' => [
                'required',
                'string',
                'max:100',
                "regex:~^(?:[0-9]{1,4}|(?=.{2,100}$)[\p{L}\p{N}][\p{L}\p{N}\s._-]*)$~u",
                Rule::unique('habitaciones_alojamiento', 'referencia')
                    ->where(fn ($consulta) => $consulta->where(
                        'alojamiento_reserva_id',
                        $alojamiento->id
                    ))
                    ->ignore($habitacion?->id),
            ],
            'observaciones' => [
                'nullable',
                'string',
                'min:3',
                'max:1000',
            ],
        ], [
            'tipo.required' =>
                'Selecciona el tipo de habitación.',
            'tipo.in' =>
                'El tipo de habitación no es válido.',
            'referencia.required' =>
                'Ingresa el número o nombre de la habitación.',
            'referencia.regex' =>
                'Usa un número de habitación o un nombre de al menos dos caracteres.',
            'referencia.unique' =>
                'Ya existe una habitación con ese número o nombre.',
            'observaciones.min' =>
                'Las camas y observaciones deben tener al menos tres caracteres.',
        ]);
        try {
            $this->service->guardarHabitacion($alojamiento, $datos, $habitacion);
            return back()->with('success', 'Habitación guardada correctamente.');
        } catch (InvalidArgumentException $error) {
            return back()->withInput()->with('error', $error->getMessage());
        }
    }
}
