<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('viajeros_reserva', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reserva_id')
                ->constrained('reservas')
                ->cascadeOnDelete();
            $table->foreignId('cliente_id')
                ->nullable()
                ->constrained('clientes')
                ->restrictOnDelete();
            $table->string('nombres', 120);
            $table->string('apellidos', 120);
            $table->string('tipo_documento', 20)->nullable();
            $table->string('documento', 50)->nullable();
            $table->date('fecha_nacimiento');
            $table->unsignedSmallInteger('edad_al_viajar');
            $table->string('categoria_tarifa', 30);
            $table->boolean('es_titular')->default(false);
            $table->timestamps();

            $table->unique(['reserva_id', 'documento']);
            $table->unique(['reserva_id', 'cliente_id']);
            $table->index(['reserva_id', 'categoria_tarifa']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('viajeros_reserva');
    }
};
