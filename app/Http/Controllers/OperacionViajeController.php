<?php

namespace App\Http\Controllers;

use App\Models\OperacionViaje;
use App\Models\Reserva;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class OperacionViajeController extends Controller
{
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
            'operacionViaje.vuelos.boletos.cliente',
            'operacionViaje.alojamientos',
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
            OperacionViaje::create([
                'reserva_id' =>
                    $reserva->id,
                'estado' =>
                    OperacionViaje::ESTADO_PENDIENTE,
                'creado_por_user_id' =>
                    Auth::id(),
            ]);

            $reserva->load([
                'operacionViaje.vuelos.boletos.cliente',
                'operacionViaje.alojamientos',
                'operacionViaje.guias',
            ]);
        }

        $viajeros = $reserva->esGrupal()
            ? $reserva->grupo
                ->clientes
            : collect([
                $reserva->cliente
            ])->filter();

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
            ]
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
        $operacion->load([
            'reserva.destino',
            'reserva.cliente',
            'reserva.grupo.clientes',
            'vuelos.boletos',
            'alojamientos',
            'guias',
        ]);

        $reserva = $operacion->reserva;

        $viajeros = $reserva->esGrupal()
            ? (
                $reserva->grupo?->clientes
                ?? collect()
            )
            : collect([
                $reserva->cliente,
            ])->filter();

        $viajerosIds = $viajeros
            ->pluck('id')
            ->filter()
            ->unique();

        $servicios = collect(
            $reserva->destino?->incluye ?? []
        )
            ->map(
                fn ($servicio) =>
                    mb_strtolower(
                        (string) $servicio
                    )
            )
            ->implode(' ');

        $requiereVuelo =
            str_contains($servicios, 'vuelo') ||
            str_contains($servicios, 'aéreo') ||
            str_contains($servicios, 'aereo') ||
            str_contains($servicios, 'boleto');

        $requiereAlojamiento =
            str_contains($servicios, 'hotel') ||
            str_contains($servicios, 'alojamiento') ||
            str_contains($servicios, 'hospedaje');

        $requiereGuia =
            str_contains($servicios, 'guía') ||
            str_contains($servicios, 'guia');

        if ($requiereVuelo) {
            $vuelosActivos = $operacion
                ->vuelos
                ->where('estado', '!=', 'cancelado');

            if ($vuelosActivos->isEmpty()) {
                return 'El paquete incluye transporte aéreo. Registra al menos un vuelo.';
            }

            foreach ($vuelosActivos as $vuelo) {
                if ($vuelo->estado !== 'confirmado') {
                    return 'Todos los vuelos deben estar confirmados antes de completar el expediente.';
                }

                $viajerosConBoleto = $vuelo
                    ->boletos
                    ->where(
                        'estado_emision',
                        'emitido'
                    )
                    ->pluck('cliente_id')
                    ->unique();

                if (
                    $viajerosIds
                        ->diff($viajerosConBoleto)
                        ->isNotEmpty()
                ) {
                    return 'Faltan boletos emitidos para uno o más viajeros.';
                }
            }
        }

        if (
            $requiereAlojamiento &&
            !$operacion->alojamientos
                ->contains('estado', 'confirmado')
        ) {
            return 'El paquete incluye alojamiento. Registra y confirma el hotel.';
        }

        if (
            $requiereGuia &&
            !$operacion->guias
                ->contains('estado', 'confirmado')
        ) {
            return 'El paquete incluye guía. Registra y confirma sus datos.';
        }

        return null;
    }
}