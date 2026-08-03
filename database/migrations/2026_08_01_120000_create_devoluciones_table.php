<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devoluciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pago_id')->constrained('pagos')->restrictOnDelete();
            $table->foreignId('reserva_id')->constrained('reservas')->restrictOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->decimal('monto', 10, 2);
            $table->string('metodo', 30);
            $table->string('referencia', 100)->nullable();
            $table->text('motivo');
            $table->string('estado', 20)->default('procesada')->index();
            $table->timestamp('fecha_devolucion')->useCurrent();
            $table->text('motivo_anulacion')->nullable();
            $table->timestamp('fecha_anulacion')->nullable();
            $table->foreignId('anulada_por_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['pago_id', 'estado']);
            $table->index(['reserva_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devoluciones');
    }
};
