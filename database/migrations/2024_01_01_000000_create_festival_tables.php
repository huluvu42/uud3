<?php
// database/migrations/2024_01_01_000000_create_festival_tables.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Users table (erweitert für Festival-Benutzer)
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('password');
            $table->string('first_name');
            $table->string('last_name');
            $table->boolean('is_admin')->default(false);
            $table->boolean('can_reset_changes')->default(false);
            $table->timestamps();
        });

        // Einstellungen
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->date('day_1_date');
            $table->date('day_2_date');
            $table->date('day_3_date');
            $table->date('day_4_date');
            $table->string('wristband_color_day_1');
            $table->string('wristband_color_day_2');
            $table->string('wristband_color_day_3');
            $table->string('wristband_color_day_4');
            $table->year('year');
            $table->timestamps();
        });

        // Feldbezeichnungen
        Schema::create('field_labels', function (Blueprint $table) {
            $table->id();
            $table->string('field_key');
            $table->string('label');
            $table->timestamps();
        });

        // Bühnen
        Schema::create('stages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('presence_days', ['performance_day', 'all_days']);
            $table->boolean('guest_allowed')->default(false);
            $table->decimal('vouchers_on_performance_day', 3, 1)->default(0.0);
            $table->year('year');
            $table->timestamps();
        });

        // Gruppen
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('backstage_day_1')->default(false);
            $table->boolean('backstage_day_2')->default(false);
            $table->boolean('backstage_day_3')->default(false);
            $table->boolean('backstage_day_4')->default(false);
            $table->decimal('voucher_day_1', 3, 1)->default(0.0);
            $table->decimal('voucher_day_2', 3, 1)->default(0.0);
            $table->decimal('voucher_day_3', 3, 1)->default(0.0);
            $table->decimal('voucher_day_4', 3, 1)->default(0.0);
            $table->text('remarks')->nullable();
            $table->year('year');
            $table->timestamps();
        });

        // Untergruppen
        Schema::create('subgroups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('group_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        // Bands
        Schema::create('bands', function (Blueprint $table) {
            $table->id();
            $table->string('band_name');
            $table->boolean('plays_day_1')->default(false);
            $table->boolean('plays_day_2')->default(false);
            $table->boolean('plays_day_3')->default(false);
            $table->boolean('plays_day_4')->default(false);
            $table->foreignId('stage_id')->nullable()->constrained()->onDelete('set null');
            $table->boolean('all_present')->default(false);
            $table->decimal('travel_costs', 8, 2)->default(0.00);
            $table->year('year');
            $table->timestamps();
        });

        // KFZ Kennzeichen
        Schema::create('vehicle_plates', function (Blueprint $table) {
            $table->id();
            $table->string('license_plate');
            $table->foreignId('band_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        // Personen
        Schema::create('persons', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->boolean('present')->default(false);
            $table->boolean('backstage_day_1')->default(false);
            $table->boolean('backstage_day_2')->default(false);
            $table->boolean('backstage_day_3')->default(false);
            $table->boolean('backstage_day_4')->default(false);
            $table->decimal('voucher_day_1', 3, 1)->default(0.0);
            $table->decimal('voucher_day_2', 3, 1)->default(0.0);
            $table->decimal('voucher_day_3', 3, 1)->default(0.0);
            $table->decimal('voucher_day_4', 3, 1)->default(0.0);
            $table->decimal('voucher_issued_day_1', 3, 1)->default(0.0);
            $table->decimal('voucher_issued_day_2', 3, 1)->default(0.0);
            $table->decimal('voucher_issued_day_3', 3, 1)->default(0.0);
            $table->decimal('voucher_issued_day_4', 3, 1)->default(0.0);
            $table->text('remarks')->nullable();
            $table->foreignId('group_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('subgroup_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('band_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('responsible_person_id')->nullable()->constrained('persons')->onDelete('set null');
            $table->year('year');
            $table->timestamps();
        });

        // Bandgäste
        Schema::create('band_guests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('present')->default(false);
            $table->foreignId('band_member_id')->constrained('persons')->onDelete('cascade');
            $table->timestamps();
        });

        // Bonkauf
        Schema::create('voucher_purchases', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount', 3, 1);
            $table->integer('day'); // 1-4
            $table->date('purchase_date');
            $table->foreignId('stage_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        // Änderungslog
        Schema::create('change_logs', function (Blueprint $table) {
            $table->id();
            $table->string('table_name');
            $table->unsignedBigInteger('record_id');
            $table->string('field_name');
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->string('action'); // create, update, delete
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('change_logs');
        Schema::dropIfExists('voucher_purchases');
        Schema::dropIfExists('band_guests');
        Schema::dropIfExists('vehicle_plates');
        Schema::dropIfExists('persons');
        Schema::dropIfExists('bands');
        Schema::dropIfExists('subgroups');
        Schema::dropIfExists('groups');
        Schema::dropIfExists('stages');
        Schema::dropIfExists('field_labels');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('users');
    }
};