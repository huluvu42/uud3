<?php
// database/migrations/YYYY_MM_DD_HHMMSS_add_person_id_to_voucher_purchases_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('voucher_purchases', function (Blueprint $table) {
            $table->foreignId('person_id')->nullable()->after('stage_id')->constrained('persons')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('voucher_purchases', function (Blueprint $table) {
            $table->dropForeign(['person_id']);
            $table->dropColumn('person_id');
        });
    }
};
