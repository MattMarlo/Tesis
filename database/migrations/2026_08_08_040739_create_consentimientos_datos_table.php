<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consentimientos_datos', function (Blueprint $table) {
            $table->id();

            $table->string('telefono', 25)->index();

            $table->string('canal', 30)
                ->default('whatsapp');

            $table->enum('estado', [
                'aceptado',
                'rechazado',
                'revocado',
            ]);

            $table->string('version_politica', 30)
                ->default('2026-08-08-v1');

            $table->string('politica_url', 255)
                ->default(
                    'https://passiontravelviajes.de/politica-de-privacidad'
                );

            $table->string('mensaje_id', 255)
                ->nullable()
                ->unique();

            $table->dateTime('fecha_evento')->nullable();

            $table->json('evidencia')
                ->nullable();

            $table->timestamps();

            $table->index([
                'telefono',
                'canal',
                'fecha_evento',
            ], 'consentimientos_busqueda_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consentimientos_datos');
    }
};