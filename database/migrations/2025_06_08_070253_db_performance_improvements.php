<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */

    public function up()
    {
        Schema::table('persons', function (Blueprint $table) {
            // Composite Index für häufige Abfragen
            $table->index(['year', 'is_duplicate']);
            $table->index(['first_name', 'last_name']);
            $table->index(['band_id', 'present']); // Für Band all_present Check
            $table->index('responsible_person_id'); // Für Gäste-Abfragen
        });

        Schema::table('bands', function (Blueprint $table) {
            $table->index(['year', 'band_name']);
            $table->index(['plays_day_1', 'plays_day_2', 'plays_day_3', 'plays_day_4']);
        });

        Schema::table('vehicle_plates', function (Blueprint $table) {
            $table->index('license_plate'); // Für Kennzeichen-Suche
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
