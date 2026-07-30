<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * SQLite no permite MODIFY y durante las pruebas esta
         * columna ya existe como texto. La modificación del ENUM
         * solamente es necesaria en MySQL.
         */
        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE `pre_reservas`
                MODIFY `origen`
                ENUM(
                    'telegram_bot',
                    'whatsapp_bot',
                    'landing_page',
                    'web',
                    'n8n'
                )
                NOT NULL
                DEFAULT 'telegram_bot'"
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE `pre_reservas`
                MODIFY `origen`
                ENUM(
                    'telegram_bot',
                    'whatsapp_bot',
                    'landing_page'
                )
                NOT NULL
                DEFAULT 'telegram_bot'"
            );
        }
    }
};