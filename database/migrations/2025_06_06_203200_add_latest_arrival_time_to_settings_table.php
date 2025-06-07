<?php

// database/migrations/2024_xx_xx_add_latest_arrival_time_to_settings_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->integer('latest_arrival_time_minutes')->nullable()->default(60)->comment('Latest arrival time for bands in minutes before performance');
        });
    }

    public function down()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('latest_arrival_time_minutes');
        });
    }
};
