<?php

namespace App\Services;

use App\Models\GastoCancelacion;
use App\Models\Reserva;
use App\Models\SolicitudCancelacion;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

class SolicitudCancelacionService
{
    private const DISCO =
        'local';

    private const TAMANO_MAXIMO =
        10 * 1024 * 1024;

    private const TIPOS_ARCHIVO_PERMITIDOS = [
        'application/pdf' =>
            'pdf',

        'image/jpeg' =>
            'jpg',

        'image/png' =>
            'png',

        'image/webp' =>
            'webp',
    ];

    public function __construct(
        private CancelacionReservaService
            $cancelacionReservaService
    ) {
    }

    /**
     * Crea una solicitud sin cancelar
     * inmediatamente la reserva.
     */
    public function solicitar(
        Reserva $reserva,
        array $datos,
        int $usuarioId,
        ?UploadedFile $evidencia = null
    ): SolicitudCancelacion {
        $this->validarDatosSolicitud(
            $datos,
            $evidencia
        );

        $usuario = User::query()
            ->findOrFail(
                $usuarioId
            );

        if (!$usuario->estaActivo()) {
            throw new InvalidArgumentException(
                'El usuario no se encuentra activo.'
            );
        }

        $metadataEvidencia = null;

        if ($evidencia) {
            $metadataEvidencia =
                $this->guardarEvidencia(
                    $reserva,
                    $evidencia
                );
        }

        try {
            return DB::transaction(
                function () use (
                    $reserva,
                    $datos,
                    $usuarioId,
                    $metadataEvidencia
                ) {
                    /*
                     * Bloqueamos la reserva para evitar
                     * solicitudes simultáneas.
                     */
                    $reservaBloqueada =
                        Reserva::query()
                            ->lockForUpdate()
                            ->findOrFail(
                                $reserva->id
                            );

                    if (
                        $reservaBloqueada
                            ->estaCancelada()
                    ) {
                        throw new InvalidArgumentException(
                            'No se puede solicitar la cancelación porque la reserva ya está cancelada.'
                        );
                    }

                    $existePendiente =
                        SolicitudCancelacion::query()
                            ->where(
                                'reserva_id',
                                $reservaBloqueada->id
                            )
                            ->where(
                                'estado',
                                SolicitudCancelacion::
                                    ESTADO_PENDIENTE
                            )
                            ->exists();

                    if ($existePendiente) {
                        throw new InvalidArgumentException(
                            'La reserva ya tiene una solicitud de cancelación pendiente.'
                        );
                    }

                    /*
                     * Se guarda el total bruto pagado.
                     * No se descuentan devoluciones.
                     */
                    $montoPagado = round(
                        (float)
                            $reservaBloqueada
                                ->pagos()
                                ->sum(
                                    'monto_depositado'
                                ),
                        2
                    );

                    $estadoAnterior =
                        $reservaBloqueada
                            ->estado_cobranza;

                    $solicitud =
                        SolicitudCancelacion::create([
                            'reserva_id' =>
                                $reservaBloqueada->id,

                            'solicitado_por_user_id' =>
                                $usuarioId,

                            'revisado_por_user_id' =>
                                null,

                            'solicitante' =>
                                $datos[
                                    'solicitante'
                                ],

                            'tipo_cancelacion' =>
                                $datos[
                                    'tipo_cancelacion'
                                ],

                            'motivo' =>
                                trim(
                                    (string)
                                        $datos['motivo']
                                ),

                            'canal_solicitud' =>
                                $datos[
                                    'canal_solicitud'
                                ],

                            'referencia_comunicacion' =>
                                !empty(
                                    $datos[
                                        'referencia_comunicacion'
                                    ]
                                )
                                    ? trim(
                                        (string)
                                            $datos[
                                                'referencia_comunicacion'
                                            ]
                                    )
                                    : null,

                            'evidencia_path' =>
                                $metadataEvidencia[
                                    'path'
                                ] ?? null,

                            'evidencia_nombre_original' =>
                                $metadataEvidencia[
                                    'nombre_original'
                                ] ?? null,

                            'evidencia_mime' =>
                                $metadataEvidencia[
                                    'mime'
                                ] ?? null,

                            'evidencia_tamano' =>
                                $metadataEvidencia[
                                    'tamano'
                                ] ?? null,

                            'evidencia_hash' =>
                                $metadataEvidencia[
                                    'hash'
                                ] ?? null,

                            'monto_pagado_solicitud' =>
                                $montoPagado,

                            'moneda' =>
                                $reservaBloqueada
                                    ->moneda ?: 'USD',

                            'estado_cobranza_anterior' =>
                                $estadoAnterior,

                            'estado' =>
                                SolicitudCancelacion::
                                    ESTADO_PENDIENTE,

                            'observaciones_internas' =>
                                !empty(
                                    $datos[
                                        'observaciones_internas'
                                    ]
                                )
                                    ? trim(
                                        (string)
                                            $datos[
                                                'observaciones_internas'
                                            ]
                                    )
                                    : null,

                            'solicitado_at' =>
                                now(),
                        ]);

                    /*
                     * La reserva continúa confirmada.
                     * Solamente cambia el estado de
                     * cobranza.
                     */
                    $reservaBloqueada
                        ->forceFill([
                            'estado_cobranza' =>
                                Reserva::
                                    COBRANZA_REVISION_CANCELACION,
                        ])
                        ->save();

                    return $solicitud->fresh([
                        'reserva',
                        'solicitadoPor',
                    ]);
                }
            );
        } catch (Throwable $error) {
            /*
             * Si falla la base de datos, eliminamos
             * el archivo para evitar evidencia
             * huérfana.
             */
            if (
                $metadataEvidencia &&
                !empty(
                    $metadataEvidencia['path']
                )
            ) {
                Storage::disk(
                    self::DISCO
                )->delete(
                    $metadataEvidencia['path']
                );
            }

            throw $error;
        }
    }

