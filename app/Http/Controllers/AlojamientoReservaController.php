<?php

namespace App\Http\Controllers;

use App\Models\AlojamientoReserva;
use App\Models\OperacionViaje;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class AlojamientoReservaController extends Controller
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

        $operacion
            ->alojamientos()
            ->create($datos);

        $this->marcarEnPreparacion(
            $operacion
        );

        return back()->with(
            'success',
            'Alojamiento registrado correctamente.'
        );
    }

    public function update(
        Request $request,
        AlojamientoReserva $alojamiento
    ) {
        $alojamiento->load(
            'operacion.reserva'
        );

        try {
            $this->validarExpedienteEditable(
                $alojamiento->operacion
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

        $alojamiento->update(
            $datos
        );

        $this->marcarEnPreparacion(
            $alojamiento->operacion
        );

        return back()->with(
            'success',
            'Alojamiento actualizado correctamente.'
        );
    }

    public function destroy(
        AlojamientoReserva $alojamiento
    ) {
        $alojamiento->load(
            'operacion.reserva'
        );

        try {
            $this->validarExpedienteEditable(
                $alojamiento->operacion
            );
        } catch (
            InvalidArgumentException $error
        ) {
            return back()->with(
                'error',
                $error->getMessage()
            );
        }

        $operacion =
            $alojamiento->operacion;

        $alojamiento->delete();

        $this->marcarEnPreparacion(
            $operacion
        );

        return back()->with(
            'success',
            'Alojamiento eliminado correctamente.'
        );
    }

    private function validarDatos(
        Request $request
    ): array {
        return $request->validate([
            'nombre_hotel' => [
                'required',
                'string',
                'min:2',
                'max:180',
            ],
            'ciudad' => [
                'required',
                'string',
                'max:120',
            ],
            'codigo_confirmacion' => [
                'nullable',
                'required_if:estado,confirmado',
                'string',
                'max:100',
            ],
            'direccion' => [
                'nullable',
                'string',
                'max:255',
            ],
            'fecha_hora_entrada' => [
                'required',
                'date',
            ],
            'fecha_hora_salida' => [
                'required',
                'date',
                'after:fecha_hora_entrada',
            ],
            'codigo_confirmacion' => [
                'nullable',
                'string',
                'max:100',
            ],
            'tipo_habitacion' => [
                'required',
                'string',
                'min:2',
                'max:120',
            ],
            'cantidad_habitaciones' => [
                'required',
                'integer',
                'min:1',
                'max:100',
            ],
            'distribucion_habitaciones' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'alimentacion_incluida' => [
                'nullable',
                'string',
                'max:120',
            ],
            'telefono_hotel' => [
                'nullable',
                'string',
                'max:30',
            ],
            'correo_hotel' => [
                'nullable',
                'email',
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
                    AlojamientoReserva::ESTADO_CONFIRMADO,
                    AlojamientoReserva::ESTADO_PENDIENTE,
                    AlojamientoReserva::ESTADO_CANCELADO,
                ]),
            ],
            'observaciones' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ], [
            'nombre_hotel.required' =>
                'Ingresa el nombre del hotel.',
            'ciudad.required' =>
                'Ingresa la ciudad del alojamiento.',

            'fecha_hora_entrada.required' =>
                'Ingresa la fecha y hora de entrada.',
            'fecha_hora_salida.required' =>
                'Ingresa la fecha y hora de salida.',
            'fecha_hora_salida.after' =>
                'La salida debe ser posterior a la entrada.',

            'cantidad_habitaciones.required' =>
                'Ingresa la cantidad de habitaciones.',
            'cantidad_habitaciones.min' =>
                'Debe existir al menos una habitación.',

            'correo_hotel.email' =>
                'Ingresa un correo válido.',

            'costo_total.numeric' =>
                'El costo debe ser un valor numérico.',
            'costo_total.min' =>
                'El costo no puede ser negativo.',

            'moneda.required' =>
                'Ingresa la moneda.',
            'moneda.size' =>
                'La moneda debe tener tres letras.',

            'estado.required' =>
                'Selecciona el estado del alojamiento.',
            'estado.in' =>
                'El estado del alojamiento no es válido.',
            'pais.required' =>
                'Ingresa el país del alojamiento.',
            'codigo_confirmacion.required_if' =>
                'Ingresa el código de confirmación cuando el alojamiento está confirmado.',
            'tipo_habitacion.required' =>
                'Ingresa el tipo de habitación.',
        ]);
    }

    private function validarExpedienteEditable(
        OperacionViaje $operacion
    ): void {
        $operacion->loadMissing(
            'reserva'
        );

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