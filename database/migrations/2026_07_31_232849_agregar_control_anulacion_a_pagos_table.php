<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->string(
                'estado',
                20
            )
                ->default('registrado')
                ->after('referencia')
                ->index();

            $table->text(
                'motivo_anulacion'
            )
                ->nullable()
                ->after('estado');

            $table->timestamp(
                'fecha_anulacion'
            )
                ->nullable()
                ->after('motivo_anulacion');

            $table->foreignId(
                'anulado_por_user_id'
            )
                ->nullable()
                ->after('fecha_anulacion')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropForeign([
                'anulado_por_user_id'
            ]);

            $table->dropIndex([
                'estado'
            ]);

            $table->dropColumn([
                'estado',
                'motivo_anulacion',
                'fecha_anulacion',
                'anulado_por_user_id',
            ]);
        });
    }
};