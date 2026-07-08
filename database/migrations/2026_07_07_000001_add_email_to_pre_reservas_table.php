<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pre_reservas', function (Blueprint $table) {
            if (!Schema::hasColumn('pre_reservas', 'email')) {
                $table->string('email', 100)->nullable()->after('cliente_nombre');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pre_reservas', function (Blueprint $table) {
            if (Schema::hasColumn('pre_reservas', 'email')) {
                $table->dropColumn('email');
            }
        });
    }
};
