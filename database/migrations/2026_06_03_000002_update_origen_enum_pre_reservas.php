<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add 'web' and 'n8n' to the enum values for `origen` in pre_reservas
        DB::statement("ALTER TABLE `pre_reservas` MODIFY `origen` ENUM('telegram_bot','whatsapp_bot','landing_page','web','n8n') NOT NULL DEFAULT 'telegram_bot';");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE `pre_reservas` MODIFY `origen` ENUM('telegram_bot','whatsapp_bot','landing_page') NOT NULL DEFAULT 'telegram_bot';");
    }
};
