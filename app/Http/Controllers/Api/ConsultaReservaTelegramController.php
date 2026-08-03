<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Reserva;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConsultaReservaTelegramController extends Controller
{
    /**
     * Busca las reservas vigentes asociadas con una
     * cédula o pasaporte.
     */
    public function buscar(Request $request): JsonResponse
    {
        if (! $this->estaAutorizado($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Solicitud no autorizada.',
            ], 401);
        }

        $datos = $request->validate([
            'documento' => [
                'required',
                'string',
                'min:6',
                'max:30',
            ],
        ]);

        $documento = $this->normalizarDocumento(
            $datos['documento']
        );

        $reservas = Reserva::query()
            ->with([
                'destino:id,nombre_paquete,pais,ciudad_destino',
            ])
            ->where(
                'estado',
                '!=',
                Reserva::ESTADO_CANCELADA
            )
            ->whereDate(
                'fecha_viaje',
                '>=',
                today()
            )
            ->where(function ($consulta) use ($documento) {
                $consulta
                    ->whereHas(
                        'cliente',
                        fn ($cliente) =>
                            $this->filtrarDocumento(
                                $cliente,
                                $documento
                            )
                    )
                    ->orWhereHas(
                        'viajerosReserva',
                        fn ($viajero) =>
                            $this->filtrarDocumento(
                                $viajero,
                                $documento
                            )
                    )
                    ->orWhereHas(
                        'grupo.clientes',
                        fn ($cliente) =>
                            $this->filtrarDocumento(
                                $cliente,
                                $documento
                            )
                    );
            })
            ->orderBy('fecha_viaje')
            ->get()
            ->map(function (Reserva $reserva) {
                return [
                    'reserva_id' => $reserva->id,

                    'codigo_reserva' =>
                        $reserva->codigo_reserva,

                    'paquete' =>
                        $reserva->destino?->nombre_paquete,

                    'destino' => trim(
                        ($reserva->destino?->ciudad_destino ?? '') .
                        ', ' .
                        ($reserva->destino?->pais ?? ''),
                        ', '
                    ),

                    'fecha_viaje' =>
                        $reserva->fecha_viaje?->format(
                            'Y-m-d'
                        ),

                    'tipo_reserva' =>
                        $reserva->tipo,

                    'estado_reserva' =>
                        $reserva->estado,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'total' => $reservas->count(),
            'reservas' => $reservas,
            'message' => $reservas->isEmpty()
                ? 'No se encontraron reservas vigentes para el documento ingresado.'
                : 'Reservas vigentes encontradas.',
        ]);
    }

    /**
     * Devuelve toda la información de una reserva
     * después de comprobar que pertenece al documento.
     */
    public function verificar(
        Request $request,
        Reserva $reserva
    ): JsonResponse {
        if (! $this->estaAutorizado($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Solicitud no autorizada.',
            ], 401);
        }

        $datos = $request->validate([
            'documento' => [
                'required',
                'string',
                'min:6',
                'max:30',
            ],
        ]);

        if (
            $reserva->estado === Reserva::ESTADO_CANCELADA ||
            $reserva->fecha_viaje?->lt(today())
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'La reserva seleccionada no está vigente.',
            ], 404);
        }

        $reserva->load([
            'cliente',
            'destino',
            'grupo.clientes',
            'viajerosReserva.cliente',
            'pagos',
            'operacionViaje.vuelos.boletos.cliente',
            'operacionViaje.vuelos.boletos.viajeroReserva',
            'operacionViaje.alojamientos.habitaciones',
            'operacionViaje.guias',
        ]);

        $documento = $this->normalizarDocumento(
            $datos['documento']
        );

        $cliente = $this->buscarClienteDeReserva(
            $reserva,
            $documento
        );

        if (! $cliente) {
            return response()->json([
                'success' => false,
                'message' =>
                    'La reserva seleccionada no pertenece al documento proporcionado.',
            ], 404);
        }

        $operacion = $reserva->operacionViaje;

        /*
         * Vuelos y boletos.
         * Solamente se devuelven boletos correspondientes
         * al cliente que realizó la consulta.
         */
        $vuelos = $operacion
            ? $operacion->vuelos->map(
                function ($vuelo) use ($cliente) {
                    $boletos = $vuelo->boletos
                        ->filter(
                            function ($boleto) use ($cliente) {
                                $perteneceAlCliente =
                                    (int) $boleto->cliente_id ===
                                    (int) $cliente->id;

                                $perteneceAlViajero =
                                    (int) optional(
                                        $boleto->viajeroReserva
                                    )->cliente_id ===
                                    (int) $cliente->id;

                                return $perteneceAlCliente ||
                                    $perteneceAlViajero;
                            }
                        )
                        ->map(
                            fn ($boleto) => [
                                'numero_boleto' =>
                                    $boleto->numero_boleto,

                                'asiento' =>
                                    $boleto->asiento,

                                'clase' =>
                                    $boleto->clase,

                                'estado_emision' =>
                                    $boleto->estado_emision,
                            ]
                        )
                        ->values();

                    return [
                        'tipo_tramo' =>
                            $vuelo->tipo_tramo,

                        'aerolinea' =>
                            $vuelo->aerolinea,

                        'numero_vuelo' =>
                            $vuelo->numero_vuelo,

                        'ciudad_origen' =>
                            $vuelo->ciudad_origen,

                        'aeropuerto_origen' =>
                            $vuelo->aeropuerto_origen,

                        'ciudad_destino' =>
                            $vuelo->ciudad_destino,

                        'aeropuerto_destino' =>
                            $vuelo->aeropuerto_destino,

                        'fecha_hora_salida' =>
                            $vuelo->fecha_hora_salida?->format(
                                'Y-m-d H:i'
                            ),

                        'fecha_hora_llegada' =>
                            $vuelo->fecha_hora_llegada?->format(
                                'Y-m-d H:i'
                            ),

                        'terminal_salida' =>
                            $vuelo->terminal_salida,

                        'terminal_llegada' =>
                            $vuelo->terminal_llegada,

                        'localizador_reserva' =>
                            $vuelo->localizador_reserva,

                        'equipaje_incluido' =>
                            $vuelo->equipaje_incluido,

                        'estado' =>
                            $vuelo->estado,

                        'boletos' =>
                            $boletos,
                    ];
                }
            )->values()
            : [];

        /*
         * Hoteles y habitaciones.
         */
        $alojamientos = $operacion
            ? $operacion->alojamientos->map(
                fn ($alojamiento) => [
                    'hotel' =>
                        $alojamiento->nombre_hotel,

                    'ciudad' =>
                        $alojamiento->ciudad,

                    'pais' =>
                        $alojamiento->pais,

                    'direccion' =>
                        $alojamiento->direccion,

                    'fecha_hora_entrada' =>
                        $alojamiento
                            ->fecha_hora_entrada
                            ?->format('Y-m-d H:i'),

                    'fecha_hora_salida' =>
                        $alojamiento
                            ->fecha_hora_salida
                            ?->format('Y-m-d H:i'),

                    'codigo_confirmacion' =>
                        $alojamiento
                            ->codigo_confirmacion,

                    'tipo_habitacion' =>
                        $alojamiento
                            ->tipo_habitacion,

                    'cantidad_habitaciones' =>
                        $alojamiento
                            ->cantidad_habitaciones,

                    'alimentacion_incluida' =>
                        $alojamiento
                            ->alimentacion_incluida,

                    'telefono_hotel' =>
                        $alojamiento
                            ->telefono_hotel,

                    'correo_hotel' =>
                        $alojamiento
                            ->correo_hotel,

                    'estado' =>
                        $alojamiento->estado,

                    'habitaciones' =>
                        $alojamiento->habitaciones
                            ->map(
                                fn ($habitacion) => [
                                    'tipo' =>
                                        $habitacion->tipo,

                                    'capacidad' =>
                                        $habitacion
                                            ->capacidad,

                                    'referencia' =>
                                        $habitacion
                                            ->referencia,
                                ]
                            )
                            ->values(),
                ]
            )->values()
            : [];

        /*
         * Guías registrados para el viaje.
         */
        $guias = $operacion
            ? $operacion->guias->map(
                fn ($guia) => [
                    'nombre' =>
                        $guia->nombre_completo,

                    'empresa' =>
                        $guia->empresa,

                    'ciudad_servicio' =>
                        $guia->ciudad_servicio,

                    'telefono' =>
                        $guia->telefono,

                    'correo' =>
                        $guia->correo,

                    'idiomas' =>
                        $guia->idiomas,

                    'fecha_inicio' =>
                        $guia->fecha_inicio?->format(
                            'Y-m-d'
                        ),

                    'fecha_fin' =>
                        $guia->fecha_fin?->format(
                            'Y-m-d'
                        ),

                    'punto_encuentro' =>
                        $guia->punto_encuentro,

                    'fecha_hora_encuentro' =>
                        $guia
                            ->fecha_hora_encuentro
                            ?->format('Y-m-d H:i'),

                    'servicios_incluidos' =>
                        $guia->servicios_incluidos,

                    'estado' =>
                        $guia->estado,
                ]
            )->values()
            : [];

        return response()->json([
            'success' => true,
            'cliente_verificado' => true,

            'reserva' => [
                'reserva_id' =>
                    $reserva->id,

                'codigo_reserva' =>
                    $reserva->codigo_reserva,

                'cliente' =>
                    $cliente->nombre_completo,

                'paquete' =>
                    $reserva->destino?->nombre_paquete,

                'destino' => trim(
                    ($reserva->destino?->ciudad_destino ?? '') .
                    ', ' .
                    ($reserva->destino?->pais ?? ''),
                    ', '
                ),

                'fecha_reserva' =>
                    $reserva->fecha_reserva?->format(
                        'Y-m-d'
                    ),

                'fecha_viaje' =>
                    $reserva->fecha_viaje?->format(
                        'Y-m-d'
                    ),

                'tipo_reserva' =>
                    $reserva->tipo,

                'cantidad_viajeros' =>
                    $reserva->cantidad_viajeros,

                'estado_reserva' =>
                    $reserva->estado,

                'estado_pago' =>
                    $reserva->estado_pago,

                'precio_total' =>
                    (float) $reserva
                        ->precio_total_viaje,

                'total_pagado' =>
                    $reserva->total_pagado,

                'saldo_pendiente' =>
                    $reserva->saldo_pendiente,

                'moneda' =>
                    $reserva->moneda,
            ],

            'pagos' => $reserva->pagos->map(
                fn ($pago) => [
                    'fecha' =>
                        $pago->fecha_pago?->format(
                            'Y-m-d H:i'
                        ),

                    'monto' =>
                        (float) $pago
                            ->monto_depositado,

                    'metodo' =>
                        $pago->metodo_pago,

                    'referencia' =>
                        $pago->referencia,
                ]
            )->values(),

            'seguimiento' => [
                'estado' =>
                    $operacion?->estado ?? 'pendiente',

                'documentacion_completa' =>
                    $operacion
                        ?->fecha_documentacion_completa
                        ?->format('Y-m-d H:i'),

                'fecha_notificacion' =>
                    $operacion
                        ?->fecha_notificacion
                        ?->format('Y-m-d H:i'),
            ],

            'vuelos' =>
                $vuelos,

            'alojamientos' =>
                $alojamientos,

            'guias' =>
                $guias,
        ]);
    }

    /**
     * Busca al cliente asociado con la reserva mediante
     * el documento proporcionado.
     */
    private function buscarClienteDeReserva(
        Reserva $reserva,
        string $documento
    ): ?Cliente {
        $candidatos = collect([
            $reserva->cliente,
        ])
            ->merge(
                $reserva->grupo?->clientes ?? []
            )
            ->merge(
                $reserva->viajerosReserva
                    ->pluck('cliente')
            )
            ->filter()
            ->unique('id');

        return $candidatos->first(
            fn (Cliente $cliente) =>
                $this->normalizarDocumento(
                    $cliente->documento
                ) === $documento
        );
    }

    /**
     * Verifica que la solicitud proceda desde n8n.
     */
    private function estaAutorizado(
        Request $request
    ): bool {
        $secretoConfigurado = (string) config(
            'services.n8n.api_secret'
        );

        $secretoRecibido = (string) $request->header(
            'X-N8N-API-SECRET'
        );

        return $secretoConfigurado !== ''
            && $secretoRecibido !== ''
            && hash_equals(
                $secretoConfigurado,
                $secretoRecibido
            );
    }

    /**
     * Normaliza cédulas y pasaportes para evitar
     * diferencias por espacios, guiones o mayúsculas.
     */
    private function normalizarDocumento(
        string $documento
    ): string {
        return mb_strtoupper(
            preg_replace(
                '/[\s\-]+/',
                '',
                trim($documento)
            )
        );
    }

    /**
     * Aplica la comparación normalizada del documento
     * dentro de una consulta Eloquent.
     */
    private function filtrarDocumento(
        $consulta,
        string $documento
    ) {
        return $consulta->whereRaw(
            "UPPER(REPLACE(REPLACE(documento, ' ', ''), '-', '')) = ?",
            [$documento]
        );
    }
}