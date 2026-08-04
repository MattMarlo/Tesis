<?php

namespace App\Http\Controllers;

use App\Models\OperacionViaje;
use App\Models\Reserva;
use App\Services\ProgresoOperacionService;
use App\Services\ViajeroReservaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OperacionViajeController extends Controller
{
    public function __construct(
        private readonly ProgresoOperacionService $progresoService,
        private readonly ViajeroReservaService $viajeroService
    ) {
    }

    public function index(Request $request)
    {
        $consulta = Reserva::query()
            ->with([
                'cliente',
                'destino',
                'grupo',
                'operacionViaje',
            ])
            ->where(
                'estado',
                '!=',
                Reserva::ESTADO_CANCELADA
            );

        if ($request->filled('buscar')) {
            $buscar = trim(
                $request->buscar
            );

            $consulta->where(
                function ($subconsulta) use (
                    $buscar
                ) {
                    $subconsulta
                        ->where(
                            'codigo_reserva',
                            'like',
                            "%{$buscar}%"
                        )
                        ->orWhereHas(
                            'cliente',
                            function ($cliente) use (
                                $buscar
                            ) {
                                $cliente
                                    ->where(
                                        'nombres',
                                        'like',
                                        "%{$buscar}%"
                                    )
                                    ->orWhere(
                                        'apellidos',
                                        'like',
                                        "%{$buscar}%"
                                    );
                            }
                        )
                        ->orWhereHas(
                            'grupo',
                            function ($grupo) use (
                                $buscar
                            ) {
                                $grupo->where(
                                    'nombre_grupo',
                                    'like',
                                    "%{$buscar}%"
                                );
                            }
                        )
                        ->orWhereHas(
                            'destino',
                            function ($destino) use (
                                $buscar
                            ) {
                                $destino->where(
                                    'nombre_paquete',
                                    'like',
                                    "%{$buscar}%"
                                );
                            }
                        );
                }
            );
        }

        if ($request->filled('estado')) {
            if (
                $request->estado ===
                'sin_iniciar'
            ) {
                $consulta->whereDoesntHave(
                    'operacionViaje'
                );
            } elseif (
                in_array(
                    $request->estado,
                    [
                        OperacionViaje::ESTADO_PENDIENTE,
                        OperacionViaje::ESTADO_PREPARACION,
                        OperacionViaje::ESTADO_COMPLETO,
                        OperacionViaje::ESTADO_NOTIFICADO,
                    ],
                    true
                )
            ) {
                $consulta->whereHas(
                    'operacionViaje',
                    function ($operacion) use (
                        $request
                    ) {
                        $operacion->where(
                            'estado',
                            $request->estado
                        );
                    }
                );
            }
        }

        $reservas = $consulta
            ->orderBy('fecha_viaje')
            ->paginate(12)
            ->withQueryString();

        $reservasActivas = Reserva::query()
            ->where(
                'estado',
                '!=',
                Reserva::ESTADO_CANCELADA
            );

        $resumen = [
            'total' =>
                (clone $reservasActivas)
                    ->count(),

            'sin_iniciar' =>
                (clone $reservasActivas)
                    ->whereDoesntHave(
                        'operacionViaje'
                    )
                    ->count(),

            'preparacion' =>
                (clone $reservasActivas)
                    ->whereHas(
                        'operacionViaje',
                        function ($operacion) {
                            $operacion->whereIn(
                                'estado',
                                [
                                    OperacionViaje::ESTADO_PENDIENTE,
                                    OperacionViaje::ESTADO_PREPARACION,
                                ]
                            );
                        }
                    )
                    ->count(),

            'completas' =>
                (clone $reservasActivas)
                    ->whereHas(
                        'operacionViaje',
                        function ($operacion) {
                            $operacion->whereIn(
                                'estado',
                                [
                                    OperacionViaje::ESTADO_COMPLETO,
                                    OperacionViaje::ESTADO_NOTIFICADO,
                                ]
                            );
                        }
                    )
                    ->count(),
        ];

        return view(
            'modules.operaciones.index',
            [
                'titulo' =>
                    'Preparación de viajes',
                'reservas' =>
                    $reservas,
                'resumen' =>
                    $resumen,
            ]
        );
    }

    public function show(string $id)
    {
        $reserva = Reserva::with([
            'cliente',
            'destino',
            'grupo.clientes',
            'grupo.responsablePago',
            'viajerosReserva',
            'operacionViaje.vuelos.boletos.cliente',
            'operacionViaje.vuelos.boletos.viajeroReserva',
            'operacionViaje.alojamientos.habitaciones.asignaciones.viajeroReserva',
            'operacionViaje.alojamientos.habitaciones.asignaciones.cliente',
            'operacionViaje.alojamientos.asignacionesHabitacion',
            'operacionViaje.guias',
        ])->findOrFail($id);

        if (
            $reserva->estaCancelada() &&
            !$reserva->operacionViaje
        ) {
            return to_route(
                'operaciones.index'
            )->with(
                'error',
                'No se puede iniciar la preparación de una reserva cancelada.'
            );
        }

        if (!$reserva->operacionViaje) {
            return to_route('operaciones.index')->with(
                'error',
                'Primero inicia la preparación mediante la acción correspondiente.'
            );
        }

        $viajeros = $reserva->esGrupal()
            ? $reserva->grupo
                ->clientes
            : collect([
                $reserva->cliente
            ])->filter();

        $composicionFamiliar =
            $reserva->grupo?->usaCategoriasFamiliares()
                ? $reserva->grupo->composicionFamiliar()
                : null;

        $totalViajerosEsperados = $composicionFamiliar
            ? (int) $reserva->cantidad_viajeros
            : $viajeros->count();

        $progreso = $this->progresoService->calcular(
            $reserva->operacionViaje
        );

        return view(
            'modules.operaciones.show',
            [
                'titulo' =>
                    'Expediente del viaje',
                'reserva' =>
                    $reserva,
                'operacion' =>
                    $reserva->operacionViaje,
                'viajeros' =>
                    $viajeros,
                'composicionFamiliar' =>
                    $composicionFamiliar,
                'totalViajerosEsperados' =>
                    $totalViajerosEsperados,
                'progreso' => $progreso,
            ]
        );
    }

    public function iniciar(Reserva $reserva)
    {
        if ($reserva->estaCancelada()) {
            return back()->with('error', 'No se puede iniciar una reserva cancelada.');
        }

        $operacion = DB::transaction(function () use ($reserva) {
            $reserva = Reserva::query()->with('grupo')
                ->lockForUpdate()->findOrFail($reserva->id);
            $operacion = OperacionViaje::firstOrCreate(
                ['reserva_id' => $reserva->id],
                [
                    'estado' => OperacionViaje::ESTADO_PENDIENTE,
                    'creado_por_user_id' => Auth::id(),
                ]
            );

            if ($reserva->grupo?->usaCategoriasFamiliares()) {
                $this->viajeroService->sincronizarTitular($reserva);
            }

            return $operacion;
        });

        return to_route('operaciones.show', $reserva->id)->with(
            'success',
            $operacion->wasRecentlyCreated
                ? 'Preparación iniciada correctamente.'
                : 'La preparación ya estaba iniciada.'
        );
    }

    public function update(
        Request $request,
        OperacionViaje $operacion
    ) {
        if ($operacion->fueNotificada()) {
            return back()->with(
                'error',
                'El expediente ya fue notificado. Primero deberá habilitarse una nueva notificación.'
            );
        }

        if (
            $operacion->reserva
                ->estaCancelada()
        ) {
            return back()->with(
                'error',
                'No se puede modificar el expediente de una reserva cancelada.'
            );
        }

        $datos = $request->validate([
            'estado' => [
                'required',
                Rule::in([
                    OperacionViaje::ESTADO_PENDIENTE,
                    OperacionViaje::ESTADO_PREPARACION,
                    OperacionViaje::ESTADO_COMPLETO,
                ]),
            ],
            'observaciones' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ], [
            'estado.required' =>
                'Selecciona el estado de preparación.',
            'estado.in' =>
                'El estado seleccionado no es válido.',
            'observaciones.max' =>
                'Las observaciones no pueden superar 2000 caracteres.',
        ]);

        if (
            $datos['estado'] ===
            OperacionViaje::ESTADO_COMPLETO
        ) {
            $errorDocumentacion =
                $this->validarDocumentacionCompleta(
                    $operacion
                );

            if ($errorDocumentacion) {
                return back()->with(
                    'error',
                    $errorDocumentacion
                );
            }
        }

        $operacion->update([
            'estado' =>
                $datos['estado'],
            'observaciones' =>
                $datos['observaciones'] ?? null,
            'fecha_documentacion_completa' =>
                $datos['estado'] ===
                OperacionViaje::ESTADO_COMPLETO
                    ? (
                        $operacion
                            ->fecha_documentacion_completa
                        ?: now()
                    )
                    : null,
            'actualizado_por_user_id' =>
                Auth::id(),
        ]);

        return back()->with(
            'success',
            'Información general del viaje actualizada correctamente.'
        );
    }

    private function validarDocumentacionCompleta(
        OperacionViaje $operacion
    ): ?string {
        $progreso = $this->progresoService->calcular($operacion);

        return $progreso['puede_completar']
            ? null
            : ($progreso['motivos_pendientes'][0] ??
                'El expediente todavía tiene tareas pendientes.');
    }
}
