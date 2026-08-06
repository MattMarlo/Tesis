<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'gastos_cancelacion',
            function (Blueprint $table) {
                $table->id();

                $table
                    ->foreignId('reserva_id')
                    ->constrained('reservas')
                    ->restrictOnDelete();

                $table
                    ->foreignId('registrado_por_user_id')
                    ->constrained('users')
                    ->restrictOnDelete();

                $table
                    ->foreignId('revisado_por_user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->string(
                    'proveedor',
                    150
                );

                $table->string(
                    'concepto',
                    200
                );

                $table->decimal(
                    'monto',
                    12,
                    2
                );

                $table->string(
                    'numero_documento',
                    100
                )->nullable();

                $table->date(
                    'fecha_documento'
                )->nullable();

                /*
                 * El archivo se guardará en storage privado.
                 * En la base solamente guardamos su ruta y datos.
                 */
                $table->string(
                    'archivo_path',
                    500
                );

                $table->string(
                    'archivo_nombre_original',
                    255
                );

                $table->string(
                    'archivo_mime',
                    100
                );

                $table->unsignedBigInteger(
                    'archivo_tamano'
                );

                /*
                 * Permite comprobar si el archivo fue alterado.
                 */
                $table->string(
                    'archivo_hash',
                    64
                );

                $table->string(
                    'estado',
                    20
                )->default('pendiente');

                $table->text(
                    'observaciones'
                )->nullable();

                $table->text(
                    'motivo_revision'
                )->nullable();

                $table->timestamp(
                    'revisado_at'
                )->nullable();

                $table->timestamps();

                $table->index([
                    'reserva_id',
                    'estado',
                ]);

                $table->index([
                    'estado',
                    'created_at',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'gastos_cancelacion'
        );
    }
};