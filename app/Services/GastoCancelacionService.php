<?php

namespace App\Services;

use App\Models\GastoCancelacion;
use App\Models\Reserva;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

class GastoCancelacionService
{
    /*
     * Los comprobantes se guardan en:
     *
     * storage/app/private
     */
    private const DISCO =
        'local';

    /*
     * Tamaño máximo permitido:
     * 10 megabytes.
     */
    private const TAMANO_MAXIMO =
        10 * 1024 * 1024;

    /*
     * Tipos de archivos aceptados.
     */
    private const TIPOS_PERMITIDOS = [
        'application/pdf' =>
            'pdf',

        'image/jpeg' =>
            'jpg',

        'image/png' =>
            'png',

        'image/webp' =>
            'webp',
    ];

    /**
     * Registra un gasto y su comprobante.
     *
     * Se permite registrar cuando:
     *
     * - La reserva ya está cancelada.
     * - La reserva tiene una solicitud de
     *   cancelación pendiente.
     */
    public function registrar(
        Reserva $reserva,
        array $datos,
        UploadedFile $archivo,
        int $usuarioId
    ): GastoCancelacion {
        $reserva->refresh();

        if (
            !$this->puedeDocumentarGastos(
                $reserva
            )
        ) {
            throw new InvalidArgumentException(
                'Los gastos solamente pueden registrarse cuando la reserva está cancelada o tiene una solicitud de cancelación pendiente.'
            );
        }

        $this->validarArchivo(
            $archivo
        );

        $monto = round(
            (float) (
                $datos['monto'] ?? 0
            ),
            2
        );

        if ($monto <= 0) {
            throw new InvalidArgumentException(
                'El monto del gasto debe ser mayor que cero.'
            );
        }

        $proveedor = trim(
            (string) (
                $datos['proveedor'] ?? ''
            )
        );

        if (
            mb_strlen($proveedor) < 2
        ) {
            throw new InvalidArgumentException(
                'Debes registrar el proveedor relacionado con el gasto.'
            );
        }

        $concepto = trim(
            (string) (
                $datos['concepto'] ?? ''
            )
        );

        if (
            mb_strlen($concepto) < 3
        ) {
            throw new InvalidArgumentException(
                'Debes registrar el concepto del gasto.'
            );
        }

        $mime = (string)
            $archivo->getMimeType();

        $tamano = (int)
            $archivo->getSize();

        $rutaTemporal =
            $archivo->getRealPath();

        if (!$rutaTemporal) {
            throw new InvalidArgumentException(
                'No se pudo leer el comprobante.'
            );
        }

        $hash = hash_file(
            'sha256',
            $rutaTemporal
        );

        if (!$hash) {
            throw new InvalidArgumentException(
                'No se pudo calcular la integridad del comprobante.'
            );
        }

        $extension =
            self::TIPOS_PERMITIDOS[
                $mime
            ];

        $directorio =
            'reservas/' .
            $reserva->id .
            '/gastos-cancelacion';

        $nombreArchivo =
            Str::uuid()->toString() .
            '.' .
            $extension;

        $rutaGuardada =
            $archivo->storeAs(
                $directorio,
                $nombreArchivo,
                self::DISCO
            );

        if (!$rutaGuardada) {
            throw new InvalidArgumentException(
                'No se pudo guardar el comprobante.'
            );
        }

        try {
            return DB::transaction(
                function () use (
                    $reserva,
                    $datos,
                    $archivo,
                    $usuarioId,
                    $monto,
                    $proveedor,
                    $concepto,
                    $mime,
                    $tamano,
                    $hash,
                    $rutaGuardada
                ) {
                    /*
                     * Bloqueamos la reserva para impedir
                     * que sea modificada simultáneamente.
                     */
                    $reservaBloqueada =
                        Reserva::query()
                            ->lockForUpdate()
                            ->findOrFail(
                                $reserva->id
                            );

                    if (
                        !$this
                            ->puedeDocumentarGastos(
                                $reservaBloqueada
                            )
                    ) {
                        throw new InvalidArgumentException(
                            'La reserva ya no se encuentra disponible para registrar gastos.'
                        );
                    }

                    /*
                     * El total de gastos registrados puede
                     * superar temporalmente lo pagado mientras
                     * están pendientes.
                     *
                     * El límite definitivo se aplica cuando
                     * el administrador aprueba cada gasto.
                     */
                    return GastoCancelacion::create([
                        'reserva_id' =>
                            $reservaBloqueada->id,

                        'registrado_por_user_id' =>
                            $usuarioId,

                        'proveedor' =>
                            $proveedor,

                        'concepto' =>
                            $concepto,

                        'monto' =>
                            $monto,

                        'numero_documento' =>
                            !empty(
                                $datos[
                                    'numero_documento'
                                ]
                            )
                                ? trim(
                                    (string) $datos[
                                        'numero_documento'
                                    ]
                                )
                                : null,

                        'fecha_documento' =>
                            $datos[
                                'fecha_documento'
                            ] ?? null,

                        'archivo_path' =>
                            $rutaGuardada,

                        'archivo_nombre_original' =>
                            $archivo
                                ->getClientOriginalName(),

                        'archivo_mime' =>
                            $mime,

                        'archivo_tamano' =>
                            $tamano,

                        'archivo_hash' =>
                            $hash,

                        'estado' =>
                            GastoCancelacion::
                                ESTADO_PENDIENTE,

                        'observaciones' =>
                            !empty(
                                $datos[
                                    'observaciones'
                                ]
                            )
                                ? trim(
                                    (string) $datos[
                                        'observaciones'
                                    ]
                                )
                                : null,
                    ]);
                }
            );
        } catch (Throwable $error) {
            /*
             * Si falla la transacción, eliminamos
             * el archivo para no dejar documentos
             * huérfanos.
             */
            Storage::disk(
                self::DISCO
            )->delete(
                $rutaGuardada
            );

            throw $error;
        }
    }

