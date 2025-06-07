<?php

// database/migrations/2024_xx_xx_make_travel_costs_nullable_in_bands_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('bands', function (Blueprint $table) {
            $table->decimal('travel_costs', 8, 2)->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('bands', function (Blueprint $table) {
            $table->decimal('travel_costs', 8, 2)->default(0.00)->change();
        });
    }
};
