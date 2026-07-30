<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testimonios', function (Blueprint $table) {
            $table->id();

            $table->string('nombre', 100);

            $table->string('destino', 150)->nullable();

            $table->text('comentario');

            $table->unsignedTinyInteger('calificacion')
                ->default(5);

            $table->string('foto')->nullable();

            $table->enum('estado', [
                'pendiente',
                'publicado',
                'oculto'
            ])->default('pendiente');

            $table->boolean('destacado')
                ->default(false);

            $table->unsignedInteger('orden')
                ->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonios');
    }
};