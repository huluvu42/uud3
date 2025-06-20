<?php

// ============================================================================
// database/migrations/xxxx_xx_xx_add_registration_fields_to_bands_table.php
// Vollständige Migration für alle neuen Felder
// ============================================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('bands', function (Blueprint $table) {
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

            // Zusätzliche Registrierungsdaten
            $table->string('emergency_contact')->nullable()->after('registration_reminder_sent_at');
            $table->text('special_requirements')->nullable()->after('emergency_contact');

            // Indexe für bessere Performance
            $table->index('registration_token');
            $table->index('registration_completed');
            $table->index('manager_email');
            $table->index(['registration_completed', 'registration_token']);
        });
    }

    public function down()
    {
        Schema::table('bands', function (Blueprint $table) {
            $table->dropIndex(['bands_registration_token_index']);
            $table->dropIndex(['bands_registration_completed_index']);
            $table->dropIndex(['bands_manager_email_index']);
            $table->dropIndex(['bands_registration_completed_registration_token_index']);

            $table->dropColumn([
                'travel_party',
                'manager_first_name',
                'manager_last_name',
                'manager_email',
                'manager_phone',
                'registration_token',
                'registration_token_expires_at',
                'registration_completed',
                'registration_link_sent_at',
                'registration_reminder_sent_at',
                'emergency_contact',
                'special_requirements',
            ]);
        });
    }
};
