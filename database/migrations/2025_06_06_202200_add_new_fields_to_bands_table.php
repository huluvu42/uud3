<?php

// database/migrations/2024_xx_xx_add_new_fields_to_bands_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('bands', function (Blueprint $table) {
            $table->time('performance_time')->nullable()->comment('Time when the band performs');
            $table->integer('performance_duration')->nullable()->comment('Performance duration in minutes');
            $table->string('hotel')->nullable()->comment('Band hotel');
            $table->text('comment')->nullable()->comment('General comment');
            $table->text('travel_costs_comment')->nullable()->comment('Travel costs comment');
        });
    }

    public function down()
    {
        Schema::table('bands', function (Blueprint $table) {
            $table->dropColumn([
                'performance_time',
                'performance_duration',
                'hotel',
                'comment',
                'travel_costs_comment'
            ]);
        });
    }
};
