<?php

namespace App\Http\Controllers;

use App\Models\GastoCancelacion;
use App\Models\Reserva;
use App\Models\SolicitudCancelacion;
use App\Models\User;
use App\Services\GastoCancelacionService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use InvalidArgumentException;

class GastoCancelacionController extends Controller
{
    public function __construct(
        private GastoCancelacionService
            $servicio
    ) {
    }

    /**
     * Muestra reservas canceladas o con
     * cancelación pendiente.
     */
    public function index(
        Request $request
    ) {
        $this->usuarioAutenticado(
            $request
        );

        $busqueda = trim(
            $request
                ->string('buscar')
                ->toString()
        );

        $estadoSeleccionado = trim(
            $request
                ->string('estado')
                ->toString()
        );

        $estadosPermitidos = [
            GastoCancelacion::
                ESTADO_PENDIENTE,

            GastoCancelacion::
                ESTADO_APROBADO,

            GastoCancelacion::
                ESTADO_RECHAZADO,

            GastoCancelacion::
                ESTADO_ANULADO,
        ];

        if (
            !in_array(
                $estadoSeleccionado,
                $estadosPermitidos,
                true
            )
        ) {
            $estadoSeleccionado = '';
        }

        $consulta = Reserva::query()
            ->with([
                'cliente',
                'destino',
                'grupo',

                'solicitudCancelacionPendiente' =>
                    function ($consulta) {
                        $consulta->with([
                            'solicitadoPor',
                            'revisadoPor',
                        ]);
                    },

                'gastosCancelacion' =>
                    function ($consulta) {
                        $consulta
                            ->with([
                                'registradoPor',
                                'revisadoPor',
                            ])
                            ->latest();
                    },
            ])
            ->where(
                function ($consulta) {
                    $consulta
                        ->where(
                            'estado',
                            Reserva::
                                ESTADO_CANCELADA
                        )
                        ->orWhereHas(
                            'solicitudesCancelacion',
                            function (
                                $consultaSolicitud
                            ) {
                                $consultaSolicitud
                                    ->where(
                                        'estado',
                                        SolicitudCancelacion::
                                            ESTADO_PENDIENTE
                                    );
                            }
                        );
                }
            );

        if ($busqueda !== '') {
            $consulta->where(
                function (
                    $consultaBusqueda
                ) use (
                    $busqueda
                ) {
                    $consultaBusqueda
                        ->where(
                            'codigo_reserva',
                            'like',
                            '%' .
                                $busqueda .
                                '%'
                        )
                        ->orWhereHas(
                            'cliente',
                            function (
                                $consultaCliente
                            ) use (
                                $busqueda
                            ) {
                                $consultaCliente
                                    ->where(
                                        'nombres',
                                        'like',
                                        '%' .
                                            $busqueda .
                                            '%'
                                    )
                                    ->orWhere(
                                        'apellidos',
                                        'like',
                                        '%' .
                                            $busqueda .
                                            '%'
                                    )
                                    ->orWhere(
                                        'documento',
                                        'like',
                                        '%' .
                                            $busqueda .
                                            '%'
                                    );
                            }
                        )
                        ->orWhereHas(
                            'grupo',
                            function (
                                $consultaGrupo
                            ) use (
                                $busqueda
                            ) {
                                $consultaGrupo
                                    ->where(
                                        'nombre_grupo',
                                        'like',
                                        '%' .
                                            $busqueda .
                                            '%'
                                    );
                            }
                        );
                }
            );
        }

        if (
            $estadoSeleccionado !== ''
        ) {
            $consulta->whereHas(
                'gastosCancelacion',
                function (
                    $consultaGasto
                ) use (
                    $estadoSeleccionado
                ) {
                    $consultaGasto->where(
                        'estado',
                        $estadoSeleccionado
                    );
                }
            );
        }

        $reservas = $consulta
            ->latest('id')
            ->paginate(8)
            ->withQueryString();

        $metricas = [
            'pendientes' =>
                GastoCancelacion::query()
                    ->pendientes()
                    ->count(),

            'aprobados' =>
                GastoCancelacion::query()
                    ->aprobados()
                    ->count(),

            'total_aprobado' =>
                round(
                    (float)
                        GastoCancelacion::query()
                            ->aprobados()
                            ->sum('monto'),
                    2
                ),

            'reservas_canceladas' =>
                Reserva::query()
                    ->where(
                        'estado',
                        Reserva::
                            ESTADO_CANCELADA
                    )
                    ->count(),

            'en_revision' =>
                SolicitudCancelacion::query()
                    ->pendientes()
                    ->count(),
        ];

        return view(
            'modules.reservas.gastos-cancelacion',
            compact(
                'reservas',
                'metricas',
                'busqueda',
                'estadoSeleccionado'
            )
        );
    }

