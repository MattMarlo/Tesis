<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pre_reserva_integrantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pre_reserva_id')->constrained('pre_reservas')->cascadeOnDelete();
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->enum('tipo_documento', ['cedula', 'pasaporte']);
            $table->string('documento', 30);
            $table->date('fecha_nacimiento');
            $table->date('fecha_caducidad_documento')->nullable();
            $table->string('nacionalidad', 80);
            $table->string('email', 150)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('contacto_emergencia', 150)->nullable();
            $table->string('telefono_emergencia', 20)->nullable();
            $table->boolean('es_lider')->default(false);
            $table->boolean('es_responsable_pago')->default(false);
            $table->unsignedSmallInteger('edad_al_viajar');
            $table->enum('categoria_tarifa', ['infante', 'nino', 'adulto', 'adulto_mayor']);
            $table->decimal('porcentaje_tarifa', 5, 2);
            $table->decimal('precio_calculado', 10, 2);
            $table->timestamps();
            $table->unique(['pre_reserva_id', 'documento']);
            $table->index('documento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pre_reserva_integrantes');
    }
};
