<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la gestión genérica para trenes, traslados,
     * entradas, alimentación, seguros y otros servicios.
     */
    public function up(): void
    {
        Schema::create(
            'gestiones_operativas',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId(
                    'operacion_viaje_id'
                )
                    ->constrained(
                        'operaciones_viaje'
                    )
                    ->cascadeOnDelete();

                $table->string(
                    'tipo',
                    30
                )->index();

                $table->string(
                    'nombre',
                    180
                );

                $table->string(
                    'proveedor',
                    150
                )->nullable();

                $table->string(
                    'contacto',
                    150
                )->nullable();

                $table->string(
                    'telefono',
                    30
                )->nullable();

                $table->string(
                    'correo',
                    150
                )->nullable();

                $table->dateTime(
                    'fecha_hora_inicio'
                )->nullable();

                $table->dateTime(
                    'fecha_hora_fin'
                )->nullable();

                $table->string(
                    'ubicacion_origen',
                    180
                )->nullable();

                $table->string(
                    'destino',
                    180
                )->nullable();

                $table->unsignedInteger(
                    'cantidad_viajeros'
                )->default(0);

                $table->unsignedInteger(
                    'capacidad'
                )->nullable();

                $table->string(
                    'referencia_confirmacion',
                    150
                )->nullable();

                $table->decimal(
                    'costo_total',
                    12,
                    2
                )->nullable();

                $table->char(
                    'moneda',
                    3
                )->default('USD');

                $table->string(
                    'estado',
                    20
                )
                    ->default('pendiente')
                    ->index();

                $table->string(
                    'archivo_comprobante'
                )->nullable();

                $table->text(
                    'observaciones'
                )->nullable();

                /*
                 * Guarda información particular dependiendo
                 * del tipo de servicio:
                 *
                 * tren:
                 * empresa, clase y ruta.
                 *
                 * traslado:
                 * vehículo, conductor y capacidad.
                 *
                 * alimentación:
                 * menú y restricciones.
                 *
                 * seguro:
                 * cobertura y número de póliza.
                 */
                $table->json(
                    'datos_adicionales'
                )->nullable();

                $table->foreignId(
                    'creado_por_user_id'
                )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->foreignId(
                    'actualizado_por_user_id'
                )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();

                $table->index(
                    [
                        'operacion_viaje_id',
                        'tipo',
                        'estado',
                    ],
                    'gestiones_operacion_tipo_estado_idx'
                );
            }
        );

        /*
         * Registra la situación individual de cada viajero.
         *
         * Permite que una misma gestión sea grupal, pero que
         * cada persona conserve su propio boleto, asiento,
         * referencia, restricción o documento.
         */
        Schema::create(
            'gestion_operativa_viajeros',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId(
                    'gestion_operativa_id'
                )
                    ->constrained(
                        'gestiones_operativas'
                    )
                    ->cascadeOnDelete();

                $table->foreignId(
                    'viajero_reserva_id'
                )
                    ->constrained(
                        'viajeros_reserva'
                    )
                    ->restrictOnDelete();

                /*
                 * Puede representar un boleto de tren,
                 * entrada, póliza, cupón o comprobante.
                 */
                $table->string(
                    'numero_documento',
                    150
                )->nullable();

                $table->string(
                    'asiento',
                    30
                )->nullable();

                $table->string(
                    'referencia_individual',
                    150
                )->nullable();

                $table->string(
                    'estado',
                    20
                )
                    ->default('pendiente')
                    ->index();

                $table->text(
                    'restricciones'
                )->nullable();

                $table->text(
                    'observaciones'
                )->nullable();

                $table->timestamps();

                /*
                 * Un viajero solo puede aparecer una vez dentro
                 * de la misma gestión operativa.
                 */
                $table->unique(
                    [
                        'gestion_operativa_id',
                        'viajero_reserva_id',
                    ],
                    'gestion_operativa_viajero_unique'
                );
            }
        );
    }

    /**
     * Revierte ambas tablas en el orden correcto.
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'gestion_operativa_viajeros'
        );

        Schema::dropIfExists(
            'gestiones_operativas'
        );
    }
};