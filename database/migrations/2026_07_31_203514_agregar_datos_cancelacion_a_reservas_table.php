<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            if (!Schema::hasColumn('reservas', 'motivo_cancelacion')) {
                $table->text('motivo_cancelacion')
                    ->nullable()
                    ->after('estado_pago');
            }

            if (!Schema::hasColumn('reservas', 'fecha_cancelacion')) {
                $table->timestamp('fecha_cancelacion')
                    ->nullable()
                    ->after('motivo_cancelacion');
            }

            if (!Schema::hasColumn('reservas', 'cancelado_por_user_id')) {
                $table->foreignId('cancelado_por_user_id')
                    ->nullable()
                    ->after('fecha_cancelacion')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            if (
                Schema::hasColumn(
                    'reservas',
                    'cancelado_por_user_id'
                )
            ) {
                $table->dropForeign([
                    'cancelado_por_user_id',
                ]);

                $table->dropColumn(
                    'cancelado_por_user_id'
                );
            }

            if (
                Schema::hasColumn(
                    'reservas',
                    'fecha_cancelacion'
                )
            ) {
                $table->dropColumn(
                    'fecha_cancelacion'
                );
            }

            if (
                Schema::hasColumn(
                    'reservas',
                    'motivo_cancelacion'
                )
            ) {
                $table->dropColumn(
                    'motivo_cancelacion'
                );
            }
        });
    }
};