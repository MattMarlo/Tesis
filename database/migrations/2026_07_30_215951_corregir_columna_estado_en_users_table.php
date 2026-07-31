<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'estado')) {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('estado', ['activo', 'inactivo'])
                    ->default('activo')
                    ->after('rol');
            });
        }
    }

    public function down(): void
    {
        // Se conserva la columna para evitar eliminar estados
        // existentes durante un rollback.
    }
};