    /**
     * Registra un comprobante.
     */
    public function store(
        Request $request,
        Reserva $reserva
    ) {
        $usuario =
            $this->usuarioAutenticado(
                $request
            );

        $datos = $request->validate(
            [
                'proveedor' => [
                    'required',
                    'string',
                    'min:2',
                    'max:150',
                ],

                'concepto' => [
                    'required',
                    'string',
                    'min:3',
                    'max:200',
                ],

                'monto' => [
                    'required',
                    'numeric',
                    'min:0.01',
                    'max:9999999999.99',
                ],

                'numero_documento' => [
                    'nullable',
                    'string',
                    'max:100',
                ],

                'fecha_documento' => [
                    'nullable',
                    'date',
                    'before_or_equal:today',
                ],

                'archivo' => [
                    'required',
                    'file',
                    'mimetypes:application/pdf,image/jpeg,image/png,image/webp',
                    'max:10240',
                ],

                'observaciones' => [
                    'nullable',
                    'string',
                    'max:2000',
                ],

                'solicitud_id' => [
                    'nullable',
                    'integer',
                    'exists:solicitudes_cancelacion,id',
                ],
            ],
            [
                'proveedor.required' =>
                    'Debes indicar el proveedor.',

                'concepto.required' =>
                    'Debes indicar el concepto del gasto.',

                'monto.required' =>
                    'Debes indicar el monto del gasto.',

                'monto.min' =>
                    'El monto debe ser mayor que cero.',

                'archivo.required' =>
                    'Debes adjuntar el comprobante.',

                'archivo.mimetypes' =>
                    'El comprobante debe ser PDF, JPG, PNG o WEBP.',

                'archivo.max' =>
                    'El comprobante no puede superar los 10 MB.',
            ]
        );

        try {
            $this->validarSolicitudReserva(
                $request,
                $reserva->id
            );

            $this->servicio->registrar(
                $reserva,
                $datos,
                $request->file('archivo'),
                $usuario->id
            );

            return $this->redirigirResultado(
                $request,
                $reserva->id,
                'success',
                'El comprobante fue registrado y quedó pendiente de revisión.'
            );
        } catch (
            InvalidArgumentException $error
        ) {
            return $this->redirigirResultado(
                $request,
                $reserva->id,
                'error',
                $error->getMessage(),
                true
            );
        }
    }

    /**
     * Aprueba un gasto.
     */
    public function aprobar(
        Request $request,
        GastoCancelacion $gasto
    ) {
        $administrador =
            $this->comprobarAdministrador(
                $request
            );

        $datos = $request->validate([
            'observacion_revision' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'solicitud_id' => [
                'nullable',
                'integer',
                'exists:solicitudes_cancelacion,id',
            ],
        ]);

        try {
            $this->validarSolicitudReserva(
                $request,
                $gasto->reserva_id
            );

            $this->servicio->aprobar(
                $gasto,
                $administrador->id,
                $datos[
                    'observacion_revision'
                ] ?? null
            );

            return $this->redirigirResultado(
                $request,
                $gasto->reserva_id,
                'success',
                'El gasto fue aprobado.'
            );
        } catch (
            InvalidArgumentException $error
        ) {
            return $this->redirigirResultado(
                $request,
                $gasto->reserva_id,
                'error',
                $error->getMessage()
            );
        }
    }

    /**
     * Rechaza un gasto pendiente.
     */
    public function rechazar(
        Request $request,
        GastoCancelacion $gasto
    ) {
        $administrador =
            $this->comprobarAdministrador(
                $request
            );

        $datos = $request->validate(
            [
                'motivo_revision' => [
                    'required',
                    'string',
                    'min:10',
                    'max:2000',
                ],

                'solicitud_id' => [
                    'nullable',
                    'integer',
                    'exists:solicitudes_cancelacion,id',
                ],
            ],
            [
                'motivo_revision.required' =>
                    'Explica por qué se rechaza el gasto.',

                'motivo_revision.min' =>
                    'El motivo debe tener al menos 10 caracteres.',
            ]
        );

        try {
            $this->validarSolicitudReserva(
                $request,
                $gasto->reserva_id
            );

            $this->servicio->rechazar(
                $gasto,
                $datos['motivo_revision'],
                $administrador->id
            );

            return $this->redirigirResultado(
                $request,
                $gasto->reserva_id,
                'success',
                'El gasto fue rechazado.'
            );
        } catch (
            InvalidArgumentException $error
        ) {
            return $this->redirigirResultado(
                $request,
                $gasto->reserva_id,
                'error',
                $error->getMessage()
            );
        }
    }

