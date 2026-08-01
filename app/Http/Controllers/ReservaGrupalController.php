<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Destino;
use App\Models\Grupo;
use App\Models\Reserva;
use App\Services\ReservaGrupalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class ReservaGrupalController extends Controller
{
    public function __construct(
        private ReservaGrupalService $reservaService
    ) {
    }

    public function create()
    {
        $clientes = Cliente::query()
            ->activos()
            ->orderBy('nombres')
            ->orderBy('apellidos')
            ->get();

        $destinos = Destino::query()
            ->where('estado_publicacion', 'publicado')
            ->whereDate('fecha_salida', '>=', today())
            ->orderBy('fecha_salida')
            ->get();

        return view(
            'modules.reservas.grupal.create',
            [
                'titulo' => 'Nueva reserva grupal',
                'clientes' => $clientes,
                'destinos' => $destinos,
            ]
        );
    }

    public function edit(string $id)
    {
        $reserva = Reserva::with([
            'grupo.clientes',
            'grupo.responsablePago',
            'destino',
        ])->findOrFail($id);

        if (!$reserva->esGrupal()) {
            return to_route('reservas')->with(
                'error',
                'La reserva seleccionada no es grupal.'
            );
        }

        if ($reserva->estaCancelada()) {
            return to_route('reservas')->with(
                'error',
                'Las reservas canceladas no se pueden editar.'
            );
        }

        if (
            $reserva->estado !==
            Reserva::ESTADO_PENDIENTE
        ) {
            return to_route('reservas')->with(
                'error',
                'Solo se pueden editar reservas pendientes.'
            );
        }

        if ($reserva->pagos()->exists()) {
            return to_route('reservas')->with(
                'error',
                'La reserva tiene pagos registrados y no se puede editar.'
            );
        }

        if (!$reserva->grupo) {
            return to_route('reservas')->with(
                'error',
                'La reserva no tiene un grupo asociado.'
            );
        }

        $clientes = Cliente::query()
            ->activos()
            ->orderBy('nombres')
            ->orderBy('apellidos')
            ->get();

        $destinos = Destino::query()
            ->where('estado_publicacion', 'publicado')
            ->whereDate('fecha_salida', '>=', today())
            ->orderBy('fecha_salida')
            ->get();

        $integrantesActuales = $reserva->grupo
            ->clientes
            ->map(function ($cliente) {
                return [
                    'cliente_id' => $cliente->id,
                    'es_lider' =>
                        (bool) $cliente->pivot->es_lider,
                ];
            })
            ->values()
            ->all();

        return view(
            'modules.reservas.grupal.edit',
            [
                'titulo' => 'Editar reserva grupal',
                'reserva' => $reserva,
                'grupo' => $reserva->grupo,
                'clientes' => $clientes,
                'destinos' => $destinos,
                'integrantesActuales' =>
                    old(
                        'integrantes',
                        $integrantesActuales
                    ),
            ]
        );
    }

    public function update(
        Request $request,
        string $id
    ) {
        $datos = $request->validate([
            'nombre_grupo' => [
                'required',
                'string',
                'min:3',
                'max:150',
            ],
            'tipo_grupo' => [
                'required',
                Rule::in([
                    Grupo::TIPO_FAMILIAR,
                    Grupo::TIPO_INDEPENDIENTE,
                ]),
            ],
            'responsable_pago_id' => [
                'nullable',
                'required_if:tipo_grupo,familiar',
                'integer',
                'exists:clientes,id',
            ],
            'destino_id' => [
                'required',
                'integer',
                'exists:destinos,id',
            ],
            'integrantes' => [
                'required',
                'array',
                'min:2',
            ],
            'integrantes.*.cliente_id' => [
                'required',
                'integer',
                'distinct',
                'exists:clientes,id',
            ],
            'integrantes.*.es_lider' => [
                'required',
                'boolean',
            ],
        ], [
            'nombre_grupo.required' =>
                'Ingresa un nombre para identificar al grupo.',
            'nombre_grupo.min' =>
                'El nombre del grupo debe tener al menos 3 caracteres.',
            'nombre_grupo.max' =>
                'El nombre del grupo no puede superar 150 caracteres.',

            'tipo_grupo.required' =>
                'Selecciona el tipo de grupo.',
            'tipo_grupo.in' =>
                'El tipo de grupo seleccionado no es válido.',

            'responsable_pago_id.required_if' =>
                'Selecciona al responsable del pago familiar.',
            'responsable_pago_id.exists' =>
                'El responsable del pago no existe.',

            'destino_id.required' =>
                'Selecciona el paquete turístico.',
            'destino_id.exists' =>
                'El paquete seleccionado no existe.',

            'integrantes.required' =>
                'Agrega los integrantes del grupo.',
            'integrantes.array' =>
                'La lista de integrantes no es válida.',
            'integrantes.min' =>
                'Una reserva grupal necesita al menos dos integrantes.',

            'integrantes.*.cliente_id.required' =>
                'Selecciona correctamente cada integrante.',
            'integrantes.*.cliente_id.distinct' =>
                'No puedes agregar el mismo cliente más de una vez.',
            'integrantes.*.cliente_id.exists' =>
                'Uno de los integrantes no existe.',

            'integrantes.*.es_lider.required' =>
                'Selecciona el líder del grupo.',
            'integrantes.*.es_lider.boolean' =>
                'El líder seleccionado no es válido.',
        ]);

        try {
            $reserva = $this->reservaService
                ->actualizar(
                    (int) $id,
                    $datos
                );

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' =>
                        'Reserva grupal actualizada correctamente.',
                    'codigo' =>
                        $reserva->codigo_reserva,
                    'redirect' =>
                        route('reservas'),
                ]);
            }

            return to_route('reservas')->with(
                'success',
                'Reserva grupal actualizada correctamente.'
            );
        } catch (InvalidArgumentException $error) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' =>
                        $error->getMessage(),
                ], 422);
            }

            return back()
                ->withInput()
                ->with(
                    'error',
                    $error->getMessage()
                );
        } catch (\Throwable $error) {
            Log::error(
                'Error al actualizar reserva grupal',
                [
                    'reserva_id' => $id,
                    'mensaje' =>
                        $error->getMessage(),
                    'usuario_id' =>
                        Auth::id(),
                ]
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' =>
                        'No se pudo actualizar la reserva grupal.',
                ], 500);
            }

            return back()
                ->withInput()
                ->with(
                    'error',
                    'No se pudo actualizar la reserva grupal. Inténtalo nuevamente.'
                );
        }
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre_grupo' => [
                'required',
                'string',
                'min:3',
                'max:150',
            ],
            'tipo_grupo' => [
                'required',
                Rule::in([
                    Grupo::TIPO_FAMILIAR,
                    Grupo::TIPO_INDEPENDIENTE,
                ]),
            ],
            'responsable_pago_id' => [
                'nullable',
                'required_if:tipo_grupo,familiar',
                'integer',
                'exists:clientes,id',
            ],
            'destino_id' => [
                'required',
                'integer',
                'exists:destinos,id',
            ],
            'integrantes' => [
                'required',
                'array',
                'min:2',
            ],
            'integrantes.*.cliente_id' => [
                'required',
                'integer',
                'distinct',
                'exists:clientes,id',
            ],
            'integrantes.*.es_lider' => [
                'required',
                'boolean',
            ],
        ], [
            'nombre_grupo.required' =>
                'Ingresa un nombre para identificar al grupo.',
            'nombre_grupo.min' =>
                'El nombre del grupo debe tener al menos 3 caracteres.',
            'nombre_grupo.max' =>
                'El nombre del grupo no puede superar 150 caracteres.',

            'tipo_grupo.required' =>
                'Selecciona el tipo de grupo.',
            'tipo_grupo.in' =>
                'El tipo de grupo seleccionado no es válido.',

            'responsable_pago_id.required_if' =>
                'Selecciona al responsable del pago familiar.',
            'responsable_pago_id.exists' =>
                'El responsable del pago no existe.',

            'destino_id.required' =>
                'Selecciona el paquete turístico.',
            'destino_id.exists' =>
                'El paquete seleccionado no existe.',

            'integrantes.required' =>
                'Agrega los integrantes del grupo.',
            'integrantes.array' =>
                'La lista de integrantes no es válida.',
            'integrantes.min' =>
                'Una reserva grupal necesita al menos dos integrantes.',

            'integrantes.*.cliente_id.required' =>
                'Selecciona correctamente cada integrante.',
            'integrantes.*.cliente_id.distinct' =>
                'No puedes agregar el mismo cliente más de una vez.',
            'integrantes.*.cliente_id.exists' =>
                'Uno de los integrantes no existe.',

            'integrantes.*.es_lider.required' =>
                'Selecciona el líder del grupo.',
            'integrantes.*.es_lider.boolean' =>
                'El líder seleccionado no es válido.',
        ]);

        $usuarioId = Auth::id();

        if (!$usuarioId) {
            return redirect()
                ->route('login')
                ->with(
                    'error',
                    'Debes iniciar sesión para registrar una reserva.'
                );
        }

        try {
            $reserva = $this->reservaService->guardar(
                $datos,
                (int) $usuarioId
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' =>
                        'Reserva grupal registrada correctamente.',
                    'codigo' =>
                        $reserva->codigo_reserva,
                    'redirect' =>
                        route('reservas'),
                ], 201);
            }

            return to_route('reservas')->with(
                'success',
                'Reserva grupal registrada correctamente. Código: ' .
                $reserva->codigo_reserva
            );
        } catch (InvalidArgumentException $error) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $error->getMessage(),
                ], 422);
            }

            return back()
                ->withInput()
                ->with('error', $error->getMessage());
        } catch (\Throwable $error) {
            Log::error(
                'Error al registrar reserva grupal',
                [
                    'mensaje' => $error->getMessage(),
                    'usuario_id' => $usuarioId,
                ]
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' =>
                        'No se pudo registrar la reserva grupal.',
                ], 500);
            }

            return back()
                ->withInput()
                ->with(
                    'error',
                    'No se pudo registrar la reserva grupal. Inténtalo nuevamente.'
                );
        }
    }
}