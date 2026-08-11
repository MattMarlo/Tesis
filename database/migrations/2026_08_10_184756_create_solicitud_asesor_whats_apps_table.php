<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'solicitud_asesor_whats_apps',
            function (Blueprint $table) {
                $table->id();

                $table
                    ->string('nombre', 150)
                    ->nullable();

                $table
                    ->string('telefono', 30)
                    ->index();

                $table->text('motivo');

                $table
                    ->string('estado', 30)
                    ->default('pendiente')
                    ->index();

                $table
                    ->string('mensaje_id', 255)
                    ->nullable()
                    ->unique();

                $table
                    ->unsignedBigInteger('atendido_por')
                    ->nullable();

                $table
                    ->dateTime('fecha_contacto')
                    ->nullable();

                $table->timestamps();

                $table
                    ->foreign('atendido_por')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'solicitud_asesor_whats_apps'
        );
    }
};