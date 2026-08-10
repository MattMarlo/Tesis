<?php

namespace App\Http\Controllers;

use App\Models\AlojamientoReserva;
use App\Models\OperacionViaje;
use App\Models\TareaOperacionViaje;
use App\Services\EstadoTareaContextualService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class AlojamientoReservaController extends Controller
{
    public function __construct(
        private readonly EstadoTareaContextualService $estadoTareaContextual
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
        } catch (
            InvalidArgumentException $error
        ) {
            return back()->with(
                'error',
                $error->getMessage()
            );
        }

        $datos = $this->validarDatos(
            $request,
            $operacion
        );

        $tarea = $this->obtenerTareaAlojamiento($request, $operacion);

        DB::transaction(function () use ($operacion, $datos, $tarea, $request): void {
            $alojamiento = $operacion->alojamientos()->create($datos);

            if ($tarea) {
                $this->estadoTareaContextual->vincular(
                    $tarea,
                    $alojamiento,
                    $request->user()
                );
            }

            $this->marcarEnPreparacion($operacion);
        });

        return back()->with(
            'success',
            $tarea
                ? 'Alojamiento registrado y vinculado con la tarea correctamente.'
                : 'Alojamiento registrado correctamente.'
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
            $request,
            $alojamiento->operacion
        );

        $tarea = $this->obtenerTareaAlojamiento(
            $request,
            $alojamiento->operacion
        );

        DB::transaction(function () use ($alojamiento, $datos, $tarea, $request): void {
            $alojamiento->update($datos);

            if ($tarea) {
                $this->estadoTareaContextual->vincular(
                    $tarea,
                    $alojamiento,
                    $request->user()
                );
            }

            $alojamiento->tareas()->vigentes()->get()->each(
                fn (TareaOperacionViaje $tareaVinculada) =>
                    $this->estadoTareaContextual->sincronizar(
                        $tareaVinculada,
                        $request->user()
                    )
            );

            $this->marcarEnPreparacion($alojamiento->operacion);
        });

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

        DB::transaction(function () use ($alojamiento, $operacion): void {
            $alojamiento->tareas()->get()->each(
                fn (TareaOperacionViaje $tarea) =>
                    $this->estadoTareaContextual->desvincular($tarea)
            );
            $alojamiento->delete();
            $this->marcarEnPreparacion($operacion);
        });

        return back()->with(
            'success',
            'Alojamiento eliminado correctamente.'
        );
    }

    private function validarDatos(
        Request $request,
        OperacionViaje $operacion
    ): array {
        $operacion->loadMissing('reserva.destino');
        $inicioPaquete = $operacion->reserva?->destino?->fecha_salida?->copy()->startOfDay();
        $finPaquete = $operacion->reserva?->destino?->fecha_regreso?->copy()->endOfDay();

        return $request->validate([
            'nombre_hotel' => [
                'required',
                'string',
                'min:2',
                'max:180',
                "regex:/^(?=(?:.*\p{L}){2})[\p{L}\p{N}\s.&'’(),\/-]+$/u",
            ],
            'ciudad' => [
                'required',
                'string',
                'min:2',
                'max:120',
                "regex:/^[\p{L}][\p{L}\s.'’,\-]+$/u",
            ],
            'pais' => [
                'required',
                'string',
                'min:2',
                'max:120',
                "regex:/^[\p{L}][\p{L}\s.'’-]+$/u",
            ],
            'codigo_confirmacion' => [
                'nullable',
                'required_if:estado,confirmado',
                'string',
                'min:3',
                'max:100',
                'regex:/^[A-Z0-9]+(?:-[A-Z0-9]+)*$/i',
            ],
            'direccion' => [
                'nullable',
                'string',
                'min:5',
                'max:255',
            ],
            'fecha_hora_entrada' => [
                'required',
                'date',
                ...($inicioPaquete ? ['after_or_equal:' . $inicioPaquete->toDateTimeString()] : []),
                ...($finPaquete ? ['before_or_equal:' . $finPaquete->toDateTimeString()] : []),
            ],
            'fecha_hora_salida' => [
                'required',
                'date',
                'after:fecha_hora_entrada',
                ...($inicioPaquete ? ['after_or_equal:' . $inicioPaquete->toDateTimeString()] : []),
                ...($finPaquete ? ['before_or_equal:' . $finPaquete->toDateTimeString()] : []),
            ],
            'tipo_habitacion' => [
                'required',
                'string',
                'min:2',
                'max:120',
                "regex:~^(?=(?:.*\p{L}){2})[\p{L}\p{N}\s.'’/-]+$~u",
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
                'min:3',
                'max:2000',
            ],
            'alimentacion_incluida' => [
                'nullable',
                'string',
                'min:3',
                'max:120',
            ],
            'telefono_hotel' => [
                'nullable',
                'string',
                'regex:/^\+?[0-9\s()\-]{7,20}$/',
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
                'min:2',
                'max:150',
                "regex:/^(?=(?:.*\p{L}){2})[\p{L}\p{N}\s.&'’(),\/-]+$/u",
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
                Rule::in(['USD', 'EUR', 'PEN']),
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
                'min:3',
                'max:1000',
            ],
        ], [
            'nombre_hotel.required' =>
                'Ingresa el nombre del hotel.',
            'nombre_hotel.regex' =>
                'Ingresa un nombre de hotel válido.',
            'ciudad.required' =>
                'Ingresa la ciudad del alojamiento.',
            'ciudad.regex' =>
                'Ingresa una ciudad válida.',
            'pais.regex' =>
                'Ingresa un país válido.',

            'fecha_hora_entrada.required' =>
                'Ingresa la fecha y hora de entrada.',
            'fecha_hora_salida.required' =>
                'Ingresa la fecha y hora de salida.',
            'fecha_hora_salida.after' =>
                'La salida debe ser posterior a la entrada.',
            'fecha_hora_entrada.after_or_equal' =>
                'La entrada no puede ser anterior al inicio del paquete.',
            'fecha_hora_entrada.before_or_equal' =>
                'La entrada no puede superar el regreso del paquete.',
            'fecha_hora_salida.after_or_equal' =>
                'La salida no puede ser anterior al inicio del paquete.',
            'fecha_hora_salida.before_or_equal' =>
                'La salida no puede superar el regreso del paquete.',

            'cantidad_habitaciones.required' =>
                'Ingresa la cantidad de habitaciones.',
            'cantidad_habitaciones.min' =>
                'Debe existir al menos una habitación.',

            'correo_hotel.email' =>
                'Ingresa un correo válido.',
            'telefono_hotel.regex' =>
                'Ingresa un teléfono válido de 7 a 20 caracteres.',

            'costo_total.numeric' =>
                'El costo debe ser un valor numérico.',
            'costo_total.min' =>
                'El costo no puede ser negativo.',

            'moneda.required' =>
                'Ingresa la moneda.',
            'moneda.in' =>
                'Selecciona una moneda válida.',

            'estado.required' =>
                'Selecciona el estado del alojamiento.',
            'estado.in' =>
                'El estado del alojamiento no es válido.',
            'pais.required' =>
                'Ingresa el país del alojamiento.',
            'codigo_confirmacion.required_if' =>
                'Ingresa el código de confirmación cuando el alojamiento está confirmado.',
            'codigo_confirmacion.regex' =>
                'El código solo puede contener letras, números y guiones.',
            'tipo_habitacion.required' =>
                'Ingresa el tipo de habitación.',
            'tipo_habitacion.regex' =>
                'Ingresa un tipo de habitación válido.',
            'fecha_compra.after_or_equal' =>
                'La fecha de compra no puede tener más de un año de antigüedad.',
            'fecha_compra.before_or_equal' =>
                'La fecha de compra no puede ser futura.',
        ]);
    }

    private function obtenerTareaAlojamiento(
        Request $request,
        OperacionViaje $operacion
    ): ?TareaOperacionViaje {
        $request->validate([
            'tarea_id' => ['nullable', 'integer'],
        ]);

        if (!$request->filled('tarea_id')) {
            return null;
        }

        $tarea = $operacion->tareas()
            ->whereKey((int) $request->input('tarea_id'))
            ->first();

        if (!$tarea) {
            throw ValidationException::withMessages([
                'tarea_id' => 'La tarea seleccionada no pertenece a este expediente.',
            ]);
        }

        if (!$tarea->vigente) {
            throw ValidationException::withMessages([
                'tarea_id' => 'La tarea seleccionada ya no está vigente.',
            ]);
        }

        if ($tarea->tipo_gestion !== TareaOperacionViaje::TIPO_ALOJAMIENTO) {
            throw ValidationException::withMessages([
                'tarea_id' => 'La tarea seleccionada no corresponde a una gestión de alojamiento.',
            ]);
        }

        return $tarea;
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
