<?php

namespace App\Http\Controllers;

use App\Models\BoletoVuelo;
use App\Models\OperacionViaje;
use App\Models\TareaOperacionViaje;
use App\Models\VueloReserva;
use App\Services\ProgresoOperacionService;
use Illuminate\Http\Request;

class GestionBoletosVueloController extends Controller
{
    public function __construct(
        private readonly ProgresoOperacionService $progresoService
    ) {
    }

    public function index(
        Request $request,
        OperacionViaje $operacion,
        VueloReserva $vuelo
    ) {
        /*
         * Evita acceder a un vuelo perteneciente
         * a otra operación modificando la URL.
         */
        abort_unless(
            (int) $vuelo->operacion_viaje_id ===
                (int) $operacion->id,
            404
        );

        $operacion->load([
            'reserva.cliente',
            'reserva.destino',
            'reserva.grupo.clientes',
            'reserva.grupo.responsablePago',
            'reserva.viajerosReserva',
            'vuelos.boletos.cliente',
            'vuelos.boletos.viajeroReserva',
            'alojamientos.habitaciones',
            'alojamientos.asignacionesHabitacion',
            'guias',
            'tareasVigentes',
        ]);

        $vuelo->load([
            'boletos.cliente',
            'boletos.viajeroReserva',
        ]);

        $reserva = $operacion->reserva;

        abort_if(
            !$reserva,
            404
        );

        $progreso = $this->progresoService->calcular(
            $operacion
        );

        $personas = collect(
            $progreso['personas'] ?? []
        )->values();

        $personasQueRequierenBoleto = $personas
            ->filter(
                fn (array $persona): bool =>
                    (bool) (
                        $persona['requiere_boleto']
                        ?? false
                    )
            )
            ->values();

        $progresoVuelo =
            $progreso['boletos_por_vuelo'][$vuelo->id]
            ?? [
                'actual' => 0,
                'total' =>
                    $personasQueRequierenBoleto->count(),
            ];

        $asientosAsignados = $personasQueRequierenBoleto
            ->filter(
                function (array $persona) use (
                    $vuelo
                ): bool {
                    return $vuelo->boletos->contains(
                        function (
                            BoletoVuelo $boleto
                        ) use ($persona): bool {
                            $pertenece = (
                                $persona['tipo'] ===
                                'viajero'
                            )
                                ? (
                                    (int)
                                    $boleto
                                        ->viajero_reserva_id ===
                                    (int)
                                    $persona['id']
                                )
                                : (
                                    (int)
                                    $boleto->cliente_id ===
                                    (int)
                                    $persona['id']
                                );

                            return $pertenece &&
                                $boleto
                                    ->estado_emision ===
                                    BoletoVuelo::ESTADO_EMITIDO &&
                                filled($boleto->asiento);
                        }
                    );
                }
            )
            ->count();

        /*
         * Si se accede desde una tarea concreta,
         * se conserva para regresar exactamente a ella.
         */
        $consultaTarea = TareaOperacionViaje::query()
            ->where(
                'operacion_viaje_id',
                $operacion->id
            )
            ->where('vigente', true)
            ->where(
                'gestionable_type',
                $vuelo->getMorphClass()
            )
            ->where(
                'gestionable_id',
                $vuelo->id
            );

        if ($request->filled('tarea_id')) {
            $consultaTarea->whereKey(
                (int) $request->input(
                    'tarea_id'
                )
            );
        }

        $tarea = $consultaTarea->first();

        $editable =
            !$operacion->fueNotificada() &&
            !$reserva->estaCancelada();

        return view(
            'modules.operaciones.boletos.index',
            [
                'titulo' =>
                    'Gestión de boletos',

                'operacion' =>
                    $operacion,

                'reserva' =>
                    $reserva,

                'vuelo' =>
                    $vuelo,

                'personas' =>
                    $personas,

                'progresoVuelo' =>
                    $progresoVuelo,

                'asientosAsignados' =>
                    $asientosAsignados,

                'tarea' =>
                    $tarea,

                'editable' =>
                    $editable,
            ]
        );
    }
}