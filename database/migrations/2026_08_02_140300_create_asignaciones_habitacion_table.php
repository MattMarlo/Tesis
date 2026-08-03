<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asignaciones_habitacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alojamiento_reserva_id')
                ->constrained('alojamientos_reserva')
                ->cascadeOnDelete();
            $table->foreignId('habitacion_alojamiento_id')
                ->constrained('habitaciones_alojamiento')
                ->cascadeOnDelete();
            $table->foreignId('viajero_reserva_id')
                ->nullable()
                ->constrained('viajeros_reserva')
                ->restrictOnDelete();
            $table->foreignId('cliente_id')
                ->nullable()
                ->constrained('clientes')
                ->restrictOnDelete();
            $table->timestamps();

            $table->unique(
                ['alojamiento_reserva_id', 'viajero_reserva_id'],
                'asignacion_alojamiento_viajero_unique'
            );
            $table->unique(
                ['alojamiento_reserva_id', 'cliente_id'],
                'asignacion_alojamiento_cliente_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asignaciones_habitacion');
    }
};
