<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Destino;
use App\Services\ReservaIndividualService;
use App\Services\PoliticaPagoReservaService;
use App\Models\Reserva;
use App\Models\PreReserva;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class ReservaIndividualController extends Controller
{
    public function __construct(
        private ReservaIndividualService $reservaService,
        private PoliticaPagoReservaService $politicaPago
    ) {
    }

    public function create(Request $request)
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
            'modules.reservas.individual.create',
            [
                'titulo' => 'Nueva reserva individual',
                'clientes' => $clientes,
                'destinos' => $destinos,
                'clienteSeleccionado' =>
                    $request->integer('cliente_id'),
                'destinoSeleccionado' =>
                    $request->integer('destino_id'),
                'preReservaId' =>
                    $request->integer('prereserva_id'),
            ]
        );
    }

    public function edit(string $id)
    {
        $reserva = \App\Models\Reserva::with([
            'cliente',
            'destino',
        ])->findOrFail($id);

        if (!$reserva->esIndividual()) {
            return to_route('reservas')->with(
                'error',
                'La reserva seleccionada no es individual.'
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
            \App\Models\Reserva::ESTADO_PENDIENTE
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
            'modules.reservas.individual.edit',
            [
                'titulo' => 'Editar reserva individual',
                'reserva' => $reserva,
                'clientes' => $clientes,
                'destinos' => $destinos,
            ]
        );
    }

    public function update(
        Request $request,
        string $id
    ) {
        $datos = $request->validate([
            'cliente_id' => [
                'required',
                'integer',
                'exists:clientes,id',
            ],
            'destino_id' => [
                'required',
                'integer',
                'exists:destinos,id',
            ],
        ], [
            'cliente_id.required' =>
                'Selecciona el cliente que realizará el viaje.',
            'cliente_id.exists' =>
                'El cliente seleccionado no existe.',
            'destino_id.required' =>
                'Selecciona el paquete turístico.',
            'destino_id.exists' =>
                'El paquete seleccionado no existe.',
        ]);

        try {
            $reserva = $this->reservaService->actualizar(
                (int) $id,
                (int) $datos['cliente_id'],
                (int) $datos['destino_id']
            );

            if ($reserva->politica_aceptada_at) {
                $reserva = $this->politicaPago
                    ->inicializar($reserva, true);
                $this->politicaPago->registrarAceptacion(
                    $reserva,
                    [
                        'canal_aceptacion_politica' =>
                            $reserva->canal_aceptacion_politica,
                        'referencia_aceptacion_politica' =>
                            $reserva->referencia_aceptacion_politica,
                    ]
                );
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' =>
                        'Reserva actualizada correctamente.',
                    'codigo' =>
                        $reserva->codigo_reserva,
                    'redirect' =>
                        route('reservas'),
                ]);
            }

            return to_route('reservas')->with(
                'success',
                'Reserva actualizada correctamente.'
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
                'Error al actualizar reserva individual',
                [
                    'reserva_id' => $id,
                    'mensaje' => $error->getMessage(),
                    'usuario_id' => Auth::id(),
                ]
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' =>
                        'No se pudo actualizar la reserva.',
                ], 500);
            }

            return back()
                ->withInput()
                ->with(
                    'error',
                    'No se pudo actualizar la reserva. Inténtalo nuevamente.'
                );
        }
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'cliente_id' => [
                'required',
                'integer',
                'exists:clientes,id',
            ],
            'destino_id' => [
                'required',
                'integer',
                'exists:destinos,id',
            ],
            'prereserva_id' => [
                'nullable',
                'integer',
                'exists:pre_reservas,id',
            ],
            'politica_aceptada' => [
                'accepted',
            ],
            'canal_aceptacion_politica' => [
                'required',
                'in:presencial,correo,whatsapp,telegram,otro',
            ],
            'referencia_aceptacion_politica' => [
                'required',
                'string',
                'min:5',
                'max:255',
            ],
        ], [
            'cliente_id.required' =>
                'Selecciona el cliente que realizará el viaje.',
            'cliente_id.exists' =>
                'El cliente seleccionado no existe.',
            'destino_id.required' =>
                'Selecciona el paquete turístico.',
            'destino_id.exists' =>
                'El paquete seleccionado no existe.',
            'prereserva_id.exists' =>
                'La prerreserva seleccionada no existe.',
            'politica_aceptada.accepted' =>
                'Confirma que el cliente aceptó la política de pagos y cancelación.',
            'canal_aceptacion_politica.required' =>
                'Selecciona el canal por el que el cliente aceptó la política.',
            'referencia_aceptacion_politica.required' =>
                'Registra una referencia que permita comprobar la aceptación.',
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
            $reserva = DB::transaction(function () use (
                $datos,
                $usuarioId
            ) {
                $preReserva = null;

                if (!empty($datos['prereserva_id'])) {
                    $preReserva = PreReserva::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $datos['prereserva_id']
                        );

                    if (
                        $preReserva->estado === 'convertida' ||
                        $preReserva->reserva_id
                    ) {
                        throw new InvalidArgumentException(
                            'Esta prerreserva ya fue convertida anteriormente.'
                        );
                    }
                }

                $reserva = $this->reservaService->guardar(
                    (int) $datos['cliente_id'],
                    (int) $datos['destino_id'],
                    (int) $usuarioId
                );
                $reserva = $this->politicaPago
                    ->inicializar($reserva);
                $reserva = $this->politicaPago
                    ->registrarAceptacion($reserva, $datos);

                if ($preReserva) {
                    $preReserva->update([
                        'estado' => 'convertida',
                        'reserva_id' => $reserva->id,
                        'user_id' => $usuarioId,
                    ]);
                }

                return $reserva;
            });

            /*
             * La prerreserva se actualizará en el siguiente paso,
             * después de validar que corresponda al cliente.
             */

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' =>
                        'Reserva registrada correctamente.',
                    'codigo' =>
                        $reserva->codigo_reserva,
                    'redirect' =>
                        route('pagos', [
                            'reserva_id' => $reserva->id,
                            'abrir_cobro' => 1,
                        ]),
                ], 201);
            }

            return to_route('pagos', [
                'reserva_id' => $reserva->id,
                'abrir_cobro' => 1,
            ])->with(
                'success',
                'Reserva provisional creada. Registra ahora el anticipo obligatorio de ' .
                ($reserva->moneda ?: 'USD') . ' ' .
                number_format((float) $reserva->monto_anticipo, 2, '.', ',') .
                ' antes del ' .
                $reserva->fecha_limite_anticipo?->format('d/m/Y H:i') . '.'
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
                'Error al registrar reserva individual',
                [
                    'mensaje' => $error->getMessage(),
                    'usuario_id' => $usuarioId,
                ]
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' =>
                        'No se pudo registrar la reserva.',
                ], 500);
            }

            return back()
                ->withInput()
                ->with(
                    'error',
                    'No se pudo registrar la reserva. Inténtalo nuevamente.'
                );
        }
    }
}