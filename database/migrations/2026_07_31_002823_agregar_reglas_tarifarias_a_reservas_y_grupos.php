<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            if (!Schema::hasColumn('reservas', 'moneda')) {
                $table->string('moneda', 3)
                    ->nullable()
                    ->after('precio_total_viaje');
            }

            if (!Schema::hasColumn('reservas', 'precio_base_persona')) {
                $table->decimal('precio_base_persona', 10, 2)
                    ->nullable()
                    ->after('precio_total_viaje');
            }

            if (!Schema::hasColumn('reservas', 'cantidad_viajeros')) {
                $table->unsignedSmallInteger('cantidad_viajeros')
                    ->nullable()
                    ->after('precio_base_persona');
            }

            if (!Schema::hasColumn('reservas', 'edad_viajero')) {
                $table->unsignedTinyInteger('edad_viajero')
                    ->nullable()
                    ->after('cantidad_viajeros');
            }

            if (!Schema::hasColumn('reservas', 'categoria_tarifa')) {
                $table->enum('categoria_tarifa', [
                    'infante',
                    'nino',
                    'adulto',
                    'adulto_mayor',
                ])
                    ->nullable()
                    ->after('edad_viajero');
            }

            if (!Schema::hasColumn('reservas', 'porcentaje_tarifa')) {
                $table->decimal('porcentaje_tarifa', 5, 2)
                    ->nullable()
                    ->after('categoria_tarifa');
            }
        });

        Schema::table('grupos', function (Blueprint $table) {
            if (!Schema::hasColumn('grupos', 'tipo_grupo')) {
                $table->enum('tipo_grupo', [
                    'familiar',
                    'independiente',
                ])
                    ->nullable()
                    ->after('descripcion');
            }

            if (!Schema::hasColumn('grupos', 'responsable_pago_id')) {
                $table->foreignId('responsable_pago_id')
                    ->nullable()
                    ->after('tipo_grupo')
                    ->constrained('clientes')
                    ->nullOnDelete();
            }
        });

        Schema::table('grupos_clientes', function (Blueprint $table) {
            if (!Schema::hasColumn('grupos_clientes', 'edad_al_viajar')) {
                $table->unsignedTinyInteger('edad_al_viajar')
                    ->nullable()
                    ->after('cliente_id');
            }

            if (!Schema::hasColumn('grupos_clientes', 'categoria_tarifa')) {
                $table->enum('categoria_tarifa', [
                    'infante',
                    'nino',
                    'adulto',
                    'adulto_mayor',
                ])
                    ->nullable()
                    ->after('edad_al_viajar');
            }

            if (!Schema::hasColumn('grupos_clientes', 'porcentaje_tarifa')) {
                $table->decimal('porcentaje_tarifa', 5, 2)
                    ->nullable()
                    ->after('categoria_tarifa');
            }

            if (!Schema::hasColumn('grupos_clientes', 'precio_base')) {
                $table->decimal('precio_base', 10, 2)
                    ->nullable()
                    ->after('porcentaje_tarifa');
            }
        });
    }

    public function down(): void
    {
        Schema::table('grupos_clientes', function (Blueprint $table) {
            $columnas = [
                'edad_al_viajar',
                'categoria_tarifa',
                'porcentaje_tarifa',
                'precio_base',
            ];

            foreach ($columnas as $columna) {
                if (Schema::hasColumn('grupos_clientes', $columna)) {
                    $table->dropColumn($columna);
                }
            }
        });

        Schema::table('grupos', function (Blueprint $table) {
            if (Schema::hasColumn('grupos', 'responsable_pago_id')) {
                $table->dropForeign([
                    'responsable_pago_id',
                ]);

                $table->dropColumn('responsable_pago_id');
            }

            if (Schema::hasColumn('grupos', 'tipo_grupo')) {
                $table->dropColumn('tipo_grupo');
            }
        });

        Schema::table('reservas', function (Blueprint $table) {
            $columnas = [
                'moneda',
                'precio_base_persona',
                'cantidad_viajeros',
                'edad_viajero',
                'categoria_tarifa',
                'porcentaje_tarifa',
            ];

            foreach ($columnas as $columna) {
                if (Schema::hasColumn('reservas', $columna)) {
                    $table->dropColumn($columna);
                }
            }
        });
    }
};