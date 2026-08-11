<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'gestion_operativa_viajeros',
            function (Blueprint $table) {
                $table->foreignId('cliente_id')
                    ->nullable()
                    ->after('viajero_reserva_id')
                    ->constrained('clientes')
                    ->restrictOnDelete();

                $table->foreignId('viajero_reserva_id')
                    ->nullable()
                    ->change();

                $table->unique(
                    [
                        'gestion_operativa_id',
                        'cliente_id',
                    ],
                    'gestion_operativa_cliente_unique'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'gestion_operativa_viajeros',
            function (Blueprint $table) {
                $table->dropUnique(
                    'gestion_operativa_cliente_unique'
                );

                $table->dropConstrainedForeignId(
                    'cliente_id'
                );

                $table->foreignId('viajero_reserva_id')
                    ->nullable(false)
                    ->change();
            }
        );
    }
};
