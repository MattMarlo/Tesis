<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración.
     */
    public function up(): void
    {
        Schema::create(
            'solicitudes_cancelacion',
            function (Blueprint $table) {
                $table->id();

                /*
                 * Reserva para la que se solicita
                 * la cancelación.
                 */
                $table->foreignId('reserva_id')
                    ->constrained('reservas')
                    ->restrictOnDelete();

                /*
                 * Usuario del sistema que registra
                 * la solicitud.
                 */
                $table->foreignId(
                    'solicitado_por_user_id'
                )
                    ->constrained('users')
                    ->restrictOnDelete();

                /*
                 * Administrador que aprueba o
                 * rechaza la solicitud.
                 */
                $table->foreignId(
                    'revisado_por_user_id'
                )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                /*
                 * Persona o entidad que originó
                 * la solicitud:
                 *
                 * cliente
                 * agencia
                 * proveedor
                 * sistema
                 */
                $table->string(
                    'solicitante',
                    30
                )->default('cliente');

                /*
                 * Tipo de cancelación:
                 *
                 * decision_cliente
                 * fuerza_mayor
                 * responsabilidad_agencia
                 * problema_proveedor
                 * cambio_viaje
                 * otro
                 */
                $table->string(
                    'tipo_cancelacion',
                    40
                );

                /*
                 * Explicación completa de la
                 * solicitud.
                 */
                $table->text(
                    'motivo'
                );

                /*
                 * Medio por el cual fue recibida:
                 *
                 * presencial
                 * llamada
                 * whatsapp
                 * correo
                 * otro
                 */
                $table->string(
                    'canal_solicitud',
                    30
                );

                /*
                 * Referencia de correo,
                 * conversación o documento.
                 */
                $table->string(
                    'referencia_comunicacion',
                    255
                )->nullable();

                /*
                 * Evidencia privada asociada
                 * con el motivo de cancelación.
                 */
                $table->string(
                    'evidencia_path',
                    500
                )->nullable();

                $table->string(
                    'evidencia_nombre_original',
                    255
                )->nullable();

                $table->string(
                    'evidencia_mime',
                    100
                )->nullable();

                $table->unsignedBigInteger(
                    'evidencia_tamano'
                )->nullable();

                /*
                 * SHA-256 utilizado para verificar
                 * la integridad del archivo.
                 */
                $table->char(
                    'evidencia_hash',
                    64
                )->nullable();

                /*
                 * Fotografía financiera del momento
                 * en que se crea la solicitud.
                 */
                $table->decimal(
                    'monto_pagado_solicitud',
                    12,
                    2
                )->default(0);

                $table->string(
                    'moneda',
                    3
                )->default('USD');

                /*
                 * Permite restaurar la cobranza
                 * cuando la solicitud es rechazada.
                 */
                $table->string(
                    'estado_cobranza_anterior',
                    30
                )->nullable();

                /*
                 * Estados permitidos:
                 *
                 * pendiente
                 * aprobada
                 * rechazada
                 * anulada
                 */
                $table->string(
                    'estado',
                    30
                )->default('pendiente');

                /*
                 * Información interna opcional
                 * registrada por el agente.
                 */
                $table->text(
                    'observaciones_internas'
                )->nullable();

                /*
                 * Explicación del administrador
                 * al aprobar o rechazar.
                 */
                $table->text(
                    'motivo_revision'
                )->nullable();

                /*
                 * Fechas del proceso.
                 */
                $table->timestamp(
                    'solicitado_at'
                )->useCurrent();

                $table->timestamp(
                    'revisado_at'
                )->nullable();

                /*
                 * Usuario que anula una solicitud
                 * antes de que sea revisada.
                 */
                $table->foreignId(
                    'anulado_por_user_id'
                )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamp(
                    'anulado_at'
                )->nullable();

                $table->timestamps();

                /*
                 * Índices usados en listados,
                 * búsquedas y validación de
                 * solicitudes pendientes.
                 */
                $table->index(
                    [
                        'reserva_id',
                        'estado',
                    ],
                    'sol_cancel_reserva_estado_idx'
                );

                $table->index(
                    [
                        'estado',
                        'solicitado_at',
                    ],
                    'sol_cancel_estado_fecha_idx'
                );

                $table->index(
                    'tipo_cancelacion',
                    'sol_cancel_tipo_idx'
                );
            }
        );
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'solicitudes_cancelacion'
        );
    }
};