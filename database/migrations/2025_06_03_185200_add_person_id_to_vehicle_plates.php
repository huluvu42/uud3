<?php
// database/migrations/2024_XX_XX_add_person_id_to_vehicle_plates.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('vehicle_plates', function (Blueprint $table) {
            // Person-Zuordnung hinzufügen
            $table->foreignId('person_id')->nullable()->after('band_id')->constrained('persons')->onDelete('cascade');

            // Band-Zuordnung optional machen (für Rückwärtskompatibilität)
            $table->foreignId('band_id')->nullable()->change();

            // Index für bessere Performance
            $table->index(['person_id']);
        });
    }

    public function down()
    {
        Schema::table('vehicle_plates', function (Blueprint $table) {
            $table->dropForeign(['person_id']);
            $table->dropColumn('person_id');

            // Band-Zuordnung wieder required machen
            $table->foreignId('band_id')->nullable(false)->change();
        });
    }
};
