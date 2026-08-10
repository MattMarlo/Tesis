<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(
            'solicitudes_prerreserva_whatsapp',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId('destino_id')
                    ->constrained('destinos')
                    ->restrictOnDelete();

                $table->string('referencia_externa', 150)
                    ->unique();

                $table->string('nombre_completo', 150);
                $table->string('cedula', 10);
                $table->string('correo', 150);
                $table->string('telefono', 20);

                $table->string('tipo_reserva', 20);
                $table->unsignedSmallInteger(
                    'cantidad_personas'
                );

                $table->string('estado', 30)
                    ->default('pendiente');

                $table->timestamps();
            }
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'solicitudes_prerreserva_whatsapp'
        );
    }
};
