<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'operaciones_viaje',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId(
                    'reserva_id'
                )
                    ->unique()
                    ->constrained('reservas')
                    ->restrictOnDelete();

                $table->string(
                    'estado',
                    30
                )
                    ->default('pendiente')
                    ->index();

                $table->text(
                    'observaciones'
                )->nullable();

                $table->timestamp(
                    'fecha_documentacion_completa'
                )->nullable();

                $table->timestamp(
                    'fecha_notificacion'
                )->nullable();

                $table->foreignId(
                    'creado_por_user_id'
                )
                    ->constrained('users')
                    ->restrictOnDelete();

                $table->foreignId(
                    'actualizado_por_user_id'
                )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'operaciones_viaje'
        );
    }
};