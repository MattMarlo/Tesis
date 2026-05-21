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
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();

            // Relaciones
            $table->foreignId('reserva_id')
                  ->constrained('reservas')
                  ->restrictOnDelete();

            $table->foreignId('cliente_id')
                  ->constrained('clientes')
                  ->restrictOnDelete();

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->restrictOnDelete();

            // Datos del pago
            $table->decimal('monto_depositado', 10, 2);
            $table->dateTime('fecha_pago')->useCurrent();

            // Método de pago
            $table->enum('metodo_pago', [
                'efectivo',
                'transferencia',
                'tarjeta',
                'otro'
            ]);

            // Referencia (comprobante, número de transacción, etc.)
            $table->string('referencia', 100)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
