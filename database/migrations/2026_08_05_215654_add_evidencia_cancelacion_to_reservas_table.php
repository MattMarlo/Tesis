<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            !Schema::hasColumn(
                'reservas',
                'evidencia_cancelacion'
            )
        ) {
            Schema::table(
                'reservas',
                function (Blueprint $table) {
                    $table
                        ->text('evidencia_cancelacion')
                        ->nullable()
                        ->after(
                            'detalle_gastos_no_reembolsables'
                        );
                }
            );
        }
    }

    public function down(): void
    {
        if (
            Schema::hasColumn(
                'reservas',
                'evidencia_cancelacion'
            )
        ) {
            Schema::table(
                'reservas',
                function (Blueprint $table) {
                    $table->dropColumn(
                        'evidencia_cancelacion'
                    );
                }
            );
        }
    }
};