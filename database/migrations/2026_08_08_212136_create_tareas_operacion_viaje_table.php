<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'tareas_operacion_viaje',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId(
                    'operacion_viaje_id'
                )
                    ->constrained(
                        'operaciones_viaje'
                    )
                    ->cascadeOnDelete();

                /*
                 * Corresponde al UUID estable guardado
                 * dentro del itinerario del paquete.
                 */
                $table->uuid(
                    'actividad_uuid'
                );

                $table->unsignedSmallInteger(
                    'dia'
                );

                $table->string(
                    'nombre',
                    150
                );

                $table->text(
                    'descripcion'
                )->nullable();

                $table->time(
                    'hora_inicio'
                )->nullable();

                $table->time(
                    'hora_fin'
                )->nullable();

                $table->string(
                    'ubicacion',
                    180
                )->nullable();

                $table->string(
                    'tipo_gestion',
                    30
                );

                $table->string(
                    'estado',
                    20
                )
                    ->default('pendiente')
                    ->index();

                /*
                 * Permite conservar el historial cuando una
                 * actividad es retirada del paquete.
                 *
                 * Las tareas no vigentes no afectan el progreso.
                 */
                $table->boolean(
                    'vigente'
                )
                    ->default(true)
                    ->index();

                $table->text(
                    'observaciones'
                )->nullable();

                $table->timestamp(
                    'completada_at'
                )->nullable();

                $table->foreignId(
                    'completada_por_user_id'
                )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();

                $table->unique(
                    [
                        'operacion_viaje_id',
                        'actividad_uuid',
                    ],
                    'tareas_operacion_actividad_unique'
                );

                $table->index(
                    [
                        'operacion_viaje_id',
                        'vigente',
                        'estado',
                    ],
                    'tareas_operacion_progreso_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'tareas_operacion_viaje'
        );
    }
};