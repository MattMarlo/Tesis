<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            if (!Schema::hasColumn('clientes', 'tipo_documento')) {
                $table->enum('tipo_documento', [
                    'cedula',
                    'pasaporte'
                ])->nullable()->after('apellidos');
            }

            if (!Schema::hasColumn('clientes', 'fecha_nacimiento')) {
                $table->date('fecha_nacimiento')
                    ->nullable()
                    ->after('documento');
            }

            if (!Schema::hasColumn('clientes', 'nacionalidad')) {
                $table->string('nacionalidad', 80)
                    ->nullable()
                    ->after('fecha_nacimiento');
            }

            if (!Schema::hasColumn('clientes', 'fecha_caducidad_documento')) {
                $table->date('fecha_caducidad_documento')
                    ->nullable()
                    ->after('nacionalidad');
            }

            if (!Schema::hasColumn('clientes', 'contacto_emergencia')) {
                $table->string('contacto_emergencia', 150)
                    ->nullable()
                    ->after('telefono');
            }

            if (!Schema::hasColumn('clientes', 'telefono_emergencia')) {
                $table->string('telefono_emergencia', 20)
                    ->nullable()
                    ->after('contacto_emergencia');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $columnas = [
                'tipo_documento',
                'fecha_nacimiento',
                'nacionalidad',
                'fecha_caducidad_documento',
                'contacto_emergencia',
                'telefono_emergencia',
            ];

            foreach ($columnas as $columna) {
                if (Schema::hasColumn('clientes', $columna)) {
                    $table->dropColumn($columna);
                }
            }
        });
    }
};