<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('persons', function (Blueprint $table) {
            // Für Voucher-Abfragen (werden in jeder Person-Karte angezeigt)
            $table->index(['voucher_day_1', 'voucher_day_2', 'voucher_day_3', 'voucher_day_4'], 'idx_persons_voucher_days');

            // Für Backstage-Status-Checks
            $table->index(['backstage_day_1', 'backstage_day_2', 'backstage_day_3', 'backstage_day_4'], 'idx_persons_backstage_days');
        });

        Schema::table('bands', function (Blueprint $table) {
            // Für Stage-Filter in Band-Listen
            $table->index(['stage_id', 'year'], 'idx_bands_stage_year');
        });

        Schema::table('voucher_purchases', function (Blueprint $table) {
            // Für die Verkaufszahlen-Abfragen (sehr häufig in getSoldVouchersForStage)
            $table->index(['stage_id', 'day', 'purchase_date'], 'idx_voucher_purchases_stats');

            // Für tägliche Statistiken
            $table->index(['purchase_date', 'day'], 'idx_voucher_purchases_daily');
        });

        Schema::table('vehicle_plates', function (Blueprint $table) {
            // Composite Index für Person-Kennzeichen Abfragen
            $table->index(['person_id', 'license_plate'], 'idx_vehicle_plates_person');
        });

        // PostgreSQL: Volltext-Suche für Namen (nur wenn PostgreSQL verwendet wird)
        if (DB::getDriverName() === 'pgsql') {
            // Prüfe ob pg_trgm Extension existiert
            $extensionExists = DB::select("SELECT 1 FROM pg_extension WHERE extname = 'pg_trgm'");

            if (empty($extensionExists)) {
                // Extension erstellen (benötigt SUPERUSER Rechte)
                try {
                    DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
                } catch (\Exception $e) {
                    // Fallback: Warnung ausgeben aber Migration nicht abbrechen
                    \Log::warning('Could not create pg_trgm extension. Fulltext search index skipped. Error: ' . $e->getMessage());
                }
            }

            // GIN Index für bessere ILIKE Performance bei Namen-Suche
            try {
                DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_persons_fullname_gin ON persons USING gin ((first_name || \' \' || last_name) gin_trgm_ops)');
            } catch (\Exception $e) {
                \Log::warning('Could not create GIN index for fulltext search. Error: ' . $e->getMessage());
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('persons', function (Blueprint $table) {
            $table->dropIndex('idx_persons_voucher_days');
            $table->dropIndex('idx_persons_backstage_days');
        });

        Schema::table('bands', function (Blueprint $table) {
            $table->dropIndex('idx_bands_stage_year');
        });

        Schema::table('voucher_purchases', function (Blueprint $table) {
            $table->dropIndex('idx_voucher_purchases_stats');
            $table->dropIndex('idx_voucher_purchases_daily');
        });

        Schema::table('vehicle_plates', function (Blueprint $table) {
            $table->dropIndex('idx_vehicle_plates_person');
        });

        // PostgreSQL GIN Index entfernen
        if (DB::getDriverName() === 'pgsql') {
            try {
                DB::statement('DROP INDEX IF EXISTS idx_persons_fullname_gin');
            } catch (\Exception $e) {
                \Log::warning('Could not drop GIN index. Error: ' . $e->getMessage());
            }
        }
    }
};
