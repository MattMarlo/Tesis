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
    private const DISCO = 'local';

    private const TIPOS_PERMITIDOS = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public function registrar(
        Reserva $reserva,
        array $datos,
        UploadedFile $archivo,
        int $usuarioId
    ): GastoCancelacion {
        $reserva->refresh();

        if (!$reserva->estaCancelada()) {
            throw new InvalidArgumentException(
                'Los gastos documentados solamente pueden registrarse después de cancelar la reserva.'
            );
        }

        if (!$archivo->isValid()) {
            throw new InvalidArgumentException(
                'El comprobante no se recibió correctamente.'
            );
        }

        $monto = round(
            (float) ($datos['monto'] ?? 0),
            2
        );

        if ($monto <= 0) {
            throw new InvalidArgumentException(
                'El monto del gasto debe ser mayor que cero.'
            );
        }

        $mime = (string) $archivo->getMimeType();

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

        $tamano = (int) $archivo->getSize();

        if ($tamano <= 0) {
            throw new InvalidArgumentException(
                'El comprobante está vacío.'
            );
        }

        /*
         * Límite defensivo de 10 MB.
         */
        if ($tamano > 10 * 1024 * 1024) {
            throw new InvalidArgumentException(
                'El comprobante no puede superar los 10 MB.'
            );
        }

        $rutaTemporal = $archivo->getRealPath();

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
            self::TIPOS_PERMITIDOS[$mime];

        $directorio =
            'reservas/' .
            $reserva->id .
            '/gastos-cancelacion';

        $nombreArchivo =
            Str::uuid()->toString() .
            '.' .
            $extension;

        /*
         * Se utiliza el disco local, cuya raíz en Laravel
         * está en storage/app/private.
         */
        $rutaGuardada = $archivo->storeAs(
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
                    $mime,
                    $tamano,
                    $hash,
                    $rutaGuardada
                ) {
                    $reservaBloqueada = Reserva::query()
                        ->lockForUpdate()
                        ->findOrFail($reserva->id);

                    if (
                        !$reservaBloqueada
                            ->estaCancelada()
                    ) {
                        throw new InvalidArgumentException(
                            'La reserva debe estar cancelada para registrar gastos.'
                        );
                    }

                    return GastoCancelacion::create([
                        'reserva_id' =>
                            $reservaBloqueada->id,

                        'registrado_por_user_id' =>
                            $usuarioId,

                        'proveedor' =>
                            trim(
                                (string) $datos['proveedor']
                            ),

                        'concepto' =>
                            trim(
                                (string) $datos['concepto']
                            ),

                        'monto' =>
                            $monto,

                        'numero_documento' =>
                            !empty(
                                $datos['numero_documento']
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
                                $datos['observaciones']
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
             * Si falla la base de datos, eliminamos el
             * archivo para evitar documentos huérfanos.
             */
            Storage::disk(
                self::DISCO
            )->delete($rutaGuardada);

            throw $error;
        }
    }

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
                $gasto = GastoCancelacion::query()
                    ->lockForUpdate()
                    ->findOrFail($gasto->id);

                if (!$gasto->estaPendiente()) {
                    throw new InvalidArgumentException(
                        'Solamente se pueden aprobar gastos pendientes.'
                    );
                }

                $reserva = Reserva::query()
                    ->lockForUpdate()
                    ->findOrFail($gasto->reserva_id);

                if (!$reserva->estaCancelada()) {
                    throw new InvalidArgumentException(
                        'La reserva debe estar cancelada.'
                    );
                }

                $basePagada = round(
                    (float) (
                        $reserva
                            ->monto_pagado_al_cancelar ??
                        $reserva->total_pagado
                    ),
                    2
                );

                $otrosGastosAprobados = round(
                    (float) GastoCancelacion::query()
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

                $totalConEsteGasto = round(
                    $otrosGastosAprobados +
                    (float) $gasto->monto,
                    2
                );

                if (
                    $totalConEsteGasto >
                    $basePagada
                ) {
                    throw new InvalidArgumentException(
                        'Los gastos aprobados no pueden superar el monto pagado por el cliente.'
                    );
                }

                $yaDevuelto = round(
                    (float) $reserva
                        ->devoluciones()
                        ->sum('monto'),
                    2
                );

                $nuevoReembolsable = round(
                    max(
                        0,
                        $basePagada -
                        $totalConEsteGasto
                    ),
                    2
                );

                /*
                 * No se puede aprobar un gasto si ya se
                 * devolvió más dinero del que quedaría autorizado.
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
                            ? trim($observacion)
                            : 'Comprobante revisado y aprobado.',

                    'revisado_at' =>
                        now(),
                ]);

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

    public function rechazar(
        GastoCancelacion $gasto,
        string $motivo,
        int $usuarioId
    ): GastoCancelacion {
        $motivo = trim($motivo);

        if (mb_strlen($motivo) < 10) {
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
                $gasto = GastoCancelacion::query()
                    ->lockForUpdate()
                    ->findOrFail($gasto->id);

                if (!$gasto->estaPendiente()) {
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

    public function anular(
        GastoCancelacion $gasto,
        string $motivo,
        int $usuarioId
    ): GastoCancelacion {
        $motivo = trim($motivo);

        if (mb_strlen($motivo) < 10) {
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
                $gasto = GastoCancelacion::query()
                    ->lockForUpdate()
                    ->findOrFail($gasto->id);

                if ($gasto->estaAnulado()) {
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
                    $reserva = Reserva::query()
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

        $rutaAbsoluta = $disco->path(
            $gasto->archivo_path
        );

        $hashActual = hash_file(
            'sha256',
            $rutaAbsoluta
        );

        return is_string($hashActual) &&
            hash_equals(
                $gasto->archivo_hash,
                $hashActual
            );
    }

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

    private function recalcularLiquidacion(
        Reserva $reserva
    ): void {
        $reserva->refresh();

        if (!$reserva->estaCancelada()) {
            return;
        }

        $basePagada = round(
            (float) (
                $reserva->monto_pagado_al_cancelar ??
                $reserva->total_pagado
            ),
            2
        );

        $totalGastos = round(
            (float) GastoCancelacion::query()
                ->aprobados()
                ->where(
                    'reserva_id',
                    $reserva->id
                )
                ->sum('monto'),
            2
        );

        $reembolsable = round(
            max(
                0,
                $basePagada -
                $totalGastos
            ),
            2
        );

        $devuelto = round(
            (float) $reserva
                ->devoluciones()
                ->sum('monto'),
            2
        );

        $estadoReembolso = match (true) {
            $basePagada <= 0 =>
                Reserva::REEMBOLSO_NO_APLICA,

            $reembolsable <= 0 =>
                Reserva::REEMBOLSO_SIN_REEMBOLSO,

            $devuelto >= $reembolsable =>
                Reserva::REEMBOLSO_COMPLETADO,

            $devuelto > 0 =>
                Reserva::REEMBOLSO_PARCIAL,

            default =>
                Reserva::REEMBOLSO_PENDIENTE,
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
}