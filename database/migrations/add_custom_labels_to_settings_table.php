<?php

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
        Schema::table('settings', function (Blueprint $table) {
            // Tag-Labels
            $table->string('day_1_label')->default('Tag 1');
            $table->string('day_2_label')->default('Tag 2');
            $table->string('day_3_label')->default('Tag 3');
            $table->string('day_4_label')->default('Tag 4');
    
            // Bereich-Labels
            $table->string('voucher_label')->default('Voucher/Bons');
            $table->string('backstage_label')->default('Backstage-Berechtigung');
    
            // Voucher-Regeln
            $table->enum('voucher_issuance_rule', ['current_day_only', 'current_and_past', 'all_days'])->default('current_day_only');
            $table->enum('voucher_output_mode', ['single', 'all_available'])->default('all_available');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // Entfernen der Tag-Labels
            $table->dropColumn(['day_1_label', 'day_2_label', 'day_3_label', 'day_4_label']);
    
            // Entfernen der Bereich-Labels
            $table->dropColumn(['voucher_label', 'backstage_label']);
    
            // Entfernen der Voucher-Regeln
            $table->dropColumn(['voucher_issuance_rule', 'voucher_output_mode']);
        });
    }
};