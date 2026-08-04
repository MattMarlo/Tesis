<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boletos_vuelo', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
        });

        Schema::table('boletos_vuelo', function (Blueprint $table) {
            $table->foreignId('cliente_id')->nullable()->change();
            $table->foreign('cliente_id')
                ->references('id')
                ->on('clientes')
                ->restrictOnDelete();
            $table->foreignId('viajero_reserva_id')
                ->nullable()
                ->after('cliente_id')
                ->constrained('viajeros_reserva')
                ->restrictOnDelete();
            $table->unique(
                ['vuelo_reserva_id', 'viajero_reserva_id'],
                'boletos_vuelo_vuelo_viajero_unique'
            );
        });
    }

    public function down(): void
    {
        if (
            DB::table('boletos_vuelo')
                ->whereNull('cliente_id')
                ->whereNotNull('viajero_reserva_id')
                ->exists()
        ) {
            throw new \RuntimeException(
                'No se puede revertir la migración: existen boletos vinculados exclusivamente a viajeros de reserva. Retírelos o migre conscientemente esos boletos antes del rollback.'
            );
        }

        Schema::table('boletos_vuelo', function (Blueprint $table) {
            $table->dropUnique('boletos_vuelo_vuelo_viajero_unique');
            $table->dropForeign(['viajero_reserva_id']);
            $table->dropColumn('viajero_reserva_id');
            $table->dropForeign(['cliente_id']);
        });

        Schema::table('boletos_vuelo', function (Blueprint $table) {
            $table->foreignId('cliente_id')->nullable(false)->change();
            $table->foreign('cliente_id')
                ->references('id')
                ->on('clientes')
                ->restrictOnDelete();
        });
    }
};
