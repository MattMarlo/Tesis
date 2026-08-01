<?php

namespace App\Http\Controllers;

use App\Models\BoletoVuelo;
use App\Models\OperacionViaje;
use App\Models\VueloReserva;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class BoletoVueloController extends Controller
{
    public function store(
        Request $request,
        VueloReserva $vuelo
    ) {
        $vuelo->load([
            'operacion.reserva.cliente',
            'operacion.reserva.grupo.clientes',
        ]);

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

        $datos = $request->validate([
            'cliente_id' => [
                'required',
                'integer',
                'exists:clientes,id',
            ],
            'numero_boleto' => [
                'nullable',
                'required_if:estado_emision,emitido',
                'string',
                'max:100',
            ],
            'asiento' => [
                'nullable',
                'string',
                'max:20',
            ],
            'clase' => [
                'nullable',
                'string',
                'max:50',
            ],
            'estado_emision' => [
                'required',
                Rule::in([
                    BoletoVuelo::ESTADO_PENDIENTE,
                    BoletoVuelo::ESTADO_EMITIDO,
                    BoletoVuelo::ESTADO_CANCELADO,
                ]),
            ],
            'archivo_boleto' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120',
            ],
            'observaciones' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ], [
            'cliente_id.required' =>
                'Selecciona el viajero.',
            'cliente_id.exists' =>
                'El viajero seleccionado no existe.',

            'estado_emision.required' =>
                'Selecciona el estado del boleto.',

            'numero_boleto.required_if' =>
                'Ingresa el número del boleto cuando está emitido.',
            'estado_emision.in' =>
                'El estado del boleto no es válido.',

            'archivo_boleto.mimes' =>
                'El boleto debe ser PDF, JPG, JPEG o PNG.',
            'archivo_boleto.max' =>
                'El archivo no puede superar 5 MB.',
        ]);

        $clienteId = (int)
            $datos['cliente_id'];

        if (
            !$this->clientePerteneceReserva(
                $vuelo,
                $clienteId
            )
        ) {
            return back()->with(
                'error',
                'El cliente seleccionado no pertenece a esta reserva.'
            );
        }

        $boleto = BoletoVuelo::firstOrNew([
            'vuelo_reserva_id' =>
                $vuelo->id,
            'cliente_id' =>
                $clienteId,
        ]);

        $archivoAnterior =
            $boleto->archivo_boleto;

        if (
            $request->hasFile(
                'archivo_boleto'
            )
        ) {
            $datos['archivo_boleto'] =
                $request
                    ->file('archivo_boleto')
                    ->store(
                        'boletos/' .
                        $vuelo->operacion
                            ->reserva_id,
                        'public'
                    );
        } else {
            unset(
                $datos['archivo_boleto']
            );
        }

        unset($datos['cliente_id']);

        $boleto->fill(
            $datos
        );

        $boleto->vuelo_reserva_id =
            $vuelo->id;

        $boleto->cliente_id =
            $clienteId;

        $boleto->save();

        if (
            !empty($datos['archivo_boleto']) &&
            $archivoAnterior &&
            $archivoAnterior !==
                $datos['archivo_boleto']
        ) {
            Storage::disk('public')->delete(
                $archivoAnterior
            );
        }

        $this->marcarEnPreparacion(
            $vuelo->operacion
        );

        return back()->with(
            'success',
            $boleto->wasRecentlyCreated
                ? 'Boleto asignado correctamente.'
                : 'Boleto actualizado correctamente.'
        );
    }

    public function destroy(
        BoletoVuelo $boleto
    ) {
        $boleto->load(
            'vuelo.operacion.reserva'
        );

        try {
            $this->validarExpedienteEditable(
                $boleto->vuelo->operacion
            );
        } catch (
            InvalidArgumentException $error
        ) {
            return back()->with(
                'error',
                $error->getMessage()
            );
        }

        $archivo =
            $boleto->archivo_boleto;

        $operacion =
            $boleto->vuelo->operacion;

        $boleto->delete();

        if ($archivo) {
            Storage::disk('public')->delete(
                $archivo
            );
        }

        $this->marcarEnPreparacion(
            $operacion
        );

        return back()->with(
            'success',
            'Boleto eliminado correctamente.'
        );
    }

    private function clientePerteneceReserva(
        VueloReserva $vuelo,
        int $clienteId
    ): bool {
        $reserva =
            $vuelo->operacion->reserva;

        if ($reserva->esIndividual()) {
            return (int)
                $reserva->cliente_id ===
                $clienteId;
        }

        return $reserva->grupo &&
            $reserva->grupo
                ->clientes
                ->contains(
                    'id',
                    $clienteId
                );
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