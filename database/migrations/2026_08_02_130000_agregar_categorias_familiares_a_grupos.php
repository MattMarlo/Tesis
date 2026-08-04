<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grupos', function (Blueprint $table) {
            $table->boolean('usa_categorias_familiares')
                ->default(false)
                ->after('responsable_pago_id');
            $table->unsignedInteger('cantidad_infantes')
                ->nullable()
                ->after('usa_categorias_familiares');
            $table->unsignedInteger('cantidad_ninos')
                ->nullable()
                ->after('cantidad_infantes');
            $table->unsignedInteger('cantidad_adultos')
                ->nullable()
                ->after('cantidad_ninos');
            $table->unsignedInteger('cantidad_adultos_mayores')
                ->nullable()
                ->after('cantidad_adultos');
        });
    }

    public function down(): void
    {
        $columnas = [
            'cantidad_adultos_mayores',
            'cantidad_adultos',
            'cantidad_ninos',
            'cantidad_infantes',
            'usa_categorias_familiares',
        ];

        foreach ($columnas as $columna) {
            if (Schema::hasColumn('grupos', $columna)) {
                Schema::table(
                    'grupos',
                    function (Blueprint $table) use ($columna) {
                        $table->dropColumn($columna);
                    }
                );
            }
        }
    }
};
