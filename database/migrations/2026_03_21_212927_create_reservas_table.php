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
        
        Schema::create('reservas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_reserva',100);
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->foreignId('destino_id')->constrained('destinos')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->enum('tipo',['individual','grupal'])->default('individual');
            $table->date('fecha_reserva');
            $table->date('fecha_viaje');
            $table->decimal('precio_total_viaje', 10, 2);
            $table->enum('estado',['confirmada','pendiente','cancelada'])->default('pendiente');
            $table->enum('estado_pago',['pagado','parcial','pendiente'])->default('pendiente');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservas');
    }
};
