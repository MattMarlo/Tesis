<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pre_reservas', function (Blueprint $table) {
            $table->enum('tipo_reserva', ['individual', 'grupal'])->default('individual');
            $table->enum('tipo_grupo', ['familiar', 'independiente'])->nullable();
            $table->string('nombre_grupo', 150)->nullable();
            $table->decimal('precio_estimado', 10, 2)->nullable();
            $table->char('moneda', 3)->default('USD');
            $table->boolean('acepta_condiciones')->default(false);
            $table->timestamp('confirmada_por_cliente_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pre_reservas', function (Blueprint $table) {
            $table->dropColumn([
                'tipo_reserva', 'tipo_grupo', 'nombre_grupo', 'precio_estimado',
                'moneda', 'acepta_condiciones', 'confirmada_por_cliente_at',
            ]);
        });
    }
};
