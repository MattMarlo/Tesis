<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'pre_reservas',
            function (Blueprint $table) {
                $table->foreignId(
                    'destino_id'
                )
                    ->nullable()
                    ->after('destino')
                    ->constrained('destinos')
                    ->nullOnDelete();

                $table->unsignedSmallInteger(
                    'cantidad_personas'
                )
                    ->default(1)
                    ->after('fecha_viaje');

                $table->string(
                    'telegram_chat_id',
                    100
                )
                    ->nullable()
                    ->after('origen')
                    ->index();

                $table->string(
                    'referencia_externa',
                    150
                )
                    ->nullable()
                    ->after('telegram_chat_id')
                    ->unique();

                $table->timestamp(
                    'fecha_contacto'
                )
                    ->nullable()
                    ->after('estado');

                $table->timestamp(
                    'fecha_descarte'
                )
                    ->nullable()
                    ->after('fecha_contacto');

                $table->text(
                    'observaciones'
                )
                    ->nullable()
                    ->after('fecha_descarte');
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'pre_reservas',
            function (Blueprint $table) {
                $table->dropUnique([
                    'referencia_externa',
                ]);

                $table->dropIndex([
                    'telegram_chat_id',
                ]);

                $table->dropConstrainedForeignId(
                    'destino_id'
                );

                $table->dropColumn([
                    'cantidad_personas',
                    'telegram_chat_id',
                    'referencia_externa',
                    'fecha_contacto',
                    'fecha_descarte',
                    'observaciones',
                ]);
            }
        );
    }
};