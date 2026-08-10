<?php

namespace App\Http\Controllers;

use App\Models\GastoCancelacion;
use App\Models\Reserva;
use App\Models\SolicitudCancelacion;
use App\Models\User;
use App\Services\SolicitudCancelacionService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class SolicitudCancelacionController extends Controller
{
    public function __construct(
        private SolicitudCancelacionService
            $solicitudCancelacionService
    ) {
    }

    /**
     * Lista las solicitudes.
     *
     * El administrador ve todas.
     * El agente ve solamente las que registró.
     */
    public function index(
        Request $request
    ) {
        $usuario =
            $this->usuarioAutenticado();

        $consulta =
            SolicitudCancelacion::query()
                ->with([
                    'reserva.cliente',
                    'reserva.destino',
                    'solicitadoPor',
                    'revisadoPor',
                ])
                ->latest(
                    'solicitado_at'
                );

        if (!$usuario->isAdmin()) {
            $consulta->where(
                'solicitado_por_user_id',
                $usuario->id
            );
        }

        if ($request->filled('estado')) {
            $consulta->where(
                'estado',
                $request
                    ->string('estado')
                    ->toString()
            );
        }

        if ($request->filled('buscar')) {
            $buscar = trim(
                $request
                    ->string('buscar')
                    ->toString()
            );

            $consulta->where(
                function (
                    $subconsulta
                ) use (
                    $buscar
                ) {
                    $subconsulta->whereHas(
                        'reserva',
                        function (
                            $consultaReserva
                        ) use (
                            $buscar
                        ) {
                            $consultaReserva
                                ->where(
                                    'codigo_reserva',
                                    'like',
                                    '%' .
                                        $buscar .
                                        '%'
                                )
                                ->orWhereHas(
                                    'cliente',
                                    function (
                                        $consultaCliente
                                    ) use (
                                        $buscar
                                    ) {
                                        $consultaCliente
                                            ->where(
                                                'nombres',
                                                'like',
                                                '%' .
                                                    $buscar .
                                                    '%'
                                            )
                                            ->orWhere(
                                                'apellidos',
                                                'like',
                                                '%' .
                                                    $buscar .
                                                    '%'
                                            )
                                            ->orWhere(
                                                'documento',
                                                'like',
                                                '%' .
                                                    $buscar .
                                                    '%'
                                            );
                                    }
                                );
                        }
                    );
                }
            );
        }

        $solicitudes =
            $consulta
                ->paginate(12)
                ->withQueryString();

        $metricas = [
            'pendientes' =>
                SolicitudCancelacion::query()
                    ->pendientes()
                    ->when(
                        !$usuario->isAdmin(),
                        function (
                            $consulta
                        ) use (
                            $usuario
                        ) {
                            $consulta->where(
                                'solicitado_por_user_id',
                                $usuario->id
                            );
                        }
                    )
                    ->count(),

            'aprobadas' =>
                SolicitudCancelacion::query()
                    ->aprobadas()
                    ->when(
                        !$usuario->isAdmin(),
                        function (
                            $consulta
                        ) use (
                            $usuario
                        ) {
                            $consulta->where(
                                'solicitado_por_user_id',
                                $usuario->id
                            );
                        }
                    )
                    ->count(),

            'rechazadas' =>
                SolicitudCancelacion::query()
                    ->rechazadas()
                    ->when(
                        !$usuario->isAdmin(),
                        function (
                            $consulta
                        ) use (
                            $usuario
                        ) {
                            $consulta->where(
                                'solicitado_por_user_id',
                                $usuario->id
                            );
                        }
                    )
                    ->count(),
        ];

        return view(
            'modules.reservas.cancelaciones.index',
            compact(
                'solicitudes',
                'metricas'
            )
        );
    }

    /**
     * Muestra el formulario de solicitud.
     */
    public function create(
        Reserva $reserva
    ) {
        $reserva->load([
            'cliente',
            'destino',
            'pagos',
            'devoluciones',
            'solicitudCancelacionPendiente',
        ]);

        if ($reserva->estaCancelada()) {
            return redirect()
                ->route('reservas.index')
                ->with(
                    'error',
                    'La reserva ya se encuentra cancelada.'
                );
        }

        if (
            $reserva
                ->tieneSolicitudCancelacionPendiente()
        ) {
            return redirect()
                ->route(
                    'cancelaciones.solicitudes.show',
                    $reserva
                        ->solicitudCancelacionPendiente
                )
                ->with(
                    'info',
                    'La reserva ya tiene una solicitud pendiente.'
                );
        }

        $pagadoBruto = round(
            (float)
                $reserva
                    ->pagos()
                    ->sum(
                        'monto_depositado'
                    ),
            2
        );

        return view(
            'modules.reservas.cancelaciones.create',
            compact(
                'reserva',
                'pagadoBruto'
            )
        );
    }

    /**
     * Guarda la solicitud sin cancelar
     * inmediatamente la reserva.
     */
    public function store(
        Request $request,
        Reserva $reserva
    ) {
        $usuario =
            $this->usuarioAutenticado();

        $datos = $request->validate([
            'solicitante' => [
                'required',
                Rule::in([
                    SolicitudCancelacion::
                        SOLICITANTE_CLIENTE,

                    SolicitudCancelacion::
                        SOLICITANTE_AGENCIA,

                    SolicitudCancelacion::
                        SOLICITANTE_PROVEEDOR,
                ]),
            ],

            'tipo_cancelacion' => [
                'required',
                Rule::in([
                    SolicitudCancelacion::
                        TIPO_DECISION_CLIENTE,

                    SolicitudCancelacion::
                        TIPO_FUERZA_MAYOR,

                    SolicitudCancelacion::
                        TIPO_RESPONSABILIDAD_AGENCIA,

                    SolicitudCancelacion::
                        TIPO_PROBLEMA_PROVEEDOR,

                    SolicitudCancelacion::
                        TIPO_CAMBIO_VIAJE,

                    SolicitudCancelacion::
                        TIPO_OTRO,
                ]),
            ],

            'motivo' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],

            'canal_solicitud' => [
                'required',
                Rule::in([
                    SolicitudCancelacion::
                        CANAL_PRESENCIAL,

                    SolicitudCancelacion::
                        CANAL_LLAMADA,

                    SolicitudCancelacion::
                        CANAL_WHATSAPP,

                    SolicitudCancelacion::
                        CANAL_CORREO,

                    SolicitudCancelacion::
                        CANAL_OTRO,
                ]),
            ],

            'referencia_comunicacion' => [
                'nullable',
                'string',
                'max:255',
            ],

            'evidencia' => [
                Rule::requiredIf(
                    fn () =>
                        $request->input(
                            'tipo_cancelacion'
                        ) ===
                        SolicitudCancelacion::
                            TIPO_FUERZA_MAYOR
                ),
                'nullable',
                'file',
                'mimetypes:application/pdf,image/jpeg,image/png,image/webp',
                'max:10240',
            ],

            'observaciones_internas' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        try {
            $solicitud =
                $this
                    ->solicitudCancelacionService
                    ->solicitar(
                        $reserva,
                        $datos,
                        $usuario->id,
                        $request->file(
                            'evidencia'
                        )
                    );

            return redirect()
                ->route(
                    'cancelaciones.solicitudes.show',
                    $solicitud
                )
                ->with(
                    'success',
                    'La solicitud fue enviada a revisión. La reserva todavía no ha sido cancelada.'
                );
        } catch (
            InvalidArgumentException $error
        ) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    $error->getMessage()
                );
        }
    }

    /**
     * Muestra el expediente.
     */
    public function show(
        SolicitudCancelacion $solicitud
    ) {
        $usuario =
            $this->usuarioAutenticado();

        if (
            !$usuario->isAdmin() &&
            $solicitud
                ->solicitado_por_user_id !==
                $usuario->id
        ) {
            abort(403);
        }

        $solicitud->load([
            'reserva.cliente',
            'reserva.destino',
            'reserva.pagos',
            'reserva.devoluciones',

            'reserva.gastosCancelacion' =>
                function ($consulta) {
                    $consulta
                        ->with([
                            'registradoPor',
                            'revisadoPor',
                        ])
                        ->latest();
                },

            'solicitadoPor',
            'revisadoPor',
            'anuladoPor',
        ]);

        $reserva =
            $solicitud->reserva;

        $pagadoBruto = round(
            (float)
                $reserva
                    ->pagos()
                    ->sum(
                        'monto_depositado'
                    ),
            2
        );

        $gastosPendientes = round(
            (float)
                GastoCancelacion::query()
                    ->pendientes()
                    ->where(
                        'reserva_id',
                        $reserva->id
                    )
                    ->sum('monto'),
            2
        );

        $gastosAprobados = round(
            (float)
                GastoCancelacion::query()
                    ->aprobados()
                    ->where(
                        'reserva_id',
                        $reserva->id
                    )
                    ->sum('monto'),
            2
        );

        $cantidadGastosPendientes =
            GastoCancelacion::query()
                ->pendientes()
                ->where(
                    'reserva_id',
                    $reserva->id
                )
                ->count();

        $reembolsoEstimado = round(
            max(
                0,
                $pagadoBruto -
                $gastosAprobados
            ),
            2
        );

        return view(
            'modules.reservas.cancelaciones.show',
            compact(
                'solicitud',
                'reserva',
                'pagadoBruto',
                'gastosPendientes',
                'gastosAprobados',
                'cantidadGastosPendientes',
                'reembolsoEstimado'
            )
        );
    }

    /**
     * Aprueba y cancela definitivamente.
     */
    public function aprobar(
        Request $request,
        SolicitudCancelacion $solicitud
    ) {
        $administrador =
            $this->exigirAdministrador();

        $datos = $request->validate([
            'observacion_revision' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'confirmar_sin_gastos' => [
                'nullable',
                'boolean',
            ],
        ]);

        try {
            $this
                ->solicitudCancelacionService
                ->aprobar(
                    $solicitud,
                    $administrador->id,
                    $datos[
                        'observacion_revision'
                    ] ?? null,
                    $request->boolean(
                        'confirmar_sin_gastos'
                    )
                );

            return redirect()
                ->route(
                    'cancelaciones.solicitudes.show',
                    $solicitud
                )
                ->with(
                    'success',
                    'La cancelación fue aprobada y la reserva quedó cancelada.'
                );
        } catch (
            InvalidArgumentException $error
        ) {
            return back()->with(
                'error',
                $error->getMessage()
            );
        }
    }

    /**
     * Rechaza una solicitud.
     */
    public function rechazar(
        Request $request,
        SolicitudCancelacion $solicitud
    ) {
        $administrador =
            $this->exigirAdministrador();

        $datos = $request->validate([
            'motivo_revision' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],
        ]);

        try {
            $this
                ->solicitudCancelacionService
                ->rechazar(
                    $solicitud,
                    $datos[
                        'motivo_revision'
                    ],
                    $administrador->id
                );

            return redirect()
                ->route(
                    'cancelaciones.solicitudes.show',
                    $solicitud
                )
                ->with(
                    'success',
                    'La solicitud fue rechazada y la reserva continúa activa.'
                );
        } catch (
            InvalidArgumentException $error
        ) {
            return back()->with(
                'error',
                $error->getMessage()
            );
        }
    }

    /**
     * Anula una solicitud pendiente.
     */
    public function anular(
        Request $request,
        SolicitudCancelacion $solicitud
    ) {
        $usuario =
            $this->usuarioAutenticado();

        $datos = $request->validate([
            'motivo_anulacion' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],
        ]);

        try {
            $this
                ->solicitudCancelacionService
                ->anular(
                    $solicitud,
                    $datos[
                        'motivo_anulacion'
                    ],
                    $usuario->id
                );

            return redirect()
                ->route(
                    'cancelaciones.solicitudes.show',
                    $solicitud
                )
                ->with(
                    'success',
                    'La solicitud fue anulada.'
                );
        } catch (
            InvalidArgumentException $error
        ) {
            return back()->with(
                'error',
                $error->getMessage()
            );
        }
    }

    /**
     * Descarga privada de la evidencia.
     */
    public function descargarEvidencia(
        SolicitudCancelacion $solicitud
    ) {
        $usuario =
            $this->usuarioAutenticado();

        if (
            !$usuario->isAdmin() &&
            $solicitud
                ->solicitado_por_user_id !==
                $usuario->id
        ) {
            abort(403);
        }

        try {
            $ruta =
                $this
                    ->solicitudCancelacionService
                    ->obtenerRutaEvidencia(
                        $solicitud
                    );

            return response()->download(
                $ruta,
                $solicitud
                    ->evidencia_nombre_original,
                [
                    'Content-Type' =>
                        $solicitud
                            ->evidencia_mime,

                    'X-Content-Type-Options' =>
                        'nosniff',

                    'Cache-Control' =>
                        'private, no-store',
                ]
            );
        } catch (
            InvalidArgumentException $error
        ) {
            return back()->with(
                'error',
                $error->getMessage()
            );
        }
    }

    /**
     * Devuelve el usuario autenticado con
     * el tipo correcto para el analizador.
     */
    private function usuarioAutenticado(): User
    {
        $usuario = Auth::user();

        if (!$usuario instanceof User) {
            throw new AuthenticationException(
                'Debes iniciar sesión para continuar.'
            );
        }

        return $usuario;
    }

    /**
     * Comprueba y devuelve un administrador.
     */
    private function exigirAdministrador(): User
    {
        $usuario =
            $this->usuarioAutenticado();

        if (
            !$usuario->estaActivo() ||
            !$usuario->isAdmin()
        ) {
            abort(
                403,
                'Solamente un administrador activo puede realizar esta acción.'
            );
        }

        return $usuario;
    }
}