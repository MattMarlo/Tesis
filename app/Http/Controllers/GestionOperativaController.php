<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarGestionOperativaRequest;
use App\Models\GestionOperativa;
use App\Models\GestionOperativaViajero;
use App\Models\OperacionViaje;
use App\Models\TareaOperacionViaje;
use App\Services\EstadoTareaContextualService;
use App\Services\PasajesTrenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class GestionOperativaController extends Controller
{
    public function __construct(
        private readonly EstadoTareaContextualService
            $estadoTareaService,
        private readonly PasajesTrenService
            $pasajesTrenService
    ) {
    }

    public function store(
        GuardarGestionOperativaRequest $request,
        OperacionViaje $operacion,
        TareaOperacionViaje $tarea
    ): RedirectResponse {
        $this->validarTareaDeOperacion(
            $operacion,
            $tarea
        );

        $this->validarExpedienteEditable(
            $operacion
        );

        $this->validarTipoGenerico(
            $tarea->tipo_gestion
        );

        $datosValidados =
            $request->validated();

        $detallesViajeros =
            $datosValidados['viajeros']
            ?? [];

        $incluyeViajeros =
            array_key_exists(
                'viajeros',
                $datosValidados
            );

        $archivoNuevo = null;

        if (
            $request->hasFile(
                'archivo_comprobante'
            )
        ) {
            $archivoNuevo =
                $request
                    ->file(
                        'archivo_comprobante'
                    )
                    ->store(
                        'gestiones-operativas',
                        'public'
                    );
        }

        try {
            $gestion = DB::transaction(
                function () use (
                    $datosValidados,
                    $detallesViajeros,
                    $incluyeViajeros,
                    $archivoNuevo,
                    $operacion,
                    $tarea,
                    $request
                ) {
                    $datosGestion =
                        $this->prepararDatosGestion(
                            $datosValidados
                        );

                    /*
                     * El tipo real siempre proviene de la tarea.
                     * No se confía en un valor enviado desde el
                     * navegador.
                     */
                    $datosGestion['tipo'] =
                        $tarea->tipo_gestion;

                    $datosGestion['nombre'] =
                        $datosGestion['nombre']
                        ?? $tarea->nombre;

                    $datosGestion[
                        'operacion_viaje_id'
                    ] = $operacion->id;

                    $datosGestion[
                        'creado_por_user_id'
                    ] = Auth::id();

                    $datosGestion[
                        'actualizado_por_user_id'
                    ] = Auth::id();

                    if ($archivoNuevo) {
                        $datosGestion[
                            'archivo_comprobante'
                        ] = $archivoNuevo;
                    }

                    $gestion =
                        GestionOperativa::create(
                            $datosGestion
                        );

                    if (
                        $gestion->tipo ===
                            GestionOperativa::TIPO_TREN
                    ) {
                        $this->pasajesTrenService
                            ->sincronizarIntegrantes(
                                $gestion
                            );
                    } elseif ($incluyeViajeros) {
                        $this->sincronizarViajeros(
                            $gestion,
                            $detallesViajeros
                        );
                    }

                    $this->estadoTareaService
                        ->vincular(
                            $tarea,
                            $gestion,
                            $request->user()
                        );

                    $this->marcarEnPreparacion(
                        $operacion
                    );

                    return $gestion;
                }
            );
        } catch (Throwable $error) {
            if ($archivoNuevo) {
                Storage::disk('public')
                    ->delete($archivoNuevo);
            }

            throw $error;
        }

        return back()->with(
            'success',
            sprintf(
                'La gestión “%s” fue registrada y vinculada con la tarea.',
                $gestion->nombre
            )
        );
    }

    public function update(
        GuardarGestionOperativaRequest $request,
        GestionOperativa $gestion
    ): RedirectResponse {
        $gestion->loadMissing([
            'operacion.reserva',
            'tareas',
        ]);

        $operacion =
            $gestion->operacion;

        if (!$operacion) {
            throw ValidationException::withMessages([
                'gestion' =>
                    'La gestión no pertenece a una operación válida.',
            ]);
        }

        $this->validarExpedienteEditable(
            $operacion
        );

        $datosValidados =
            $request->validated();

        $detallesViajeros =
            $datosValidados['viajeros']
            ?? [];

        $incluyeViajeros =
            array_key_exists(
                'viajeros',
                $datosValidados
            );

        $archivoAnterior =
            $gestion->archivo_comprobante;

        $archivoNuevo = null;

        if (
            $request->hasFile(
                'archivo_comprobante'
            )
        ) {
            $archivoNuevo =
                $request
                    ->file(
                        'archivo_comprobante'
                    )
                    ->store(
                        'gestiones-operativas',
                        'public'
                    );
        }

        try {
            DB::transaction(
                function () use (
                    $gestion,
                    $datosValidados,
                    $detallesViajeros,
                    $incluyeViajeros,
                    $archivoNuevo,
                    $request
                ) {
                    $datosGestion =
                        $this->prepararDatosGestion(
                            $datosValidados
                        );

                    /*
                     * Una gestión ya vinculada no puede cambiar
                     * de tipo silenciosamente.
                     */
                    $datosGestion['tipo'] =
                        $gestion->tipo;

                    $datosGestion[
                        'actualizado_por_user_id'
                    ] = Auth::id();

                    if ($archivoNuevo) {
                        $datosGestion[
                            'archivo_comprobante'
                        ] = $archivoNuevo;
                    }

                    $gestion->update(
                        $datosGestion
                    );

                    if (
                        $gestion->tipo ===
                            GestionOperativa::TIPO_TREN
                    ) {
                        $this->pasajesTrenService
                            ->sincronizarIntegrantes(
                                $gestion
                            );
                    } elseif ($incluyeViajeros) {
                        $this->sincronizarViajeros(
                            $gestion,
                            $detallesViajeros
                        );
                    }

                    $gestion->load([
                        'tareas',
                    ]);

                    /*
                     * Una misma gestión puede estar vinculada
                     * con varias tareas.
                     */
                    foreach (
                        $gestion->tareas
                        as $tarea
                    ) {
                        $this->estadoTareaService
                            ->sincronizar(
                                $tarea,
                                $request->user()
                            );
                    }

                    $this->marcarEnPreparacion(
                        $gestion->operacion
                    );
                }
            );
        } catch (Throwable $error) {
            if ($archivoNuevo) {
                Storage::disk('public')
                    ->delete($archivoNuevo);
            }

            throw $error;
        }

        if (
            $archivoNuevo
            && $archivoAnterior
            && $archivoAnterior !== $archivoNuevo
        ) {
            Storage::disk('public')
                ->delete($archivoAnterior);
        }

        return back()->with(
            'success',
            'La gestión operativa fue actualizada correctamente.'
        );
    }

    public function destroy(
        GestionOperativa $gestion
    ): RedirectResponse {
        $gestion->loadMissing([
            'operacion.reserva',
            'tareas',
        ]);

        $operacion =
            $gestion->operacion;

        if (!$operacion) {
            throw ValidationException::withMessages([
                'gestion' =>
                    'La gestión no pertenece a una operación válida.',
            ]);
        }

        $this->validarExpedienteEditable(
            $operacion
        );

        $archivo =
            $gestion->archivo_comprobante;

        DB::transaction(
            function () use (
                $gestion,
                $operacion
            ) {
                /*
                 * Antes de eliminar la gestión, se desvinculan
                 * sus tareas para evitar referencias huérfanas.
                 */
                foreach (
                    $gestion->tareas
                    as $tarea
                ) {
                    $this->estadoTareaService
                        ->desvincular($tarea);
                }

                $gestion->delete();

                $this->marcarEnPreparacion(
                    $operacion
                );
            }
        );

        if ($archivo) {
            Storage::disk('public')
                ->delete($archivo);
        }

        return back()->with(
            'success',
            'La gestión operativa fue eliminada correctamente.'
        );
    }

    public function vincular(
        OperacionViaje $operacion,
        TareaOperacionViaje $tarea,
        GestionOperativa $gestion
    ): RedirectResponse {
        $this->validarTareaDeOperacion(
            $operacion,
            $tarea
        );

        $this->validarExpedienteEditable(
            $operacion
        );

        if (
            (int) $gestion->operacion_viaje_id
            !== (int) $operacion->id
        ) {
            throw ValidationException::withMessages([
                'gestion' =>
                    'La gestión seleccionada pertenece a otra operación.',
            ]);
        }

        $this->estadoTareaService
            ->vincular(
                $tarea,
                $gestion,
                request()->user()
            );

        $this->marcarEnPreparacion(
            $operacion
        );

        return back()->with(
            'success',
            'La gestión fue vinculada con la tarea correctamente.'
        );
    }

    public function desvincular(
        OperacionViaje $operacion,
        TareaOperacionViaje $tarea
    ): RedirectResponse {
        $this->validarTareaDeOperacion(
            $operacion,
            $tarea
        );

        $this->validarExpedienteEditable(
            $operacion
        );

        $this->estadoTareaService
            ->desvincular($tarea);

        $this->marcarEnPreparacion(
            $operacion
        );

        return back()->with(
            'success',
            'La gestión fue desvinculada de la tarea.'
        );
    }

    private function prepararDatosGestion(
        array $datos
    ): array {
        $datosGestion = Arr::except(
            $datos,
            [
                'viajeros',
                'archivo_comprobante',
            ]
        );

        if (
            isset($datosGestion['moneda'])
        ) {
            $datosGestion['moneda'] =
                strtoupper(
                    trim(
                        $datosGestion['moneda']
                    )
                );
        }

        if (
            isset(
                $datosGestion[
                    'referencia_confirmacion'
                ]
            )
        ) {
            $datosGestion[
                'referencia_confirmacion'
            ] = trim(
                $datosGestion[
                    'referencia_confirmacion'
                ]
            );
        }

        return $datosGestion;
    }

    private function sincronizarViajeros(
        GestionOperativa $gestion,
        array $viajeros
    ): void {
        $gestion->loadMissing([
            'operacion.reserva',
        ]);

        $reserva =
            $gestion->operacion?->reserva;

        if (!$reserva) {
            throw ValidationException::withMessages([
                'viajeros' =>
                    'No fue posible identificar la reserva de esta gestión.',
            ]);
        }

        $detalles = collect($viajeros)
            ->map(function ($detalle) {
                $viajeroId = filled(
                    $detalle[
                        'viajero_reserva_id'
                    ] ?? null
                )
                    ? (int) $detalle[
                        'viajero_reserva_id'
                    ]
                    : null;

                $clienteId = filled(
                    $detalle['cliente_id']
                    ?? null
                )
                    ? (int) $detalle[
                        'cliente_id'
                    ]
                    : null;

                return [
                    ...$detalle,
                    'viajero_reserva_id' =>
                        $viajeroId,
                    'cliente_id' => $clienteId,
                    'clave_integrante' => $viajeroId
                        ? 'viajero:'.$viajeroId
                        : 'cliente:'.$clienteId,
                ];
            });

        $identificadores = $detalles
            ->pluck('clave_integrante')
            ->filter()
            ->unique()
            ->values();

        if (
            $identificadores->count()
            !== $detalles->count()
        ) {
            throw ValidationException::withMessages([
                'viajeros' =>
                    'No se puede registrar dos veces al mismo viajero.',
            ]);
        }

        $viajerosIds = $detalles
            ->pluck('viajero_reserva_id')
            ->filter()
            ->values();

        $viajerosValidos =
            $reserva->viajerosReserva()
                ->whereKey(
                    $viajerosIds->all()
                )
                ->pluck('id')
                ->map(
                    fn ($id) => (int) $id
                );

        $identificadoresInvalidos =
            $viajerosIds->diff(
                $viajerosValidos
            );

        if (
            $identificadoresInvalidos
                ->isNotEmpty()
        ) {
            throw ValidationException::withMessages([
                'viajeros' =>
                    'Uno o más viajeros no pertenecen a esta reserva.',
            ]);
        }

        $clientesIds = $detalles
            ->pluck('cliente_id')
            ->filter()
            ->values();

        $clientesValidos = collect();

        if (
            $gestion->tipo ===
                GestionOperativa::TIPO_TREN
            && $reserva->viajerosReserva()
                ->doesntExist()
        ) {
            $clientesValidos = $reserva->esIndividual()
                ? collect([$reserva->cliente_id])
                    ->filter()
                : ($reserva->grupo?->clientes()
                    ->whereKey($clientesIds->all())
                    ->pluck('clientes.id')
                    ?? collect());

            $clientesValidos = $clientesValidos
                ->map(fn ($id) => (int) $id);
        }

        if (
            $clientesIds->diff($clientesValidos)
                ->isNotEmpty()
        ) {
            throw ValidationException::withMessages([
                'viajeros' =>
                    'Uno o más integrantes no pertenecen a esta reserva.',
            ]);
        }

        foreach ($detalles as $detalle) {
            $viajeroId = $detalle[
                'viajero_reserva_id'
            ];

            $clienteId = $detalle['cliente_id'];

            $gestion
                ->detallesViajeros()
                ->updateOrCreate(
                    $viajeroId
                        ? [
                            'viajero_reserva_id' =>
                                $viajeroId,
                        ]
                        : [
                            'cliente_id' =>
                                $clienteId,
                        ],
                    [
                        'viajero_reserva_id' =>
                            $viajeroId,

                        'cliente_id' =>
                            $clienteId,

                        'numero_documento' =>
                            $detalle[
                                'numero_documento'
                            ] ?? null,

                        'asiento' =>
                            $detalle['asiento']
                            ?? null,

                        'referencia_individual' =>
                            $detalle[
                                'referencia_individual'
                            ] ?? null,

                        'estado' =>
                            $detalle['estado']
                            ?? GestionOperativaViajero::ESTADO_PENDIENTE,

                        'restricciones' =>
                            $detalle[
                                'restricciones'
                            ] ?? null,

                        'observaciones' =>
                            $detalle[
                                'observaciones'
                            ] ?? null,
                    ]
                );
        }

        /*
         * Los viajeros que ya no llegaron en el formulario
         * se retiran de esta gestión.
         */
        if ($detalles->isEmpty()) {
            $gestion
                ->detallesViajeros()
                ->delete();

            return;
        }

        $gestion->detallesViajeros()
            ->get()
            ->reject(function ($detalle) use (
                $identificadores
            ) {
                $clave = $detalle
                    ->viajero_reserva_id
                    ? 'viajero:'.(int) $detalle
                        ->viajero_reserva_id
                    : 'cliente:'.(int) $detalle
                        ->cliente_id;

                return $identificadores
                    ->contains($clave);
            })
            ->each->delete();
    }

    private function validarTareaDeOperacion(
        OperacionViaje $operacion,
        TareaOperacionViaje $tarea
    ): void {
        if (
            (int) $tarea->operacion_viaje_id
            !== (int) $operacion->id
        ) {
            abort(404);
        }
    }

    private function validarTipoGenerico(
        string $tipo
    ): void {
        $tiposGenericos = [
            TareaOperacionViaje::TIPO_TREN,
            TareaOperacionViaje::TIPO_TRASLADO,
            TareaOperacionViaje::TIPO_ENTRADA,
            TareaOperacionViaje::TIPO_ALIMENTACION,
            TareaOperacionViaje::TIPO_ACTIVIDAD_RESERVADA,
            TareaOperacionViaje::TIPO_SEGURO,
            TareaOperacionViaje::TIPO_OTRO,
        ];

        if (
            !in_array(
                $tipo,
                $tiposGenericos,
                true
            )
        ) {
            throw ValidationException::withMessages([
                'tipo' =>
                    'Esta tarea debe gestionarse desde su módulo especializado.',
            ]);
        }
    }

    private function validarExpedienteEditable(
        OperacionViaje $operacion
    ): void {
        $operacion->loadMissing(
            'reserva'
        );

        if ($operacion->fueNotificada()) {
            throw ValidationException::withMessages([
                'operacion' =>
                    'El expediente ya fue notificado y no puede modificarse.',
            ]);
        }

        if (
            $operacion->reserva?->estaCancelada()
        ) {
            throw ValidationException::withMessages([
                'operacion' =>
                    'No se puede modificar una reserva cancelada.',
            ]);
        }
    }

    private function marcarEnPreparacion(
        OperacionViaje $operacion
    ): void {
        if (
            $operacion->estado ===
            OperacionViaje::ESTADO_PENDIENTE
        ) {
            $operacion->update([
                'estado' =>
                    OperacionViaje::ESTADO_PREPARACION,

                'actualizado_por_user_id' =>
                    Auth::id(),
            ]);
        }
    }
}
