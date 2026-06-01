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
        Schema::create('pre_reservas', function (Blueprint $table) {
            $table->id();
            $table->string('cliente_nombre', 100);
            $table->string('destino', 255);
            $table->string('telefono', 50);
            $table->date('fecha_viaje')->nullable();
            $table->timestamp('fecha_reserva')->useCurrent();
            $table->enum('origen', ['telegram_bot', 'whatsapp_bot', 'landing_page'])->default('telegram_bot');
            $table->enum('estado', ['pendiente_contacto', 'contactado', 'convertida', 'perdida'])->default('pendiente_contacto');
            
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            
            $table->foreignId('reserva_id')->nullable()->constrained('reservas')->nullOnDelete();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pre_reservas');
    }
};
