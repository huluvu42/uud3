<?php
// database/migrations/YYYY_MM_DD_HHMMSS_add_duplicate_fields_to_persons_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('persons', function (Blueprint $table) {
            $table->boolean('is_duplicate')->default(false)->after('knack_id');
            $table->string('duplicate_reason')->nullable()->after('is_duplicate');
            $table->timestamp('duplicate_marked_at')->nullable()->after('duplicate_reason');
            $table->unsignedBigInteger('duplicate_marked_by')->nullable()->after('duplicate_marked_at');
            
            // Index für bessere Performance bei Duplikat-Suchen
            $table->index(['first_name', 'last_name', 'year']);
            $table->index(['is_duplicate']);
            
            // Foreign Key für User der die Markierung gesetzt hat
            $table->foreign('duplicate_marked_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('persons', function (Blueprint $table) {
            $table->dropForeign(['duplicate_marked_by']);
            $table->dropIndex(['first_name', 'last_name', 'year']);
            $table->dropIndex(['is_duplicate']);
            $table->dropColumn(['is_duplicate', 'duplicate_reason', 'duplicate_marked_at', 'duplicate_marked_by']);
        });
    }
};