    /**
     * Aprueba un gasto documentado.
     */
    public function aprobar(
        GastoCancelacion $gasto,
        int $usuarioId,
        ?string $observacion = null
    ): GastoCancelacion {
        return DB::transaction(
            function () use (
                $gasto,
                $usuarioId,
                $observacion
            ) {
                $gasto =
                    GastoCancelacion::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $gasto->id
                        );

                if (
                    !$gasto->estaPendiente()
                ) {
                    throw new InvalidArgumentException(
                        'Solamente se pueden aprobar gastos pendientes.'
                    );
                }

                $reserva =
                    Reserva::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $gasto->reserva_id
                        );

                if (
                    !$this
                        ->puedeDocumentarGastos(
                            $reserva
                        )
                ) {
                    throw new InvalidArgumentException(
                        'La reserva debe estar cancelada o tener una solicitud de cancelación pendiente.'
                    );
                }

                /*
                 * Para reservas ya canceladas usamos
                 * la fotografía financiera guardada al
                 * cancelar.
                 *
                 * Para solicitudes pendientes usamos
                 * todos los pagos registrados.
                 */
                $basePagada =
                    $this->obtenerBasePagada(
                        $reserva
                    );

                $otrosGastosAprobados =
                    round(
                        (float)
                            GastoCancelacion::query()
                                ->aprobados()
                                ->where(
                                    'reserva_id',
                                    $reserva->id
                                )
                                ->where(
                                    'id',
                                    '!=',
                                    $gasto->id
                                )
                                ->sum('monto'),
                        2
                    );

                $totalConEsteGasto =
                    round(
                        $otrosGastosAprobados +
                        (float) $gasto->monto,
                        2
                    );

                /*
                 * Los gastos aprobados nunca pueden
                 * superar el dinero pagado.
                 */
                if (
                    $totalConEsteGasto >
                    $basePagada
                ) {
                    throw new InvalidArgumentException(
                        'Los gastos aprobados no pueden superar el monto pagado por el cliente.'
                    );
                }

                $yaDevuelto = round(
                    (float)
                        $reserva
                            ->devoluciones()
                            ->sum('monto'),
                    2
                );

                $nuevoReembolsable =
                    round(
                        max(
                            0,
                            $basePagada -
                            $totalConEsteGasto
                        ),
                        2
                    );

                /*
                 * Una aprobación no puede reducir el
                 * reembolso por debajo de lo que ya fue
                 * devuelto.
                 */
                if (
                    $yaDevuelto >
                    $nuevoReembolsable
                ) {
                    throw new InvalidArgumentException(
                        'No se puede aprobar el gasto porque ya se procesaron devoluciones superiores al nuevo monto reembolsable.'
                    );
                }

                $gasto->update([
                    'estado' =>
                        GastoCancelacion::
                            ESTADO_APROBADO,

                    'revisado_por_user_id' =>
                        $usuarioId,

                    'motivo_revision' =>
                        $observacion
                            ? trim(
                                $observacion
                            )
                            : 'Comprobante revisado y aprobado.',

                    'revisado_at' =>
                        now(),
                ]);

                /*
                 * Si la reserva ya está cancelada,
                 * actualizamos su liquidación.
                 *
                 * Si aún está en revisión, el cálculo
                 * definitivo ocurrirá cuando se apruebe
                 * la cancelación.
                 */
                $this->recalcularLiquidacion(
                    $reserva
                );

                return $gasto->fresh([
                    'reserva',
                    'registradoPor',
                    'revisadoPor',
                ]);
            }
        );
    }

    /**
     * Rechaza un gasto pendiente.
     */
    public function rechazar(
        GastoCancelacion $gasto,
        string $motivo,
        int $usuarioId
    ): GastoCancelacion {
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
                $gasto,
                $motivo,
                $usuarioId
            ) {
                $gasto =
                    GastoCancelacion::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $gasto->id
                        );

                if (
                    !$gasto->estaPendiente()
                ) {
                    throw new InvalidArgumentException(
                        'Solamente se pueden rechazar gastos pendientes.'
                    );
                }

                $gasto->update([
                    'estado' =>
                        GastoCancelacion::
                            ESTADO_RECHAZADO,

                    'revisado_por_user_id' =>
                        $usuarioId,

                    'motivo_revision' =>
                        $motivo,

                    'revisado_at' =>
                        now(),
                ]);

                return $gasto->fresh([
                    'reserva',
                    'registradoPor',
                    'revisadoPor',
                ]);
            }
        );
    }

    /**
     * Anula un gasto.
     */
    public function anular(
        GastoCancelacion $gasto,
        string $motivo,
        int $usuarioId
    ): GastoCancelacion {
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
                $gasto,
                $motivo,
                $usuarioId
            ) {
                $gasto =
                    GastoCancelacion::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $gasto->id
                        );

                if (
                    $gasto->estaAnulado()
                ) {
                    throw new InvalidArgumentException(
                        'El gasto ya está anulado.'
                    );
                }

                $estabaAprobado =
                    $gasto->estaAprobado();

                $gasto->update([
                    'estado' =>
                        GastoCancelacion::
                            ESTADO_ANULADO,

                    'revisado_por_user_id' =>
                        $usuarioId,

                    'motivo_revision' =>
                        $motivo,

                    'revisado_at' =>
                        now(),
                ]);

                if ($estabaAprobado) {
                    $reserva =
                        Reserva::query()
                            ->lockForUpdate()
                            ->findOrFail(
                                $gasto->reserva_id
                            );

                    $this->recalcularLiquidacion(
                        $reserva
                    );
                }

                return $gasto->fresh([
                    'reserva',
                    'registradoPor',
                    'revisadoPor',
                ]);
            }
        );
    }

    /**
     * Verifica que el archivo exista y que
     * conserve el mismo hash.
     */
    public function verificarIntegridad(
        GastoCancelacion $gasto
    ): bool {
        $disco = Storage::disk(
            self::DISCO
        );

        if (
            !$disco->exists(
                $gasto->archivo_path
            )
        ) {
            return false;
        }

        $rutaAbsoluta =
            $disco->path(
                $gasto->archivo_path
            );

        $hashActual = hash_file(
            'sha256',
            $rutaAbsoluta
        );

        return
            is_string($hashActual) &&
            hash_equals(
                (string)
                    $gasto->archivo_hash,
                $hashActual
            );
    }

    /**
     * Obtiene la ruta privada del comprobante.
     */
    public function obtenerRutaAbsoluta(
        GastoCancelacion $gasto
    ): string {
        if (
            !$this->verificarIntegridad(
                $gasto
            )
        ) {
            throw new InvalidArgumentException(
                'El comprobante no existe o su integridad no es válida.'
            );
        }

        return Storage::disk(
            self::DISCO
        )->path(
            $gasto->archivo_path
        );
    }

    /**
     * Determina si una reserva puede recibir
     * documentos de gastos.
     */
    private function puedeDocumentarGastos(
        Reserva $reserva
    ): bool {
        if (
            $reserva->estaCancelada()
        ) {
            return true;
        }

        return $reserva
            ->tieneSolicitudCancelacionPendiente();
    }

    /**
     * Obtiene el dinero pagado antes de aplicar
     * devoluciones.
     *
     * Para registros antiguos con una fotografía
     * financiera incorrecta, utiliza el mayor valor
     * entre el campo guardado y los pagos registrados.
     */
    private function obtenerBasePagada(
        Reserva $reserva
    ): float {
        $pagadoBruto = round(
            (float)
                $reserva
                    ->pagos()
                    ->sum(
                        'monto_depositado'
                    ),
            2
        );

        if (
            !$reserva->estaCancelada()
        ) {
            return $pagadoBruto;
        }

        $pagadoAlCancelar = round(
            (float) (
                $reserva
                    ->monto_pagado_al_cancelar ??
                0
            ),
            2
        );

        /*
         * En una reserva cancelada correctamente,
         * ambos valores deberían coincidir.
         *
         * max() permite recuperar casos antiguos
         * que guardaron cero por error.
         */
        return max(
            $pagadoAlCancelar,
            $pagadoBruto
        );
    }

    /**
     * Recalcula la liquidación de una reserva
     * que ya se encuentra cancelada.
     */
    private function recalcularLiquidacion(
        Reserva $reserva
    ): void {
        $reserva->refresh();

        /*
         * Durante una solicitud pendiente no
         * modificamos todavía la liquidación.
         */
        if (
            !$reserva->estaCancelada()
        ) {
            return;
        }

        $basePagada =
            $this->obtenerBasePagada(
                $reserva
            );

        $totalGastos = round(
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

        /*
         * Protección adicional para datos
         * antiguos o manipulados.
         */
        if (
            $totalGastos >
            $basePagada
        ) {
            throw new InvalidArgumentException(
                'Los gastos aprobados superan el monto pagado por el cliente.'
            );
        }

        $reembolsable = round(
            max(
                0,
                $basePagada -
                $totalGastos
            ),
            2
        );

        $devuelto = round(
            (float)
                $reserva
                    ->devoluciones()
                    ->sum('monto'),
            2
        );

        if (
            $devuelto >
            $reembolsable
        ) {
            throw new InvalidArgumentException(
                'Las devoluciones procesadas superan el monto reembolsable documentado.'
            );
        }

        $estadoReembolso =
            match (true) {
                $basePagada <= 0 =>
                    Reserva::
                        REEMBOLSO_NO_APLICA,

                $reembolsable <= 0 =>
                    Reserva::
                        REEMBOLSO_SIN_REEMBOLSO,

                $devuelto >=
                    $reembolsable =>
                    Reserva::
                        REEMBOLSO_COMPLETADO,

                $devuelto > 0 =>
                    Reserva::
                        REEMBOLSO_PARCIAL,

                default =>
                    Reserva::
                        REEMBOLSO_PENDIENTE,
            };

        $cantidadAprobada =
            GastoCancelacion::query()
                ->aprobados()
                ->where(
                    'reserva_id',
                    $reserva->id
                )
                ->count();

        $reserva->forceFill([
            /*
             * Corrige también fotografías
             * antiguas que quedaron en cero.
             */
            'monto_pagado_al_cancelar' =>
                $basePagada,

            'gastos_no_reembolsables' =>
                $totalGastos,

            'monto_reembolsable' =>
                $reembolsable,

            'estado_reembolso' =>
                $estadoReembolso,

            'detalle_gastos_no_reembolsables' =>
                $cantidadAprobada > 0
                    ? 'Calculado a partir de ' .
                        $cantidadAprobada .
                        ' gasto(s) documentado(s) y aprobado(s).'
                    : null,
        ])->save();
    }

    /**
     * Valida formato, contenido y tamaño
     * del comprobante.
     */
    private function validarArchivo(
        UploadedFile $archivo
    ): void {
        if (
            !$archivo->isValid()
        ) {
            throw new InvalidArgumentException(
                'El comprobante no se recibió correctamente.'
            );
        }

        $mime = (string)
            $archivo->getMimeType();

        if (
            !array_key_exists(
                $mime,
                self::TIPOS_PERMITIDOS
            )
        ) {
            throw new InvalidArgumentException(
                'El comprobante debe ser PDF, JPG, PNG o WEBP.'
            );
        }

        $tamano = (int)
            $archivo->getSize();

        if ($tamano <= 0) {
            throw new InvalidArgumentException(
                'El comprobante está vacío.'
            );
        }

        if (
            $tamano >
            self::TAMANO_MAXIMO
        ) {
            throw new InvalidArgumentException(
                'El comprobante no puede superar los 10 MB.'
            );
        }
    }
}