    /**
     * Anula un gasto.
     */
    public function anular(
        Request $request,
        GastoCancelacion $gasto
    ) {
        $administrador =
            $this->comprobarAdministrador(
                $request
            );

        $datos = $request->validate(
            [
                'motivo_anulacion' => [
                    'required',
                    'string',
                    'min:10',
                    'max:2000',
                ],

                'solicitud_id' => [
                    'nullable',
                    'integer',
                    'exists:solicitudes_cancelacion,id',
                ],
            ],
            [
                'motivo_anulacion.required' =>
                    'Explica por qué se anula el gasto.',

                'motivo_anulacion.min' =>
                    'El motivo debe tener al menos 10 caracteres.',
            ]
        );

        try {
            $this->validarSolicitudReserva(
                $request,
                $gasto->reserva_id
            );

            $this->servicio->anular(
                $gasto,
                $datos['motivo_anulacion'],
                $administrador->id
            );

            return $this->redirigirResultado(
                $request,
                $gasto->reserva_id,
                'success',
                'El gasto fue anulado y la liquidación fue recalculada.'
            );
        } catch (
            InvalidArgumentException $error
        ) {
            return $this->redirigirResultado(
                $request,
                $gasto->reserva_id,
                'error',
                $error->getMessage()
            );
        }
    }

    /**
     * Descarga privada del comprobante.
     */
    public function descargar(
        Request $request,
        GastoCancelacion $gasto
    ) {
        $usuario =
            $this->usuarioAutenticado(
                $request
            );

        if (!$usuario->estaActivo()) {
            abort(
                403,
                'No tienes autorización para descargar el comprobante.'
            );
        }

        try {
            $ruta =
                $this->servicio
                    ->obtenerRutaAbsoluta(
                        $gasto
                    );

            $nombre = str_replace(
                [
                    '\\',
                    '/',
                    "\0",
                ],
                '-',
                $gasto
                    ->archivo_nombre_original
            );

            return response()->download(
                $ruta,
                $nombre,
                [
                    'Content-Type' =>
                        $gasto
                            ->archivo_mime,

                    'X-Content-Type-Options' =>
                        'nosniff',

                    'Cache-Control' =>
                        'private, no-store, max-age=0',
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
     * Comprueba que solicitud y reserva
     * correspondan entre sí.
     */
    private function validarSolicitudReserva(
        Request $request,
        int $reservaId
    ): ?SolicitudCancelacion {
        $solicitudId = (int)
            $request->input(
                'solicitud_id',
                0
            );

        if ($solicitudId <= 0) {
            return null;
        }

        $solicitud =
            SolicitudCancelacion::query()
                ->find(
                    $solicitudId
                );

        if (!$solicitud) {
            throw new InvalidArgumentException(
                'La solicitud de cancelación no existe.'
            );
        }

        if (
            $solicitud->reserva_id !==
            $reservaId
        ) {
            throw new InvalidArgumentException(
                'La solicitud no corresponde con la reserva seleccionada.'
            );
        }

        return $solicitud;
    }

    /**
     * Regresa al expediente cuando la acción
     * fue realizada desde ese apartado.
     */
    private function redirigirResultado(
        Request $request,
        int $reservaId,
        string $tipoMensaje,
        string $mensaje,
        bool $conInput = false
    ) {
        $solicitud =
            $this->validarSolicitudReserva(
                $request,
                $reservaId
            );

        if ($solicitud) {
            $respuesta = redirect()
                ->route(
                    'cancelaciones.solicitudes.show',
                    $solicitud
                )
                ->with(
                    $tipoMensaje,
                    $mensaje
                );

            if ($conInput) {
                $respuesta->withInput();
            }

            return $respuesta;
        }

        $respuesta = back()->with(
            $tipoMensaje,
            $mensaje
        );

        if ($conInput) {
            $respuesta->withInput();
        }

        return $respuesta;
    }

    /**
     * Obtiene el usuario autenticado con
     * el modelo correcto.
     */
    private function usuarioAutenticado(
        Request $request
    ): User {
        $usuario =
            $request->user();

        if (!$usuario instanceof User) {
            throw new AuthenticationException(
                'Debes iniciar sesión para continuar.'
            );
        }

        return $usuario;
    }

    /**
     * Limita decisiones financieras
     * a administradores.
     */
    private function comprobarAdministrador(
        Request $request
    ): User {
        $usuario =
            $this->usuarioAutenticado(
                $request
            );

        if (
            !$usuario->estaActivo() ||
            !$usuario->isAdmin()
        ) {
            abort(
                403,
                'Solamente un administrador activo puede aprobar, rechazar o anular gastos.'
            );
        }

        return $usuario;
    }
}