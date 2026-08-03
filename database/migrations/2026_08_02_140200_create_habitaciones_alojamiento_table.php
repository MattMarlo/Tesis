<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('habitaciones_alojamiento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alojamiento_reserva_id')
                ->constrained('alojamientos_reserva')
                ->cascadeOnDelete();
            $table->string('tipo', 20);
            $table->unsignedSmallInteger('capacidad');
            $table->string('referencia', 100)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('habitaciones_alojamiento');
    }
};
