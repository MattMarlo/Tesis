<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'alojamientos_reserva',
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
                    'nombre_hotel',
                    180
                );

                $table->string(
                    'ciudad',
                    120
                );

                $table->string(
                    'pais',
                    120
                )->nullable();

                $table->string(
                    'direccion',
                    255
                )->nullable();

                $table->dateTime(
                    'fecha_hora_entrada'
                );

                $table->dateTime(
                    'fecha_hora_salida'
                );

                $table->string(
                    'codigo_confirmacion',
                    100
                )->nullable();

                $table->string(
                    'tipo_habitacion',
                    120
                )->nullable();

                $table->unsignedInteger(
                    'cantidad_habitaciones'
                )->default(1);

                $table->text(
                    'distribucion_habitaciones'
                )->nullable();

                $table->string(
                    'alimentacion_incluida',
                    120
                )->nullable();

                $table->string(
                    'telefono_hotel',
                    30
                )->nullable();

                $table->string(
                    'correo_hotel',
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
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'alojamientos_reserva'
        );
    }
};