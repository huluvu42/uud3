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
        // ===== STAGE TABLE ENHANCEMENTS =====
        Schema::table('stages', function (Blueprint $table) {
            // Neue Spalten für erweiterte Funktionalität
            if (!Schema::hasColumn('stages', 'max_bands')) {
                $table->integer('max_bands')->default(999)->after('name');
            }

            if (!Schema::hasColumn('stages', 'backstage_all_days')) {
                $table->boolean('backstage_all_days')->default(false)->after('guest_allowed');
            }

            if (!Schema::hasColumn('stages', 'voucher_amount')) {
                $table->decimal('voucher_amount', 5, 2)->default(0)->after('backstage_all_days');
            }

            // Voucher pro Tag (falls unterschiedliche Beträge pro Tag gewünscht)
            if (!Schema::hasColumn('stages', 'voucher_day_1')) {
                $table->decimal('voucher_day_1', 5, 2)->default(0)->after('voucher_amount');
                $table->decimal('voucher_day_2', 5, 2)->default(0)->after('voucher_day_1');
                $table->decimal('voucher_day_3', 5, 2)->default(0)->after('voucher_day_2');
                $table->decimal('voucher_day_4', 5, 2)->default(0)->after('voucher_day_3');
            }
        });

        // ===== BANDS TABLE OPTIMIZATIONS =====
        Schema::table('bands', function (Blueprint $table) {
            // Performance Indexe für häufige Queries
            $table->index(['year', 'band_name'], 'idx_bands_year_name');
            $table->index(['stage_id', 'year'], 'idx_bands_stage_year');
            $table->index('all_present', 'idx_bands_all_present');
            $table->index('created_at', 'idx_bands_created_at');

            // Travel costs precision improvement
            if (Schema::hasColumn('bands', 'travel_costs')) {
                $table->decimal('travel_costs', 8, 2)->nullable()->change();
            }
        });

        // ===== PERSONS TABLE OPTIMIZATIONS =====
        Schema::table('persons', function (Blueprint $table) {
            // Composite Indexe für Band-Member Queries
            $table->index(['band_id', 'present'], 'idx_persons_band_present');
            $table->index(['band_id', 'is_duplicate'], 'idx_persons_band_duplicate');
            $table->index(['year', 'is_duplicate'], 'idx_persons_year_duplicate');
            $table->index('responsible_person_id', 'idx_persons_responsible');

            // Indexe für Voucher-Queries
            $table->index(['band_id', 'voucher_day_1'], 'idx_persons_band_voucher1');
            $table->index(['group_id', 'present'], 'idx_persons_group_present');
        });

        // ===== VEHICLE PLATES TABLE OPTIMIZATIONS =====
        Schema::table('vehicle_plates', function (Blueprint $table) {
            // Indexe für Kennzeichen-Suche
            $table->index('license_plate', 'idx_vehicle_plates_license');
            $table->index('band_id', 'idx_vehicle_plates_band');
            $table->index('person_id', 'idx_vehicle_plates_person');

            // Unique Index für Kennzeichen (falls gewünscht)
            // $table->unique('license_plate', 'unique_license_plate');
        });

        // ===== GROUPS TABLE OPTIMIZATIONS =====
        if (Schema::hasTable('groups')) {
            Schema::table('groups', function (Blueprint $table) {
                $table->index('can_have_guests', 'idx_groups_can_have_guests');
                $table->index('name', 'idx_groups_name');
            });
        }

        // ===== SUBGROUPS TABLE OPTIMIZATIONS =====
        if (Schema::hasTable('subgroups')) {
            Schema::table('subgroups', function (Blueprint $table) {
                $table->index(['group_id', 'name'], 'idx_subgroups_group_name');
            });
        }

        // ===== NEUE CACHE TABLE (Optional) =====
        if (!Schema::hasTable('band_statistics_cache')) {
            Schema::create('band_statistics_cache', function (Blueprint $table) {
                $table->id();
                $table->string('cache_key')->unique();
                $table->json('data');
                $table->timestamp('expires_at');
                $table->timestamps();

                $table->index(['cache_key', 'expires_at'], 'idx_cache_key_expires');
            });
        }

        // ===== PERFORMANCE VIEWS (PostgreSQL/MySQL 8+) =====
        if (
            config('database.default') === 'pgsql' ||
            (config('database.default') === 'mysql' && version_compare(DB::select('SELECT VERSION() as version')[0]->version, '8.0.0', '>='))
        ) {

            // View für Band-Statistiken
            DB::statement('
                CREATE OR REPLACE VIEW band_statistics_view AS
                SELECT 
                    b.id,
                    b.band_name,
                    b.year,
                    s.name as stage_name,
                    COUNT(p.id) as total_members,
                    COUNT(CASE WHEN p.present = true THEN 1 END) as present_members,
                    COUNT(vp.id) as total_vehicles,
                    SUM(COALESCE(p.voucher_day_1, 0) + COALESCE(p.voucher_day_2, 0) + COALESCE(p.voucher_day_3, 0) + COALESCE(p.voucher_day_4, 0)) as total_voucher_value,
                    b.all_present,
                    b.travel_costs
                FROM bands b
                LEFT JOIN stages s ON b.stage_id = s.id
                LEFT JOIN persons p ON b.id = p.band_id AND p.is_duplicate = false
                LEFT JOIN vehicle_plates vp ON b.id = vp.band_id
                GROUP BY b.id, b.band_name, b.year, s.name, b.all_present, b.travel_costs
            ');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // ===== VIEWS LÖSCHEN =====
        if (
            config('database.default') === 'pgsql' ||
            (config('database.default') === 'mysql' && version_compare(DB::select('SELECT VERSION() as version')[0]->version, '8.0.0', '>='))
        ) {
            DB::statement('DROP VIEW IF EXISTS band_statistics_view');
        }

        // ===== CACHE TABLE LÖSCHEN =====
        Schema::dropIfExists('band_statistics_cache');

        // ===== INDEXE LÖSCHEN =====

        // Stages Indexe
        Schema::table('stages', function (Blueprint $table) {
            // Spalten entfernen (vorsichtig - nur wenn sicher dass sie nicht anderweitig genutzt werden)
            if (Schema::hasColumn('stages', 'voucher_day_4')) {
                $table->dropColumn(['voucher_day_1', 'voucher_day_2', 'voucher_day_3', 'voucher_day_4']);
            }
            if (Schema::hasColumn('stages', 'voucher_amount')) {
                $table->dropColumn('voucher_amount');
            }
            if (Schema::hasColumn('stages', 'backstage_all_days')) {
                $table->dropColumn('backstage_all_days');
            }
            if (Schema::hasColumn('stages', 'max_bands')) {
                $table->dropColumn('max_bands');
            }
        });

        // Bands Indexe
        Schema::table('bands', function (Blueprint $table) {
            $table->dropIndex('idx_bands_year_name');
            $table->dropIndex('idx_bands_stage_year');
            $table->dropIndex('idx_bands_all_present');
            $table->dropIndex('idx_bands_created_at');
        });

        // Persons Indexe
        Schema::table('persons', function (Blueprint $table) {
            $table->dropIndex('idx_persons_band_present');
            $table->dropIndex('idx_persons_band_duplicate');
            $table->dropIndex('idx_persons_year_duplicate');
            $table->dropIndex('idx_persons_responsible');
            $table->dropIndex('idx_persons_band_voucher1');
            $table->dropIndex('idx_persons_group_present');
        });

        // Vehicle Plates Indexe
        Schema::table('vehicle_plates', function (Blueprint $table) {
            $table->dropIndex('idx_vehicle_plates_license');
            $table->dropIndex('idx_vehicle_plates_band');
            $table->dropIndex('idx_vehicle_plates_person');
        });

        // Groups Indexe
        if (Schema::hasTable('groups')) {
            Schema::table('groups', function (Blueprint $table) {
                $table->dropIndex('idx_groups_can_have_guests');
                $table->dropIndex('idx_groups_name');
            });
        }

        // Subgroups Indexe
        if (Schema::hasTable('subgroups')) {
            Schema::table('subgroups', function (Blueprint $table) {
                $table->dropIndex('idx_subgroups_group_name');
            });
        }
    }

    /**
     * Seeder für Standard-Werte nach der Migration
     */
    private function seedDefaultValues(): void
    {
        // Standard Voucher-Beträge für existierende Stages setzen
        DB::table('stages')->whereNull('voucher_amount')->update([
            'voucher_amount' => 15.00, // Beispiel: 15€ Standard
            'voucher_day_1' => 15.00,
            'voucher_day_2' => 15.00,
            'voucher_day_3' => 15.00,
            'voucher_day_4' => 15.00,
            'backstage_all_days' => false,
            'max_bands' => 20 // Beispiel: Max 20 Bands pro Bühne
        ]);

        // Main Stage bekommt alle Tage Backstage
        DB::table('stages')
            ->where('name', 'LIKE', '%Main%')
            ->orWhere('name', 'LIKE', '%Haupt%')
            ->update(['backstage_all_days' => true]);
    }
};
