<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega una relación polimórfica para vincular una tarea
     * con un vuelo, alojamiento, guía o gestión genérica.
     */
    public function up(): void
    {
        Schema::table(
            'tareas_operacion_viaje',
            function (Blueprint $table) {
                $table->string(
                    'gestionable_type'
                )
                    ->nullable()
                    ->after('tipo_gestion');

                $table->unsignedBigInteger(
                    'gestionable_id'
                )
                    ->nullable()
                    ->after('gestionable_type');

                $table->index(
                    [
                        'gestionable_type',
                        'gestionable_id',
                    ],
                    'tareas_operacion_gestionable_idx'
                );
            }
        );
    }

    /**
     * Elimina completamente la relación agregada.
     */
    public function down(): void
    {
        Schema::table(
            'tareas_operacion_viaje',
            function (Blueprint $table) {
                $table->dropIndex(
                    'tareas_operacion_gestionable_idx'
                );

                $table->dropColumn([
                    'gestionable_type',
                    'gestionable_id',
                ]);
            }
        );
    }
};