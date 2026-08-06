<?php

namespace App\Http\Controllers;

use App\Models\GastoCancelacion;
use App\Models\Reserva;
use App\Services\GastoCancelacionService;
use Illuminate\Http\Request;
use InvalidArgumentException;

class GastoCancelacionController extends Controller
{
    public function __construct(
        private GastoCancelacionService $servicio
    ) {
    }

    public function index(Request $request)
    {
        $busqueda = trim(
            (string) $request->query(
                'buscar',
                ''
            )
        );

        $estado = $request->query(
            'estado'
        );

        $reservas = Reserva::query()
            ->where(
                'estado',
                Reserva::ESTADO_CANCELADA
            )
            ->with([
                'cliente',
                'destino',
                'grupo',
                'gastosCancelacion' =>
                    function ($consulta) use ($estado) {
                        $consulta
                            ->with([
                                'registradoPor',
                                'revisadoPor',
                            ])
                            ->when(
                                in_array(
                                    $estado,
                                    [
                                        GastoCancelacion::
                                            ESTADO_PENDIENTE,

                                        GastoCancelacion::
                                            ESTADO_APROBADO,

                                        GastoCancelacion::
                                            ESTADO_RECHAZADO,

                                        GastoCancelacion::
                                            ESTADO_ANULADO,
                                    ],
                                    true
                                ),
                                fn ($consulta) =>
                                    $consulta->where(
                                        'estado',
                                        $estado
                                    )
                            )
                            ->latest('id');
                    },
            ])
            ->when(
                $busqueda !== '',
                function ($consulta) use ($busqueda) {
                    $consulta->where(
                        function ($subconsulta) use (
                            $busqueda
                        ) {
                            $subconsulta
                                ->where(
                                    'codigo_reserva',
                                    'like',
                                    '%' .
                                    $busqueda .
                                    '%'
                                )
                                ->orWhereHas(
                                    'cliente',
                                    function ($clientes) use (
                                        $busqueda
                                    ) {
                                        $clientes
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
                                );
                        }
                    );
                }
            )
            ->orderByDesc(
                'fecha_cancelacion'
            )
            ->orderByDesc('id')
            ->paginate(10)
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
                (float) GastoCancelacion::query()
                    ->aprobados()
                    ->sum('monto'),

            'reservas_canceladas' =>
                Reserva::query()
                    ->where(
                        'estado',
                        Reserva::ESTADO_CANCELADA
                    )
                    ->count(),
        ];

        return view(
            'modules.reservas.gastos-cancelacion',
            [
                'titulo' =>
                    'Gastos documentados',

                'reservas' =>
                    $reservas,

                'metricas' =>
                    $metricas,

                'busqueda' =>
                    $busqueda,

                'estadoSeleccionado' =>
                    $estado,
            ]
        );
    }

    public function store(
        Request $request,
        Reserva $reserva
    ) {
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
                    'min:5',
                    'max:200',
                ],

                'monto' => [
                    'required',
                    'numeric',
                    'gt:0',
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
                    'mimes:pdf,jpg,jpeg,png,webp',
                    'max:10240',
                ],

                'observaciones' => [
                    'nullable',
                    'string',
                    'max:2000',
                ],
            ],
            [
                'proveedor.required' =>
                    'Ingresa el nombre del proveedor.',

                'concepto.required' =>
                    'Describe el concepto del gasto.',

                'concepto.min' =>
                    'El concepto debe tener al menos 5 caracteres.',

                'monto.required' =>
                    'Ingresa el monto del gasto.',

                'monto.gt' =>
                    'El monto debe ser mayor que cero.',

                'fecha_documento.before_or_equal' =>
                    'La fecha del comprobante no puede ser futura.',

                'archivo.required' =>
                    'Adjunta el comprobante del gasto.',

                'archivo.mimes' =>
                    'El comprobante debe ser PDF, JPG, PNG o WEBP.',

                'archivo.max' =>
                    'El comprobante no puede superar los 10 MB.',
            ]
        );

        try {
            $this->servicio->registrar(
                $reserva,
                $datos,
                $request->file('archivo'),
                (int) $request->user()->id
            );

            return back()->with(
                'success',
                'El gasto fue registrado y quedó pendiente de aprobación.'
            );
        } catch (InvalidArgumentException $error) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    $error->getMessage()
                );
        }
    }

    public function aprobar(
        Request $request,
        GastoCancelacion $gasto
    ) {
        $this->comprobarAdministrador(
            $request
        );

        $datos = $request->validate([
            'observacion_revision' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        try {
            $this->servicio->aprobar(
                $gasto,
                (int) $request->user()->id,
                $datos[
                    'observacion_revision'
                ] ?? null
            );

            return back()->with(
                'success',
                'El gasto fue aprobado y el reembolso fue recalculado.'
            );
        } catch (InvalidArgumentException $error) {
            return back()->with(
                'error',
                $error->getMessage()
            );
        }
    }

    public function rechazar(
        Request $request,
        GastoCancelacion $gasto
    ) {
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
            ],
            [
                'motivo_revision.required' =>
                    'Explica por qué se rechaza el gasto.',

                'motivo_revision.min' =>
                    'El motivo debe tener al menos 10 caracteres.',
            ]
        );

        try {
            $this->servicio->rechazar(
                $gasto,
                $datos['motivo_revision'],
                (int) $request->user()->id
            );

            return back()->with(
                'success',
                'El gasto fue rechazado.'
            );
        } catch (InvalidArgumentException $error) {
            return back()->with(
                'error',
                $error->getMessage()
            );
        }
    }

    public function anular(
        Request $request,
        GastoCancelacion $gasto
    ) {
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
            ],
            [
                'motivo_anulacion.required' =>
                    'Explica por qué se anula el gasto.',

                'motivo_anulacion.min' =>
                    'El motivo debe tener al menos 10 caracteres.',
            ]
        );

        try {
            $this->servicio->anular(
                $gasto,
                $datos['motivo_anulacion'],
                (int) $request->user()->id
            );

            return back()->with(
                'success',
                'El gasto fue anulado y la liquidación fue recalculada.'
            );
        } catch (InvalidArgumentException $error) {
            return back()->with(
                'error',
                $error->getMessage()
            );
        }
    }

    public function descargar(
        Request $request,
        GastoCancelacion $gasto
    ) {
        if (
            !$request->user() ||
            !$request->user()->estaActivo()
        ) {
            abort(
                403,
                'No tienes autorización para descargar el comprobante.'
            );
        }

        try {
            $ruta = $this->servicio
                ->obtenerRutaAbsoluta(
                    $gasto
                );

            /*
             * Eliminamos separadores del nombre original
             * para evitar nombres de descarga peligrosos.
             */
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
                        $gasto->archivo_mime,

                    'X-Content-Type-Options' =>
                        'nosniff',

                    'Cache-Control' =>
                        'private, no-store, max-age=0',
                ]
            );
        } catch (InvalidArgumentException $error) {
            return back()->with(
                'error',
                $error->getMessage()
            );
        }
    }

    private function comprobarAdministrador(
        Request $request
    ): void {
        if (
            !$request->user() ||
            !$request->user()->isAdmin()
        ) {
            abort(
                403,
                'Solamente un administrador puede aprobar, rechazar o anular gastos.'
            );
        }
    }
}