<?php

namespace App\Http\Controllers;

use App\Models\GuiaReserva;
use App\Models\OperacionViaje;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class GuiaReservaController extends Controller
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

        $operacion->guias()->create(
            $datos
        );

        $this->marcarEnPreparacion(
            $operacion
        );

        return back()->with(
            'success',
            'Guía registrado correctamente.'
        );
    }

    public function update(
        Request $request,
        GuiaReserva $guia
    ) {
        $guia->load(
            'operacion.reserva'
        );

        try {
            $this->validarExpedienteEditable(
                $guia->operacion
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

        $guia->update(
            $datos
        );

        $this->marcarEnPreparacion(
            $guia->operacion
        );

        return back()->with(
            'success',
            'Información del guía actualizada correctamente.'
        );
    }

    public function destroy(
        GuiaReserva $guia
    ) {
        $guia->load(
            'operacion.reserva'
        );

        try {
            $this->validarExpedienteEditable(
                $guia->operacion
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
            $guia->operacion;

        $guia->delete();

        $this->marcarEnPreparacion(
            $operacion
        );

        return back()->with(
            'success',
            'Guía eliminado correctamente.'
        );
    }

    private function validarDatos(
        Request $request
    ): array {
        return $request->validate([
            'nombre_completo' => [
                'required',
                'string',
                'min:3',
                'max:180',
                'regex:/^[\pL\s.\'-]+$/u',
            ],
            'empresa' => [
                'nullable',
                'string',
                'max:150',
            ],
            'ciudad_servicio' => [
                'required',
                'string',
                'min:2',
                'max:120',
            ],
            'telefono' => [
                'required',
                'string',
                'min:7',
                'max:30',
                'regex:/^\+?[0-9\s\-()]{7,20}$/',
            ],
            'correo' => [
                'nullable',
                'email',
                'max:150',
            ],
            'idiomas' => [
                'nullable',
                'string',
                'max:150',
            ],
            'fecha_inicio' => [
                'required',
                'date',
            ],
            'fecha_fin' => [
                'required',
                'date',
                'after_or_equal:fecha_inicio',
            ],
            'punto_encuentro' => [
                'nullable',
                'string',
                'max:255',
            ],
            'fecha_hora_encuentro' => [
                'nullable',
                'date',
            ],
            'servicios_incluidos' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'contacto_emergencia' => [
                'nullable',
                'string',
                'max:150',
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
                    GuiaReserva::ESTADO_CONFIRMADO,
                    GuiaReserva::ESTADO_PENDIENTE,
                    GuiaReserva::ESTADO_CANCELADO,
                ]),
            ],
            'observaciones' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ], [
            'nombre_completo.required' =>
                'Ingresa el nombre del guía.',
            'telefono.required' =>
                'Ingresa el teléfono del guía.',
            'telefono.min' =>
                'El teléfono debe tener al menos 7 caracteres.',

            'correo.email' =>
                'Ingresa un correo válido.',

            'fecha_fin.after_or_equal' =>
                'La fecha final no puede ser anterior a la fecha inicial.',

            'costo_total.numeric' =>
                'El costo debe ser un valor numérico.',
            'costo_total.min' =>
                'El costo no puede ser negativo.',

            'moneda.required' =>
                'Ingresa la moneda.',
            'moneda.size' =>
                'La moneda debe tener tres letras.',

            'estado.required' =>
                'Selecciona el estado del guía.',
            'estado.in' =>
                'El estado del guía no es válido.',
            'nombre_completo.regex' =>
                'El nombre del guía solo puede contener letras, espacios, puntos, apóstrofes y guiones.',

            'ciudad_servicio.required' =>
                'Ingresa la ciudad donde trabajará el guía.',

            'telefono.regex' =>
                'Ingresa un número de teléfono válido.',

            'fecha_inicio.required' =>
                'Ingresa la fecha de inicio del servicio.',

            'fecha_fin.required' =>
                'Ingresa la fecha de finalización del servicio.',
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