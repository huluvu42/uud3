<?php
// database/migrations/2025_06_20_081900_add_travel_party_and_manager_to_bands_table.php

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
        // database/migrations/add_travel_party_and_manager_to_bands_table.php
        Schema::table('bands', function (Blueprint $table) {
            // Travel Party
            $table->integer('travel_party')->nullable()->after('band_name');

            // Manager Kontaktdaten
            $table->string('manager_first_name')->nullable()->after('travel_party');
            $table->string('manager_last_name')->nullable()->after('manager_first_name');
            $table->string('manager_email')->nullable()->after('manager_last_name');
            $table->string('manager_phone')->nullable()->after('manager_email');

            // Registration System
            $table->string('registration_token', 64)->unique()->nullable()->after('manager_phone');
            $table->timestamp('registration_token_expires_at')->nullable()->after('registration_token');
            $table->boolean('registration_completed')->default(false)->after('registration_token_expires_at');
            $table->timestamp('registration_link_sent_at')->nullable()->after('registration_completed');
            $table->timestamp('registration_reminder_sent_at')->nullable()->after('registration_link_sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bands', function (Blueprint $table) {
            $table->dropColumn('travel_party');
            $table->dropColumn('manager_first_name');
            $table->dropColumn('manager_last_name');
            $table->dropColumn('manager_email');
            $table->dropColumn('manager_phone');
            $table->dropColumn('registration_token');
            $table->dropColumn('registration_token_expires_at');
            $table->dropColumn('registration_completed');
            $table->dropColumn('registration_link_sent_at');
            $table->dropColumn('registration_reminder_sent_at');
        });
    }
};
