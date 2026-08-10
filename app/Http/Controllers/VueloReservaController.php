<?php

namespace App\Http\Controllers;

use App\Models\OperacionViaje;
use App\Models\TareaOperacionViaje;
use App\Models\VueloReserva;
use App\Services\EstadoTareaContextualService;
use Carbon\Carbon;
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
            $request,
            $operacion
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
            $request,
            $vuelo->operacion
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
        Request $request,
        OperacionViaje $operacion
    ): array {
        $operacion->loadMissing('reserva.destino');

        $fechaInicio = $operacion->reserva?->destino?->fecha_salida
            ?->copy()
            ->startOfDay();
        $fechaFin = $operacion->reserva?->destino?->fecha_regreso
            ?->copy()
            ->endOfDay();

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
                "regex:/^(?=(?:.*\p{L}){2})[\p{L}\p{N}\s.&'’-]+$/u",
            ],

            'numero_vuelo' => [
                'nullable',
                'required_if:estado,confirmado',
                'string',
                'max:30',
                'regex:/^[A-Z0-9]{2,3}[\s-]?[0-9]{1,4}[A-Z]?$/i',
            ],

            'ciudad_origen' => [
                'required',
                'string',
                'min:2',
                'max:120',
                "regex:/^[\p{L}][\p{L}\s.'’-]+$/u",
            ],

            'aeropuerto_origen' => [
                'nullable',
                'string',
                'min:3',
                'max:150',
                "regex:/^(?=(?:.*\p{L}){2})[\p{L}\p{N}\s.'’&(),\/-]+$/u",
            ],

            'ciudad_destino' => [
                'required',
                'string',
                'min:2',
                'max:120',
                'different:ciudad_origen',
                "regex:/^[\p{L}][\p{L}\s.'’-]+$/u",
            ],

            'aeropuerto_destino' => [
                'nullable',
                'string',
                'min:3',
                'max:150',
                "regex:/^(?=(?:.*\p{L}){2})[\p{L}\p{N}\s.'’&(),\/-]+$/u",
            ],

            'fecha_hora_salida' => [
                'required',
                'date',
                ...($fechaInicio
                    ? ['after_or_equal:' . $fechaInicio->toDateTimeString()]
                    : []),
                ...($fechaFin
                    ? ['before_or_equal:' . $fechaFin->toDateTimeString()]
                    : []),
            ],

            'fecha_hora_llegada' => [
                'required',
                'date',
                'after:fecha_hora_salida',
                ...($fechaInicio
                    ? ['after_or_equal:' . $fechaInicio->toDateTimeString()]
                    : []),
                ...($fechaFin
                    ? ['before_or_equal:' . $fechaFin->toDateTimeString()]
                    : []),
            ],

            'terminal_salida' => [
                'nullable',
                'string',
                'min:2',
                'max:50',
                "regex:/^[\p{L}\p{N}][\p{L}\p{N}\s.-]+$/u",
            ],

            'terminal_llegada' => [
                'nullable',
                'string',
                'min:2',
                'max:50',
                "regex:/^[\p{L}\p{N}][\p{L}\p{N}\s.-]+$/u",
            ],

            'localizador_reserva' => [
                'nullable',
                'string',
                'between:5,12',
                'regex:/^[A-Z0-9]+$/i',
            ],

            'equipaje_incluido' => [
                'nullable',
                'string',
                'min:3',
                'max:150',
            ],

            'proveedor' => [
                'nullable',
                'string',
                'min:2',
                'max:150',
                "regex:/^(?=(?:.*\p{L}){2})[\p{L}\p{N}\s.&'’-]+$/u",
            ],

            'fecha_compra' => [
                'nullable',
                'date',
                'after_or_equal:' . Carbon::today()->subYear()->toDateString(),
                'before_or_equal:today',
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

            'aerolinea.regex' =>
                'Ingresa una aerolínea válida con al menos dos letras.',

            'numero_vuelo.regex' =>
                'Ingresa un número de vuelo válido, por ejemplo LA 1447.',

            'ciudad_origen.required' =>
                'Ingresa la ciudad de origen.',

            'ciudad_origen.regex' =>
                'Ingresa una ciudad de origen válida.',

            'ciudad_destino.required' =>
                'Ingresa la ciudad de destino.',

            'ciudad_destino.regex' =>
                'Ingresa una ciudad de destino válida.',

            'ciudad_destino.different' =>
                'La ciudad de destino debe ser diferente a la ciudad de origen.',

            'aeropuerto_origen.regex' =>
                'Ingresa un aeropuerto de origen válido.',

            'aeropuerto_destino.regex' =>
                'Ingresa un aeropuerto de destino válido.',

            'terminal_salida.regex' =>
                'Ingresa una terminal de salida válida.',

            'terminal_llegada.regex' =>
                'Ingresa una terminal de llegada válida.',

            'localizador_reserva.between' =>
                'El localizador debe tener entre 5 y 12 caracteres.',

            'localizador_reserva.regex' =>
                'El localizador solo puede contener letras y números.',

            'equipaje_incluido.min' =>
                'Describe el equipaje con al menos tres caracteres.',

            'proveedor.regex' =>
                'Ingresa un proveedor válido con al menos dos letras.',

            'fecha_hora_salida.required' =>
                'Ingresa la fecha y hora de salida.',

            'fecha_hora_llegada.required' =>
                'Ingresa la fecha y hora de llegada.',

            'fecha_hora_llegada.after' =>
                'La llegada debe ser posterior a la salida.',

            'fecha_hora_salida.after_or_equal' =>
                'La salida no puede ser anterior al inicio del paquete.',

            'fecha_hora_salida.before_or_equal' =>
                'La salida no puede superar la fecha de regreso del paquete.',

            'fecha_hora_llegada.after_or_equal' =>
                'La llegada no puede ser anterior al inicio del paquete.',

            'fecha_hora_llegada.before_or_equal' =>
                'La llegada no puede superar la fecha de regreso del paquete.',

            'fecha_compra.after_or_equal' =>
                'La fecha de compra no puede tener más de un año de antigüedad.',

            'fecha_compra.before_or_equal' =>
                'La fecha de compra no puede ser futura.',

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
