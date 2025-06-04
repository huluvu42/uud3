<?php
// database/migrations/xxxx_xx_xx_add_api_key_to_knack_objects_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('knack_objects', function (Blueprint $table) {
            $table->text('encrypted_api_key')->nullable()->after('app_id');
        });
    }

    public function down()
    {
        Schema::table('knack_objects', function (Blueprint $table) {
            $table->dropColumn('encrypted_api_key');
        });
    }
};
