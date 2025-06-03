<?php
// database/migrations/YYYY_MM_DD_HHMMSS_add_can_have_guests_to_persons_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('persons', function (Blueprint $table) {
            $table->boolean('can_have_guests')->default(false)->after('present');
        });
    }

    public function down()
    {
        Schema::table('persons', function (Blueprint $table) {
            $table->dropColumn('can_have_guests');
        });
    }
};