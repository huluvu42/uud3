<?php
// database/migrations/add_performance_times_per_day_to_bands_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPerformanceTimesPerDayToBandsTable extends Migration
{
    public function up()
    {
        Schema::table('bands', function (Blueprint $table) {
            // Neue Performance-Time-Felder pro Tag hinzufügen
            $table->time('performance_time_day_1')->nullable()->after('performance_time'); // Donnerstag
            $table->time('performance_time_day_2')->nullable()->after('performance_time_day_1'); // Freitag
            $table->time('performance_time_day_3')->nullable()->after('performance_time_day_2'); // Samstag
            $table->time('performance_time_day_4')->nullable()->after('performance_time_day_3'); // Sonntag

            // Performance-Duration pro Tag
            $table->integer('performance_duration_day_1')->nullable()->after('performance_duration'); // Donnerstag
            $table->integer('performance_duration_day_2')->nullable()->after('performance_duration_day_1'); // Freitag
            $table->integer('performance_duration_day_3')->nullable()->after('performance_duration_day_2'); // Samstag
            $table->integer('performance_duration_day_4')->nullable()->after('performance_duration_day_3'); // Sonntag
        });
    }

    public function down()
    {
        Schema::table('bands', function (Blueprint $table) {
            $table->dropColumn([
                'performance_time_day_1',
                'performance_time_day_2',
                'performance_time_day_3',
                'performance_time_day_4',
                'performance_duration_day_1',
                'performance_duration_day_2',
                'performance_duration_day_3',
                'performance_duration_day_4'
            ]);
        });
    }
}
