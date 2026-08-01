<?php

namespace App\Http\Controllers;

use App\Models\OperacionViaje;
use App\Models\VueloReserva;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class VueloReservaController extends Controller
{
    public function store(
        Request $request,
        OperacionViaje $operacion
    ) {
        try {
            $this->validarExpedienteEditable(
                $operacion
            );
        } catch (
            InvalidArgumentException $error
        ) {
            return back()->with(
                'error',
                $error->getMessage()
            );
        }

        $datos = $this->validarDatos(
            $request
        );

        $operacion->vuelos()->create(
            $datos
        );

        $this->marcarEnPreparacion(
            $operacion
        );

        return back()->with(
            'success',
            'Vuelo registrado correctamente.'
        );
    }

    public function update(
        Request $request,
        VueloReserva $vuelo
    ) {
        $vuelo->load('operacion.reserva');

        try {
            $this->validarExpedienteEditable(
                $vuelo->operacion
            );
        } catch (
            InvalidArgumentException $error
        ) {
            return back()->with(
                'error',
                $error->getMessage()
            );
        }

        $datos = $this->validarDatos(
            $request
        );

        $vuelo->update(
            $datos
        );

        $this->marcarEnPreparacion(
            $vuelo->operacion
        );

        return back()->with(
            'success',
            'Vuelo actualizado correctamente.'
        );
    }

    public function destroy(
        VueloReserva $vuelo
    ) {
        $vuelo->load('operacion.reserva');

        try {
            $this->validarExpedienteEditable(
                $vuelo->operacion
            );
        } catch (
            InvalidArgumentException $error
        ) {
            return back()->with(
                'error',
                $error->getMessage()
            );
        }

        $vuelo->delete();

        $this->marcarEnPreparacion(
            $vuelo->operacion
        );

        return back()->with(
            'success',
            'Vuelo eliminado correctamente.'
        );
    }

    private function validarDatos(
        Request $request
    ): array {
        return $request->validate([
            'tipo_tramo' => [
                'required',
                Rule::in([
                    VueloReserva::TRAMO_IDA,
                    VueloReserva::TRAMO_REGRESO,
                    VueloReserva::TRAMO_CONEXION,
                ]),
            ],
            'aerolinea' => [
                'required',
                'string',
                'min:2',
                'max:120',
            ],
            'numero_vuelo' => [
                'nullable',
                'required_if:estado,confirmado',
                'string',
                'max:30',
            ],
            'ciudad_origen' => [
                'required',
                'string',
                'max:120',
            ],
            'aeropuerto_origen' => [
                'nullable',
                'string',
                'max:150',
            ],
            'ciudad_destino' => [
                'required',
                'string',
                'max:120',
                'different:ciudad_origen',
            ],
            'aeropuerto_destino' => [
                'nullable',
                'string',
                'max:150',
            ],
            'fecha_hora_salida' => [
                'required',
                'date',
            ],
            'fecha_hora_llegada' => [
                'required',
                'date',
                'after:fecha_hora_salida',
            ],
            'terminal_salida' => [
                'nullable',
                'string',
                'max:50',
            ],
            'terminal_llegada' => [
                'nullable',
                'string',
                'max:50',
            ],
            'localizador_reserva' => [
                'nullable',
                'string',
                'max:80',
            ],
            'equipaje_incluido' => [
                'nullable',
                'string',
                'max:150',
            ],
            'proveedor' => [
                'nullable',
                'string',
                'max:150',
            ],
            'fecha_compra' => [
                'nullable',
                'date',
            ],
            'costo_total' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'moneda' => [
                'required',
                'string',
                'size:3',
            ],
            'estado' => [
                'required',
                Rule::in([
                    VueloReserva::ESTADO_CONFIRMADO,
                    VueloReserva::ESTADO_PENDIENTE,
                    VueloReserva::ESTADO_CANCELADO,
                ]),
            ],
            'observaciones' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ], [
            'tipo_tramo.required' =>
                'Selecciona el tipo de tramo.',
            'tipo_tramo.in' =>
                'El tipo de tramo no es válido.',

            'aerolinea.required' =>
                'Ingresa el nombre de la aerolínea.',

            'ciudad_origen.required' =>
                'Ingresa la ciudad de origen.',
            'ciudad_destino.required' =>
                'Ingresa la ciudad de destino.',

            'fecha_hora_salida.required' =>
                'Ingresa la fecha y hora de salida.',
            'fecha_hora_llegada.required' =>
                'Ingresa la fecha y hora de llegada.',
            'fecha_hora_llegada.after' =>
                'La llegada debe ser posterior a la salida.',

            'costo_total.numeric' =>
                'El costo debe ser un valor numérico.',
            'costo_total.min' =>
                'El costo no puede ser negativo.',

            'moneda.required' =>
                'Ingresa la moneda.',
            'moneda.size' =>
                'La moneda debe tener tres letras.',

            'estado.required' =>
                'Selecciona el estado del vuelo.',
            'estado.in' =>
                'El estado del vuelo no es válido.',

            'numero_vuelo.required_if' =>
                'Ingresa el número del vuelo cuando está confirmado.',

            'ciudad_destino.different' =>
                'La ciudad de destino debe ser diferente a la ciudad de origen.',
        ]);
    }

    private function validarExpedienteEditable(
        OperacionViaje $operacion
    ): void {
        $operacion->loadMissing('reserva');

        if ($operacion->fueNotificada()) {
            throw new InvalidArgumentException(
                'El expediente ya fue notificado y no puede modificarse.'
            );
        }

        if ($operacion->reserva->estaCancelada()) {
            throw new InvalidArgumentException(
                'No se puede modificar una reserva cancelada.'
            );
        }
    }

    private function marcarEnPreparacion(
        OperacionViaje $operacion
    ): void {
        if (
            $operacion->estado ===
            OperacionViaje::ESTADO_PENDIENTE
        ) {
            $operacion->estado =
                OperacionViaje::ESTADO_PREPARACION;
        }

        $operacion->actualizado_por_user_id =
            Auth::id();

        $operacion->save();
    }
}