<?php

namespace App\Http\Controllers;

use App\Models\OperacionViaje;
use App\Models\TareaOperacionViaje;
use App\Models\VueloReserva;
use App\Services\EstadoTareaContextualService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class VueloReservaController extends Controller
{
    public function __construct(
        private readonly EstadoTareaContextualService
            $estadoTareaContextual
    ) {
    }

    public function store(
        Request $request,
        OperacionViaje $operacion
    ) {
        try {
            $this->validarExpedienteEditable(
                $operacion
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

        $tarea = $this->obtenerTareaVuelo(
            $request,
            $operacion
        );

        DB::transaction(function () use (
            $operacion,
            $datos,
            $tarea,
            $request
        ): void {
            $vuelo = $operacion
                ->vuelos()
                ->create($datos);

            if ($tarea) {
                $this->estadoTareaContextual
                    ->vincular(
                        $tarea,
                        $vuelo,
                        $request->user()
                    );
            }

            $this->marcarEnPreparacion(
                $operacion
            );
        });

        return back()->with(
            'success',
            $tarea
                ? 'Vuelo registrado y vinculado con la tarea correctamente.'
                : 'Vuelo registrado correctamente.'
        );
    }

    public function update(
        Request $request,
        VueloReserva $vuelo
    ) {
        $vuelo->load([
            'operacion.reserva',
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

        $tarea = $this->obtenerTareaVuelo(
            $request,
            $vuelo->operacion
        );

        DB::transaction(function () use (
            $vuelo,
            $datos,
            $tarea,
            $request
        ): void {
            $vuelo->update($datos);

            if ($tarea) {
                $this->estadoTareaContextual
                    ->vincular(
                        $tarea,
                        $vuelo,
                        $request->user()
                    );
            }

            $this->sincronizarTareasDelVuelo(
                $vuelo,
                $request
            );

            $this->marcarEnPreparacion(
                $vuelo->operacion
            );
        });

        return back()->with(
            'success',
            $tarea
                ? 'Vuelo actualizado y vinculado con la tarea correctamente.'
                : 'Vuelo actualizado correctamente.'
        );
    }

    public function destroy(
        VueloReserva $vuelo
    ) {
        $vuelo->load([
            'operacion.reserva',
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

        $operacion = $vuelo->operacion;

        DB::transaction(function () use (
            $vuelo,
            $operacion
        ): void {
            /*
             * Antes de eliminar el vuelo se desvinculan todas
             * las tareas que lo utilizan. Estas regresarán a
             * estado pendiente.
             */
            $vuelo->tareas()
                ->get()
                ->each(function (
                    TareaOperacionViaje $tarea
                ): void {
                    $this->estadoTareaContextual
                        ->desvincular($tarea);
                });

            $vuelo->delete();

            $this->marcarEnPreparacion(
                $operacion
            );
        });

        return back()->with(
            'success',
            'Vuelo eliminado correctamente. Las tareas vinculadas quedaron pendientes.'
        );
    }

    private function obtenerTareaVuelo(
        Request $request,
        OperacionViaje $operacion
    ): ?TareaOperacionViaje {
        $request->validate([
            'tarea_id' => [
                'nullable',
                'integer',
            ],
        ], [
            'tarea_id.integer' =>
                'La tarea seleccionada no es válida.',
        ]);

        if (!$request->filled('tarea_id')) {
            return null;
        }

        $tarea = $operacion
            ->tareas()
            ->whereKey(
                (int) $request->input('tarea_id')
            )
            ->first();

        if (!$tarea) {
            throw ValidationException::withMessages([
                'tarea_id' =>
                    'La tarea seleccionada no pertenece a este expediente.',
            ]);
        }

        if (!$tarea->vigente) {
            throw ValidationException::withMessages([
                'tarea_id' =>
                    'La tarea seleccionada ya no está vigente.',
            ]);
        }

        if (
            $tarea->tipo_gestion !==
            TareaOperacionViaje::TIPO_VUELO
        ) {
            throw ValidationException::withMessages([
                'tarea_id' =>
                    'La tarea seleccionada no corresponde a una gestión de vuelo.',
            ]);
        }

        return $tarea;
    }

    private function sincronizarTareasDelVuelo(
        VueloReserva $vuelo,
        Request $request
    ): void {
        $vuelo->tareas()
            ->vigentes()
            ->get()
            ->each(function (
                TareaOperacionViaje $tarea
            ) use ($request): void {
                $this->estadoTareaContextual
                    ->sincronizar(
                        $tarea,
                        $request->user()
                    );
            });
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

            'ciudad_destino.different' =>
                'La ciudad de destino debe ser diferente a la ciudad de origen.',

            'fecha_hora_salida.required' =>
                'Ingresa la fecha y hora de salida.',

            'fecha_hora_llegada.required' =>
                'Ingresa la fecha y hora de llegada.',

            'fecha_hora_llegada.after' =>
                'La llegada debe ser posterior a la salida.',

            'numero_vuelo.required_if' =>
                'Ingresa el número del vuelo cuando está confirmado.',

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