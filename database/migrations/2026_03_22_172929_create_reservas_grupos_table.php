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
        Schema::create('reservas_grupos', function (Blueprint $table) {
            // Definimos la columna
            $table->unsignedBigInteger('reserva_id'); 
            $table->foreignId('grupo_id')->constrained('grupos')->onDelete('cascade');
            $table->timestamps();

            // HACEMOS QUE reserva_id SEA LA PRIMARY KEY (Lógica 1 a 1)
            $table->primary('reserva_id');
            
            // Definimos la relación foránea manualmente para la PK
            $table->foreign('reserva_id')->references('id')->on('reservas')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservas_grupos');
    }
};
