<?php

namespace App\Http\Controllers;

use App\Models\AlojamientoReserva;
use App\Models\OperacionViaje;
use App\Models\TareaOperacionViaje;
use App\Services\ProgresoOperacionService;
use Illuminate\Http\Request;

class GestionHabitacionesAlojamientoController extends Controller
{
    public function __construct(
        private readonly ProgresoOperacionService $progresoService
    ) {
    }

    public function index(
        Request $request,
        OperacionViaje $operacion,
        AlojamientoReserva $alojamiento
    ) {
        abort_unless(
            (int) $alojamiento->operacion_viaje_id === (int) $operacion->id,
            404
        );

        $operacion->load([
            'reserva.cliente',
            'reserva.destino',
            'reserva.grupo.clientes',
            'reserva.viajerosReserva',
            'alojamientos.habitaciones.asignaciones.viajeroReserva',
            'alojamientos.habitaciones.asignaciones.cliente',
            'tareasVigentes',
        ]);
        $alojamiento->load([
            'habitaciones.asignaciones.viajeroReserva',
            'habitaciones.asignaciones.cliente',
        ]);

        $reserva = $operacion->reserva;
        abort_if(!$reserva, 404);

        $progreso = $this->progresoService->calcular($operacion);
        $personas = collect($progreso['personas'] ?? [])
            ->filter(fn (array $persona): bool => (bool) ($persona['requiere_habitacion'] ?? true))
            ->values();

        $tareaQuery = TareaOperacionViaje::query()
            ->where('operacion_viaje_id', $operacion->id)
            ->where('vigente', true)
            ->where('gestionable_type', $alojamiento->getMorphClass())
            ->where('gestionable_id', $alojamiento->id);

        if ($request->filled('tarea_id')) {
            $tareaQuery->whereKey((int) $request->input('tarea_id'));
        }

        $asignados = $alojamiento->asignacionesHabitacion()
            ->get()
            ->map(fn ($asignacion) => $asignacion->viajero_reserva_id
                ? 'viajero-'.$asignacion->viajero_reserva_id
                : 'cliente-'.$asignacion->cliente_id)
            ->all();

        return view('modules.operaciones.alojamientos.habitaciones', [
            'titulo' => 'Gestión de habitaciones',
            'operacion' => $operacion,
            'reserva' => $reserva,
            'alojamiento' => $alojamiento,
            'personas' => $personas,
            'asignados' => $asignados,
            'tarea' => $tareaQuery->first(),
            'editable' => !$operacion->fueNotificada() && !$reserva->estaCancelada(),
        ]);
    }
}
