<?php

namespace App\Http\Controllers;

use App\Models\GestionOperativa;
use App\Models\GestionOperativaViajero;
use App\Models\OperacionViaje;
use App\Models\TareaOperacionViaje;
use App\Services\EstadoTareaContextualService;
use App\Services\PasajesTrenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GestionPasajesTrenController extends Controller
{
    public function __construct(
        private readonly PasajesTrenService
            $pasajesTrenService,
        private readonly EstadoTareaContextualService
            $estadoTareaService
    ) {
    }

    public function index(
        Request $request,
        OperacionViaje $operacion,
        GestionOperativa $gestion
    ) {
        $this->validarGestion(
            $operacion,
            $gestion
        );

        $detalles = $this->pasajesTrenService
            ->sincronizarIntegrantes($gestion)
            ->keyBy(function ($detalle) {
                return $detalle->viajero_reserva_id
                    ? 'viajero:'.(int) $detalle
                        ->viajero_reserva_id
                    : 'cliente:'.(int) $detalle
                        ->cliente_id;
            });

        $gestion->tareas()
            ->vigentes()
            ->get()
            ->each(function ($tarea) use (
                $request
            ): void {
                $this->estadoTareaService
                    ->sincronizar(
                        $tarea,
                        $request->user()
                    );
            });

        $reserva = $operacion->reserva;

        $integrantes = $this->pasajesTrenService
            ->integrantesReserva($reserva)
            ->map(function ($integrante) use (
                $detalles
            ) {
                $clave = $integrante['tipo'].':'
                    .$integrante['id'];

                return [
                    ...$integrante,
                    'pasaje' => $detalles->get($clave),
                ];
            });

        $consultaTarea = TareaOperacionViaje::query()
            ->where(
                'operacion_viaje_id',
                $operacion->id
            )
            ->where('vigente', true)
            ->where(
                'gestionable_type',
                $gestion->getMorphClass()
            )
            ->where(
                'gestionable_id',
                $gestion->id
            );

        if ($request->filled('tarea_id')) {
            $consultaTarea->whereKey(
                (int) $request->input('tarea_id')
            );
        }

        $confirmados = $detalles
            ->where(
                'estado',
                GestionOperativaViajero::ESTADO_CONFIRMADO
            )
            ->count();

        return view(
            'modules.operaciones.trenes.pasajes',
            [
                'titulo' => 'Gestión de pasajes de tren',
                'operacion' => $operacion,
                'reserva' => $reserva,
                'gestion' => $gestion,
                'integrantes' => $integrantes,
                'confirmados' => $confirmados,
                'tarea' => $consultaTarea->first(),
                'editable' =>
                    !$operacion->fueNotificada()
                    && !$reserva->estaCancelada(),
            ]
        );
    }

    public function update(
        Request $request,
        GestionOperativaViajero $pasaje
    ) {
        $pasaje->loadMissing([
            'gestion.operacion.reserva',
            'gestion.tareas',
        ]);

        $gestion = $pasaje->gestion;

        if (
            !$gestion
            || $gestion->tipo !==
                GestionOperativa::TIPO_TREN
            || !$gestion->operacion?->reserva
        ) {
            abort(404);
        }

        $this->validarEditable(
            $gestion->operacion
        );

        $datos = $request->validate([
            'pasaje_id' => [
                'required',
                'integer',
                Rule::in([$pasaje->id]),
            ],
            'numero_documento' => [
                'nullable',
                'required_if:estado,confirmado',
                'string',
                'min:3',
                'max:150',
                'regex:/^[A-Z0-9-]+$/i',
            ],
            'asiento' => [
                'nullable',
                'string',
                'max:30',
                'regex:/^[0-9]{1,3}[A-Z]$/i',
            ],
            'referencia_individual' => [
                'nullable',
                'string',
                'min:3',
                'max:150',
                'regex:/^[A-Z0-9-]+$/i',
            ],
            'estado' => [
                'required',
                Rule::in(
                    GestionOperativaViajero::
                        ESTADOS_PERMITIDOS
                ),
            ],
            'restricciones' => [
                'nullable',
                'string',
                'min:3',
                'max:2000',
            ],
            'observaciones' => [
                'nullable',
                'string',
                'min:3',
                'max:2000',
            ],
        ], [
            'numero_documento.required_if' =>
                'Ingresa el número del pasaje cuando esté confirmado.',
            'numero_documento.min' =>
                'El número del pasaje debe tener al menos tres caracteres.',
            'numero_documento.regex' =>
                'El número del pasaje solo puede contener letras, números y guiones.',
            'asiento.regex' =>
                'Ingresa un asiento válido, por ejemplo 12A.',
            'referencia_individual.min' =>
                'La referencia debe tener al menos tres caracteres.',
            'referencia_individual.regex' =>
                'La referencia solo puede contener letras, números y guiones.',
            'estado.required' =>
                'Selecciona el estado del pasaje.',
            'estado.in' =>
                'El estado seleccionado no es válido.',
            'restricciones.min' =>
                'Las restricciones deben tener al menos tres caracteres.',
            'observaciones.min' =>
                'Las observaciones deben tener al menos tres caracteres.',
        ]);

        unset($datos['pasaje_id']);

        DB::transaction(function () use (
            $pasaje,
            $gestion,
            $datos,
            $request
        ): void {
            $pasaje->update($datos);

            $gestion->tareas()
                ->vigentes()
                ->get()
                ->each(function ($tarea) use (
                    $request
                ): void {
                    $this->estadoTareaService
                        ->sincronizar(
                            $tarea,
                            $request->user()
                        );
                });

            $operacion = $gestion->operacion;

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
        });

        return back()->with(
            'success',
            'El pasaje de tren fue actualizado correctamente.'
        );
    }

    private function validarGestion(
        OperacionViaje $operacion,
        GestionOperativa $gestion
    ): void {
        abort_unless(
            (int) $gestion->operacion_viaje_id ===
                (int) $operacion->id
            && $gestion->tipo ===
                GestionOperativa::TIPO_TREN,
            404
        );

        $operacion->loadMissing([
            'reserva.cliente',
            'reserva.destino',
            'reserva.grupo.clientes',
            'reserva.viajerosReserva',
        ]);

        abort_if(!$operacion->reserva, 404);
    }

    private function validarEditable(
        OperacionViaje $operacion
    ): void {
        if ($operacion->fueNotificada()) {
            throw ValidationException::withMessages([
                'pasaje' =>
                    'El expediente ya fue notificado y no puede modificarse.',
            ]);
        }

        if ($operacion->reserva->estaCancelada()) {
            throw ValidationException::withMessages([
                'pasaje' =>
                    'No se puede modificar una reserva cancelada.',
            ]);
        }
    }
}
