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
        Schema::create('conversaciones_telegram', function (Blueprint $table) {
            $table->id(); 
            $table->string('chat_id', 100)->unique();
            $table->string('estado', 50)->default('esperando_destino');
            $table->string('destino', 255)->nullable();
            $table->string('nombre', 100)->nullable();
            $table->date('fecha_viaje')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversaciones_telegram');
    }
};
