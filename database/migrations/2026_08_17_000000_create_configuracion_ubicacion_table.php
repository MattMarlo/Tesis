<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracion_ubicacion', function (Blueprint $table) {
            $table->id();
            $table->string('localidad', 100);
            $table->string('direccion');
            $table->string('consulta_mapa');
            $table->text('enlace_mapa');
            $table->timestamps();
        });

        DB::table('configuracion_ubicacion')->insert([
            'localidad' => 'Salcedo',
            'direccion' => 'Salcedo, Cotopaxi, Ecuador',
            'consulta_mapa' => 'Passion Travel, Salcedo, Cotopaxi, Ecuador',
            'enlace_mapa' => 'https://maps.app.goo.gl/BcySuXQbntDDHPZY8',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracion_ubicacion');
    }
};