    /**
     * Aprueba la solicitud y recién entonces
     * cancela la reserva.
     */
    public function aprobar(
        SolicitudCancelacion $solicitud,
        int $usuarioId,
        ?string $observacion = null,
        bool $confirmarSinGastos = false
    ): SolicitudCancelacion {
        $administrador =
            $this->obtenerAdministrador(
                $usuarioId
            );

        return DB::transaction(
            function () use (
                $solicitud,
                $administrador,
                $observacion,
                $confirmarSinGastos
            ) {
                $solicitudBloqueada =
                    SolicitudCancelacion::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $solicitud->id
                        );

                if (
                    !$solicitudBloqueada
                        ->estaPendiente()
                ) {
                    throw new InvalidArgumentException(
                        'Solamente se pueden aprobar solicitudes pendientes.'
                    );
                }

                $reserva =
                    Reserva::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $solicitudBloqueada
                                ->reserva_id
                        );

                if ($reserva->estaCancelada()) {
                    throw new InvalidArgumentException(
                        'La reserva ya se encuentra cancelada.'
                    );
                }

                /*
                 * No se puede tomar una decisión mientras
                 * existan comprobantes pendientes.
                 */
                $cantidadGastosPendientes =
                    GastoCancelacion::query()
                        ->pendientes()
                        ->where(
                            'reserva_id',
                            $reserva->id
                        )
                        ->count();

                if (
                    $cantidadGastosPendientes > 0
                ) {
                    throw new InvalidArgumentException(
                        'Debes aprobar o rechazar todos los gastos pendientes antes de aprobar la cancelación.'
                    );
                }

                $gastosAprobados =
                    GastoCancelacion::query()
                        ->aprobados()
                        ->where(
                            'reserva_id',
                            $reserva->id
                        )
                        ->orderBy('id')
                        ->get();

                $totalGastos = round(
                    (float)
                        $gastosAprobados
                            ->sum('monto'),
                    2
                );

                $montoPagadoBruto =
                    round(
                        (float)
                            $reserva
                                ->pagos()
                                ->sum(
                                    'monto_depositado'
                                ),
                        2
                    );

                if (
                    $totalGastos >
                    $montoPagadoBruto
                ) {
                    throw new InvalidArgumentException(
                        'Los gastos aprobados no pueden superar el monto pagado por el cliente.'
                    );
                }

                /*
                 * Si no existen gastos, el administrador
                 * debe confirmarlo expresamente.
                 */
                if (
                    $totalGastos <= 0 &&
                    !$confirmarSinGastos
                ) {
                    throw new InvalidArgumentException(
                        'Confirma que no existen gastos no reembolsables antes de aprobar la cancelación.'
                    );
                }

                /*
                 * Si la cancelación es responsabilidad
                 * de la agencia o proveedor, los gastos
                 * no pueden cargarse al cliente.
                 */
                if (
                    in_array(
                        $solicitudBloqueada
                            ->tipo_cancelacion,
                        [
                            SolicitudCancelacion::
                                TIPO_RESPONSABILIDAD_AGENCIA,

                            SolicitudCancelacion::
                                TIPO_PROBLEMA_PROVEEDOR,
                        ],
                        true
                    ) &&
                    $totalGastos > 0
                ) {
                    throw new InvalidArgumentException(
                        'Cuando la cancelación es responsabilidad de la agencia o del proveedor, los gastos aprobados no pueden descontarse al cliente. Rechaza o anula esos gastos antes de continuar.'
                    );
                }

                /*
                 * Para fuerza mayor verificamos que la
                 * evidencia siga existiendo y conserve
                 * su integridad.
                 */
                if (
                    $solicitudBloqueada
                        ->requiereEvidencia()
                ) {
                    if (
                        !$solicitudBloqueada
                            ->tieneEvidencia()
                    ) {
                        throw new InvalidArgumentException(
                            'La solicitud por fuerza mayor requiere evidencia.'
                        );
                    }

                    if (
                        !$this
                            ->verificarIntegridadEvidencia(
                                $solicitudBloqueada
                            )
                    ) {
                        throw new InvalidArgumentException(
                            'La evidencia de fuerza mayor no existe o su integridad no es válida.'
                        );
                    }
                }

                $detalleGastos =
                    $this->construirDetalleGastos(
                        $gastosAprobados
                    );

                $evidenciaCancelacion =
                    $this->construirReferenciaEvidencia(
                        $solicitudBloqueada
                    );

                /*
                 * Convertimos los tipos de la solicitud
                 * a los tipos utilizados por el servicio
                 * actual de cancelación.
                 */
                $tipoServicio =
                    $this->convertirTipoCancelacion(
                        $solicitudBloqueada
                            ->tipo_cancelacion
                    );

                /*
                 * Este es el único punto donde la
                 * solicitud manual cancela realmente
                 * la reserva.
                 */
                $this->cancelacionReservaService
                    ->cancelar(
                        $reserva,
                        [
                            'tipo_cancelacion' =>
                                $tipoServicio,

                            'motivo_cancelacion' =>
                                $solicitudBloqueada
                                    ->motivo,

                            'gastos_no_reembolsables' =>
                                $totalGastos,

                            'detalle_gastos_no_reembolsables' =>
                                $detalleGastos,

                            'evidencia_cancelacion' =>
                                $evidenciaCancelacion,
                        ],
                        $administrador->id,
                        false
                    );

                $solicitudBloqueada->update([
                    'estado' =>
                        SolicitudCancelacion::
                            ESTADO_APROBADA,

                    'revisado_por_user_id' =>
                        $administrador->id,

                    'motivo_revision' =>
                        $observacion
                            ? trim(
                                $observacion
                            )
                            : 'Solicitud y liquidación revisadas y aprobadas.',

                    'revisado_at' =>
                        now(),
                ]);

                return $solicitudBloqueada
                    ->fresh([
                        'reserva',
                        'solicitadoPor',
                        'revisadoPor',
                    ]);
            }
        );
    }

    /**
     * Rechaza una solicitud y mantiene
     * activa la reserva.
     */
    public function rechazar(
        SolicitudCancelacion $solicitud,
        string $motivo,
        int $usuarioId
    ): SolicitudCancelacion {
        $administrador =
            $this->obtenerAdministrador(
                $usuarioId
            );

        $motivo = trim(
            $motivo
        );

        if (
            mb_strlen($motivo) < 10
        ) {
            throw new InvalidArgumentException(
                'El motivo del rechazo debe tener al menos 10 caracteres.'
            );
        }

        return DB::transaction(
            function () use (
                $solicitud,
                $administrador,
                $motivo
            ) {
                $solicitudBloqueada =
                    SolicitudCancelacion::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $solicitud->id
                        );

                if (
                    !$solicitudBloqueada
                        ->estaPendiente()
                ) {
                    throw new InvalidArgumentException(
                        'Solamente se pueden rechazar solicitudes pendientes.'
                    );
                }

                $reserva =
                    Reserva::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $solicitudBloqueada
                                ->reserva_id
                        );

                if ($reserva->estaCancelada()) {
                    throw new InvalidArgumentException(
                        'La reserva ya está cancelada y la solicitud no puede rechazarse.'
                    );
                }

                $solicitudBloqueada->update([
                    'estado' =>
                        SolicitudCancelacion::
                            ESTADO_RECHAZADA,

                    'revisado_por_user_id' =>
                        $administrador->id,

                    'motivo_revision' =>
                        $motivo,

                    'revisado_at' =>
                        now(),
                ]);

                $this->restaurarCobranza(
                    $reserva,
                    $solicitudBloqueada
                );

                return $solicitudBloqueada
                    ->fresh([
                        'reserva',
                        'solicitadoPor',
                        'revisadoPor',
                    ]);
            }
        );
    }

    /**
     * Anula una solicitud antes de que
     * sea revisada.
     */
    public function anular(
        SolicitudCancelacion $solicitud,
        string $motivo,
        int $usuarioId
    ): SolicitudCancelacion {
        $usuario = User::query()
            ->findOrFail(
                $usuarioId
            );

        if (!$usuario->estaActivo()) {
            throw new InvalidArgumentException(
                'El usuario no se encuentra activo.'
            );
        }

        $motivo = trim(
            $motivo
        );

        if (
            mb_strlen($motivo) < 10
        ) {
            throw new InvalidArgumentException(
                'El motivo de anulación debe tener al menos 10 caracteres.'
            );
        }

        return DB::transaction(
            function () use (
                $solicitud,
                $usuario,
                $motivo
            ) {
                $solicitudBloqueada =
                    SolicitudCancelacion::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $solicitud->id
                        );

                if (
                    !$solicitudBloqueada
                        ->estaPendiente()
                ) {
                    throw new InvalidArgumentException(
                        'Solamente se pueden anular solicitudes pendientes.'
                    );
                }

                if (
                    $solicitudBloqueada
                        ->solicitado_por_user_id !==
                        $usuario->id &&
                    !$usuario->isAdmin()
                ) {
                    throw new InvalidArgumentException(
                        'No tienes autorización para anular esta solicitud.'
                    );
                }

                $reserva =
                    Reserva::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $solicitudBloqueada
                                ->reserva_id
                        );

                if ($reserva->estaCancelada()) {
                    throw new InvalidArgumentException(
                        'La reserva ya está cancelada.'
                    );
                }

                $solicitudBloqueada->update([
                    'estado' =>
                        SolicitudCancelacion::
                            ESTADO_ANULADA,

                    'anulado_por_user_id' =>
                        $usuario->id,

                    'anulado_at' =>
                        now(),

                    'motivo_revision' =>
                        $motivo,
                ]);

                $this->restaurarCobranza(
                    $reserva,
                    $solicitudBloqueada
                );

                return $solicitudBloqueada
                    ->fresh([
                        'reserva',
                        'solicitadoPor',
                        'anuladoPor',
                    ]);
            }
        );
    }

    /**
     * Comprueba la integridad de la evidencia.
     */
    public function verificarIntegridadEvidencia(
        SolicitudCancelacion $solicitud
    ): bool {
        if (
            !$solicitud->tieneEvidencia()
        ) {
            return false;
        }

        $disco = Storage::disk(
            self::DISCO
        );

        if (
            !$disco->exists(
                $solicitud->evidencia_path
            )
        ) {
            return false;
        }

        $rutaAbsoluta =
            $disco->path(
                $solicitud
                    ->evidencia_path
            );

        $hashActual = hash_file(
            'sha256',
            $rutaAbsoluta
        );

        return
            is_string($hashActual) &&
            hash_equals(
                (string)
                    $solicitud
                        ->evidencia_hash,
                $hashActual
            );
    }

    /**
     * Devuelve la ruta privada de la evidencia.
     */
    public function obtenerRutaEvidencia(
        SolicitudCancelacion $solicitud
    ): string {
        if (
            !$this
                ->verificarIntegridadEvidencia(
                    $solicitud
                )
        ) {
            throw new InvalidArgumentException(
                'La evidencia no existe o su integridad no es válida.'
            );
        }

        return Storage::disk(
            self::DISCO
        )->path(
            $solicitud->evidencia_path
        );
    }

    /**
     * Valida la información principal.
     */
    private function validarDatosSolicitud(
        array $datos,
        ?UploadedFile $evidencia
    ): void {
        $solicitantesPermitidos = [
            SolicitudCancelacion::
                SOLICITANTE_CLIENTE,

            SolicitudCancelacion::
                SOLICITANTE_AGENCIA,

            SolicitudCancelacion::
                SOLICITANTE_PROVEEDOR,

            SolicitudCancelacion::
                SOLICITANTE_SISTEMA,
        ];

        $tiposPermitidos = [
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
        ];

        $canalesPermitidos = [
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
        ];

        if (
            !in_array(
                $datos['solicitante'] ?? null,
                $solicitantesPermitidos,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Selecciona quién solicita la cancelación.'
            );
        }

        if (
            !in_array(
                $datos[
                    'tipo_cancelacion'
                ] ?? null,
                $tiposPermitidos,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Selecciona un tipo de cancelación válido.'
            );
        }

        $motivo = trim(
            (string) (
                $datos['motivo'] ?? ''
            )
        );

        if (
            mb_strlen($motivo) < 10
        ) {
            throw new InvalidArgumentException(
                'El motivo debe tener al menos 10 caracteres.'
            );
        }

        if (
            mb_strlen($motivo) > 1000
        ) {
            throw new InvalidArgumentException(
                'El motivo no puede superar los 1000 caracteres.'
            );
        }

        if (
            !in_array(
                $datos[
                    'canal_solicitud'
                ] ?? null,
                $canalesPermitidos,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Selecciona un canal de solicitud válido.'
            );
        }

        $tipo =
            $datos['tipo_cancelacion'];

        if (
            $tipo ===
                SolicitudCancelacion::
                    TIPO_FUERZA_MAYOR &&
            !$evidencia
        ) {
            throw new InvalidArgumentException(
                'Las solicitudes por fuerza mayor requieren evidencia.'
            );
        }

        if ($evidencia) {
            $this->validarArchivoEvidencia(
                $evidencia
            );
        }
    }

    /**
     * Guarda la evidencia de forma privada.
     */
    private function guardarEvidencia(
        Reserva $reserva,
        UploadedFile $evidencia
    ): array {
        $mime = (string)
            $evidencia->getMimeType();

        $extension =
            self::TIPOS_ARCHIVO_PERMITIDOS[
                $mime
            ];

        $rutaTemporal =
            $evidencia->getRealPath();

        if (!$rutaTemporal) {
            throw new InvalidArgumentException(
                'No se pudo leer la evidencia.'
            );
        }

        $hash = hash_file(
            'sha256',
            $rutaTemporal
        );

        if (!$hash) {
            throw new InvalidArgumentException(
                'No se pudo calcular la integridad de la evidencia.'
            );
        }

        $directorio =
            'reservas/' .
            $reserva->id .
            '/solicitudes-cancelacion';

        $nombreGuardado =
            Str::uuid()->toString() .
            '.' .
            $extension;

        $path = $evidencia->storeAs(
            $directorio,
            $nombreGuardado,
            self::DISCO
        );

        if (!$path) {
            throw new InvalidArgumentException(
                'No se pudo guardar la evidencia.'
            );
        }

        return [
            'path' =>
                $path,

            'nombre_original' =>
                $evidencia
                    ->getClientOriginalName(),

            'mime' =>
                $mime,

            'tamano' =>
                (int)
                    $evidencia
                        ->getSize(),

            'hash' =>
                $hash,
        ];
    }

    /**
     * Valida la evidencia adjunta.
     */
    private function validarArchivoEvidencia(
        UploadedFile $evidencia
    ): void {
        if (!$evidencia->isValid()) {
            throw new InvalidArgumentException(
                'La evidencia no se recibió correctamente.'
            );
        }

        $mime = (string)
            $evidencia->getMimeType();

        if (
            !array_key_exists(
                $mime,
                self::TIPOS_ARCHIVO_PERMITIDOS
            )
        ) {
            throw new InvalidArgumentException(
                'La evidencia debe ser PDF, JPG, PNG o WEBP.'
            );
        }

        $tamano = (int)
            $evidencia->getSize();

        if ($tamano <= 0) {
            throw new InvalidArgumentException(
                'La evidencia está vacía.'
            );
        }

        if (
            $tamano >
            self::TAMANO_MAXIMO
        ) {
            throw new InvalidArgumentException(
                'La evidencia no puede superar los 10 MB.'
            );
        }
    }

    /**
     * Obtiene y valida un administrador.
     *
     * Se permite que el administrador que
     * creó la solicitud también la apruebe.
     */
    private function obtenerAdministrador(
        int $usuarioId
    ): User {
        $usuario = User::query()
            ->findOrFail(
                $usuarioId
            );

        if (
            !$usuario->estaActivo() ||
            !$usuario->isAdmin()
        ) {
            throw new InvalidArgumentException(
                'Solamente un administrador activo puede revisar solicitudes de cancelación.'
            );
        }

        return $usuario;
    }

    /**
     * Restaura el estado de cobranza cuando
     * la solicitud es rechazada o anulada.
     */
    private function restaurarCobranza(
        Reserva $reserva,
        SolicitudCancelacion $solicitud
    ): void {
        if ($reserva->estaCancelada()) {
            return;
        }

        $estadoAnterior =
            $solicitud
                ->estado_cobranza_anterior;

        if (!$estadoAnterior) {
            $estadoAnterior =
                match (
                    $reserva->estado_pago
                ) {
                    Reserva::PAGO_COMPLETO =>
                        Reserva::
                            COBRANZA_PAGADA,

                    Reserva::PAGO_PENDIENTE =>
                        Reserva::
                            COBRANZA_PENDIENTE_ANTICIPO,

                    default =>
                        Reserva::
                            COBRANZA_AL_DIA,
                };
        }

        $reserva->forceFill([
            'estado_cobranza' =>
                $estadoAnterior,
        ])->save();
    }

    /**
     * Convierte el tipo utilizado por la
     * solicitud al formato del servicio
     * actual de cancelación.
     */
    private function convertirTipoCancelacion(
        string $tipo
    ): string {
        return match ($tipo) {
            SolicitudCancelacion::
                TIPO_DECISION_CLIENTE =>
                    'cliente',

            SolicitudCancelacion::
                TIPO_FUERZA_MAYOR =>
                    'fuerza_mayor',

            SolicitudCancelacion::
                TIPO_RESPONSABILIDAD_AGENCIA =>
                    'agencia',

            SolicitudCancelacion::
                TIPO_PROBLEMA_PROVEEDOR =>
                    'proveedor',

            default =>
                'otro',
        };
    }

    /**
     * Construye un resumen de todos los
     * gastos documentados y aprobados.
     */
    private function construirDetalleGastos(
        $gastosAprobados
    ): string {
        if (
            $gastosAprobados->isEmpty()
        ) {
            return '';
        }

        return $gastosAprobados
            ->map(
                function (
                    GastoCancelacion $gasto
                ): string {
                    $documento =
                        $gasto
                            ->numero_documento
                            ? ' Documento: ' .
                                $gasto
                                    ->numero_documento .
                                '.'
                            : '';

                    return
                        $gasto->proveedor .
                        ': ' .
                        $gasto->concepto .
                        ' por ' .
                        number_format(
                            (float)
                                $gasto->monto,
                            2,
                            '.',
                            ''
                        ) .
                        '.' .
                        $documento;
                }
            )
            ->implode(' ');
    }

    /**
     * Construye la referencia que se guarda
     * en la reserva cancelada.
     */
    private function construirReferenciaEvidencia(
        SolicitudCancelacion $solicitud
    ): string {
        if (
            !$solicitud->tieneEvidencia()
        ) {
            return '';
        }

        return
            'Solicitud #' .
            $solicitud->id .
            '. Evidencia privada: ' .
            $solicitud
                ->evidencia_nombre_original .
            '. SHA-256: ' .
            $solicitud
                ->evidencia_hash .
            '.';
    }
}