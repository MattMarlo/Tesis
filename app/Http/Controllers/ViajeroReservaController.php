<?php

namespace App\Http\Controllers;

use App\Models\OperacionViaje;
use App\Models\Reserva;
use App\Models\ViajeroReserva;
use App\Services\ViajeroReservaService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class ViajeroReservaController extends Controller
{
    public function __construct(
        private readonly ViajeroReservaService $service
    ) {
    }

    public function sincronizarTitular(Reserva $reserva)
    {
        try {
            $this->service->sincronizarTitular($reserva);
            return back()->with('success', 'Titular inicializado correctamente.');
        } catch (InvalidArgumentException $error) {
            return back()->with('error', $error->getMessage());
        }
    }

    public function store(Request $request, OperacionViaje $operacion)
    {
        $datos = $this->validar($request);
        try {
            $this->validarEditable($operacion);
            $this->service->guardar($operacion->reserva_id, $datos);
            return back()->with('success', 'Viajero registrado correctamente.');
        } catch (InvalidArgumentException $error) {
            return back()->withInput()->with('error', $error->getMessage());
        }
    }

    public function update(Request $request, ViajeroReserva $viajero)
    {
        $datos = $this->validar($request);
        $viajero->load('reserva.operacionViaje');
        try {
            $this->validarEditable($viajero->reserva->operacionViaje);
            $this->service->actualizar($viajero, $datos);
            return back()->with('success', 'Viajero actualizado correctamente.');
        } catch (InvalidArgumentException $error) {
            return back()->withInput()->with('error', $error->getMessage());
        }
    }

    public function destroy(ViajeroReserva $viajero)
    {
        $viajero->load('reserva.operacionViaje');
        try {
            $this->validarEditable($viajero->reserva->operacionViaje);
            $this->service->eliminar($viajero);
            return back()->with('success', 'Viajero eliminado correctamente.');
        } catch (InvalidArgumentException $error) {
            return back()->with('error', $error->getMessage());
        }
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'nombres' => ['required', 'string', 'min:2', 'max:120'],
            'apellidos' => ['required', 'string', 'min:2', 'max:120'],
            'tipo_documento' => [
                'nullable',
                Rule::requiredIf($request->filled('documento')),
                Rule::in(['cedula', 'pasaporte']),
            ],
            'documento' => [
                'nullable',
                Rule::requiredIf($request->filled('tipo_documento')),
                'string',
                'max:50',
            ],
            'fecha_nacimiento' => ['required', 'date', 'before:today'],
        ], [
            'nombres.required' => 'Ingresa los nombres del viajero.',
            'apellidos.required' => 'Ingresa los apellidos del viajero.',
            'fecha_nacimiento.required' => 'Ingresa la fecha de nacimiento.',
            'tipo_documento.required' => 'Selecciona el tipo de documento.',
            'documento.required' => 'Ingresa el número de documento.',
        ]);
    }

    private function validarEditable(?OperacionViaje $operacion): void
    {
        if (!$operacion) {
            throw new InvalidArgumentException('Primero inicia la preparación del viaje.');
        }
        $operacion->loadMissing('reserva');
        if ($operacion->fueNotificada() || $operacion->reserva->estaCancelada()) {
            throw new InvalidArgumentException('El expediente no puede modificarse.');
        }
    }
}
