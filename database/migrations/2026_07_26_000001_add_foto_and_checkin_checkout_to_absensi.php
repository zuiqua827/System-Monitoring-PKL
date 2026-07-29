<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration adds:
     * - foto_masuk and foto_pulang columns for Check In/Out photos
     * - Adds 'terlambat' to the status enum
     * - Adds lokasi_masuk and lokasi_pulang text columns for location descriptions
     */
    public function up(): void
    {
        // Add new columns
        Schema::table('absensi', function (Blueprint $table) {
            // Photo columns
            $table->string('foto_masuk')->nullable()->after('longitude_keluar');
            $table->string('foto_pulang')->nullable()->after('foto_masuk');

            // Location description columns (human-readable address)
            $table->text('lokasi_masuk')->nullable()->after('foto_pulang');
            $table->text('lokasi_pulang')->nullable()->after('lokasi_masuk');
        });

        // Update the ENUM to include 'terlambat'
        // MySQL requires altering the column to change ENUM values
        DB::statement("ALTER TABLE absensi MODIFY COLUMN status ENUM('hadir', 'terlambat', 'izin', 'sakit', 'alpha') NOT NULL DEFAULT 'hadir'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('absensi', function (Blueprint $table) {
            $table->dropColumn(['foto_masuk', 'foto_pulang', 'lokasi_masuk', 'lokasi_pulang']);
        });

        // Revert status ENUM to original
        DB::statement("ALTER TABLE absensi MODIFY COLUMN status ENUM('hadir', 'izin', 'sakit', 'alpha') NOT NULL DEFAULT 'hadir'");
    }
};

