<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'vuelos_reserva',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId(
                    'operacion_viaje_id'
                )
                    ->constrained(
                        'operaciones_viaje'
                    )
                    ->cascadeOnDelete();

                $table->string(
                    'tipo_tramo',
                    20
                );

                $table->string(
                    'aerolinea',
                    120
                );

                $table->string(
                    'numero_vuelo',
                    30
                )->nullable();

                $table->string(
                    'ciudad_origen',
                    120
                );

                $table->string(
                    'aeropuerto_origen',
                    150
                )->nullable();

                $table->string(
                    'ciudad_destino',
                    120
                );

                $table->string(
                    'aeropuerto_destino',
                    150
                )->nullable();

                $table->dateTime(
                    'fecha_hora_salida'
                );

                $table->dateTime(
                    'fecha_hora_llegada'
                );

                $table->string(
                    'terminal_salida',
                    50
                )->nullable();

                $table->string(
                    'terminal_llegada',
                    50
                )->nullable();

                $table->string(
                    'localizador_reserva',
                    80
                )->nullable();

                $table->string(
                    'equipaje_incluido',
                    150
                )->nullable();

                $table->string(
                    'proveedor',
                    150
                )->nullable();

                $table->date(
                    'fecha_compra'
                )->nullable();

                $table->decimal(
                    'costo_total',
                    12,
                    2
                )->nullable();

                $table->string(
                    'moneda',
                    3
                )->default('USD');

                $table->string(
                    'estado',
                    20
                )->default('confirmado');

                $table->text(
                    'observaciones'
                )->nullable();

                $table->timestamps();

                $table->index([
                    'operacion_viaje_id',
                    'tipo_tramo',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'vuelos_reserva'
        );
    }
};