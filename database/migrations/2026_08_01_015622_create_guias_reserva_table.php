<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'guias_reserva',
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
                    'nombre_completo',
                    180
                );

                $table->string(
                    'empresa',
                    150
                )->nullable();

                $table->string(
                    'ciudad_servicio',
                    120
                )->nullable();

                $table->string(
                    'telefono',
                    30
                );

                $table->string(
                    'correo',
                    150
                )->nullable();

                $table->string(
                    'idiomas',
                    150
                )->nullable();

                $table->date(
                    'fecha_inicio'
                )->nullable();

                $table->date(
                    'fecha_fin'
                )->nullable();

                $table->string(
                    'punto_encuentro',
                    255
                )->nullable();

                $table->dateTime(
                    'fecha_hora_encuentro'
                )->nullable();

                $table->text(
                    'servicios_incluidos'
                )->nullable();

                $table->string(
                    'contacto_emergencia',
                    150
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
            'guias_reserva'
        );
    }
};