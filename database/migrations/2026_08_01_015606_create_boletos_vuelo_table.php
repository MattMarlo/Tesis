<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'boletos_vuelo',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId(
                    'vuelo_reserva_id'
                )
                    ->constrained(
                        'vuelos_reserva'
                    )
                    ->cascadeOnDelete();

                $table->foreignId(
                    'cliente_id'
                )
                    ->constrained('clientes')
                    ->restrictOnDelete();

                $table->string(
                    'numero_boleto',
                    100
                )->nullable();

                $table->string(
                    'asiento',
                    20
                )->nullable();

                $table->string(
                    'clase',
                    50
                )->nullable();

                $table->string(
                    'estado_emision',
                    20
                )->default('pendiente');

                $table->string(
                    'archivo_boleto',
                    255
                )->nullable();

                $table->text(
                    'observaciones'
                )->nullable();

                $table->timestamps();

                $table->unique([
                    'vuelo_reserva_id',
                    'cliente_id',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'boletos_vuelo'
        );
    }
};