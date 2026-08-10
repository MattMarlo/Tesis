<?php

namespace App\Http\Controllers;

use App\Models\BoletoVuelo;
use App\Models\OperacionViaje;
use App\Models\Reserva;
use App\Models\TareaOperacionViaje;
use App\Models\VueloReserva;
use App\Models\ViajeroReserva;
use App\Services\EstadoTareaContextualService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class BoletoVueloController extends Controller
{
    public function __construct(
        private readonly EstadoTareaContextualService
            $estadoTareaContextual
    ) {
    }

    public function store(
        Request $request,
        VueloReserva $vuelo
    ) {
        $vuelo->load([
            'operacion.reserva.cliente',
            'operacion.reserva.grupo.clientes',
            'operacion.reserva.viajerosReserva',
        ]);

        try {
            $this->validarExpedienteEditable(
                $vuelo->operacion
            );
        } catch (InvalidArgumentException $error) {
            return back()->with(
                'error',
                $error->getMessage()
            );
        }

        $datos = $this->validarDatos(
            $request
        );

        $clienteId = filled(
            $datos['cliente_id'] ?? null
        )
            ? (int) $datos['cliente_id']
            : null;

        $viajeroId = filled(
            $datos['viajero_reserva_id'] ?? null
        )
            ? (int) $datos['viajero_reserva_id']
            : null;

        if (
            ($clienteId === null) ===
            ($viajeroId === null)
        ) {
            return back()->with(
                'error',
                'Selecciona exactamente una persona viajera.'
            );
        }

        $familiaNueva = $vuelo
            ->operacion
            ->reserva
            ->grupo
            ?->usaCategoriasFamiliares()
            ?? false;

        if (
            ($familiaNueva && !$viajeroId)
            || (!$familiaNueva && !$clienteId)
        ) {
            return back()->with(
                'error',
                'El tipo de persona seleccionado no corresponde a esta reserva.'
            );
        }

        $personaValida = $viajeroId
            ? $vuelo
                ->operacion
                ->reserva
                ->viajerosReserva
                ->contains(
                    'id',
                    $viajeroId
                )
            : $this->clientePerteneceReserva(
                $vuelo,
                $clienteId
            );

        if (!$personaValida) {
            return back()->with(
                'error',
                'La persona seleccionada no pertenece a esta reserva.'
            );
        }

        $viajero = $viajeroId
            ? $vuelo
                ->operacion
                ->reserva
                ->viajerosReserva
                ->firstWhere(
                    'id',
                    $viajeroId
                )
            : null;

        $cliente = $clienteId
            ? (
                $vuelo
                    ->operacion
                    ->reserva
                    ->esIndividual()
                        ? $vuelo
                            ->operacion
                            ->reserva
                            ->cliente
                        : $vuelo
                            ->operacion
                            ->reserva
                            ->grupo
                            ->clientes
                            ->firstWhere(
                                'id',
                                $clienteId
                            )
            )
            : null;

        $categoriaPersona =
            $viajero?->categoria_tarifa;

        if ($cliente) {
            $categoriaPersona = $vuelo
                ->operacion
                ->reserva
                ->esIndividual()
                    ? $vuelo
                        ->operacion
                        ->reserva
                        ->categoria_tarifa
                    : $cliente
                        ->pivot
                        ?->categoria_tarifa;
        }

        if (
            $categoriaPersona ===
            Reserva::TARIFA_INFANTE
        ) {
            throw ValidationException::withMessages([
                'viajero_reserva_id' =>
                    'Los infantes menores de 2 años no requieren boleto de avión.',
            ]);
        }

        if (
            ($datos['estado_emision'] ?? null) ===
                BoletoVuelo::ESTADO_EMITIDO
            && (
                !filled(
                    $viajero?->tipo_documento
                    ?? $cliente?->tipo_documento
                )
                || !filled(
                    $viajero?->documento
                    ?? $cliente?->documento
                )
            )
        ) {
            return back()->with(
                'error',
                'Registra el tipo y número de documento antes de emitir el boleto.'
            );
        }

        $clavePersona = $viajeroId
            ? [
                'viajero_reserva_id' =>
                    $viajeroId,
            ]
            : [
                'cliente_id' =>
                    $clienteId,
            ];

        $boleto = BoletoVuelo::firstOrNew([
            'vuelo_reserva_id' =>
                $vuelo->id,

            ...$clavePersona,
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
                            $vuelo
                                ->operacion
                                ->reserva_id,
                        'public'
                    );
        } else {
            unset(
                $datos['archivo_boleto']
            );
        }

        unset(
            $datos['cliente_id'],
            $datos['viajero_reserva_id']
        );

        $boleto->fill($datos);

        $boleto->vuelo_reserva_id =
            $vuelo->id;

        $boleto->cliente_id =
            $clienteId;

        $boleto->viajero_reserva_id =
            $viajeroId;

        DB::transaction(function () use (
            $boleto,
            $vuelo,
            $request
        ): void {
            $boleto->save();

            /*
             * Después de guardar el boleto se vuelve a calcular
             * el estado de todas las tareas vinculadas al vuelo.
             */
            $this->sincronizarTareasDelVuelo(
                $vuelo,
                $request
            );

            $this->marcarEnPreparacion(
                $vuelo->operacion
            );
        });

        if (
            !empty($datos['archivo_boleto'])
            && $archivoAnterior
            && $archivoAnterior !==
                $datos['archivo_boleto']
        ) {
            Storage::disk('public')->delete(
                $archivoAnterior
            );
        }

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
        } catch (InvalidArgumentException $error) {
            return back()->with(
                'error',
                $error->getMessage()
            );
        }

        $archivo =
            $boleto->archivo_boleto;

        $vuelo =
            $boleto->vuelo;

        $operacion =
            $vuelo->operacion;

        DB::transaction(function () use (
            $boleto,
            $vuelo,
            $operacion
        ): void {
            $boleto->delete();

            /*
             * Al eliminar un boleto la tarea puede dejar de estar
             * completa y regresar al estado en proceso.
             */
            $this->sincronizarTareasDelVuelo(
                $vuelo
            );

            $this->marcarEnPreparacion(
                $operacion
            );
        });

        if ($archivo) {
            Storage::disk('public')->delete(
                $archivo
            );
        }

        return back()->with(
            'success',
            'Boleto eliminado correctamente. El progreso del vuelo fue actualizado.'
        );
    }

    private function validarDatos(
        Request $request
    ): array {
        return $request->validate([
            'cliente_id' => [
                'nullable',
                'integer',
                'exists:clientes,id',
            ],

            'viajero_reserva_id' => [
                'nullable',
                'integer',
                'exists:viajeros_reserva,id',
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
            'cliente_id.exists' =>
                'El viajero seleccionado no existe.',

            'viajero_reserva_id.exists' =>
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
    }

    private function sincronizarTareasDelVuelo(
        VueloReserva $vuelo,
        ?Request $request = null
    ): void {
        $usuario =
            $request?->user()
            ?? Auth::user();

        $vuelo->tareas()
            ->vigentes()
            ->get()
            ->each(function (
                TareaOperacionViaje $tarea
            ) use ($usuario): void {
                $this->estadoTareaContextual
                    ->sincronizar(
                        $tarea,
                        $usuario
                    );
            });
    }

    private function clientePerteneceReserva(
        VueloReserva $vuelo,
        int $clienteId
    ): bool {
        $reserva =
            $vuelo->operacion->reserva;

        if ($reserva->esIndividual()) {
            return (int) $reserva->cliente_id ===
                $clienteId;
        }

        return $reserva->grupo
            && $reserva
                ->grupo
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