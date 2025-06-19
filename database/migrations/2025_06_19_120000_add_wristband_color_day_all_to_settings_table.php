<?php
// database/migrations/2025_06_19_120000_add_wristband_color_day_all_to_settings_table.php

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
        Schema::table('settings', function (Blueprint $table) {
            $table->string('wristband_color_day_all')->nullable()->after('wristband_color_day_4')
                ->comment('Bändchenfarbe für alle verbleibenden Tage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('wristband_color_day_all');
        });
    }
};
