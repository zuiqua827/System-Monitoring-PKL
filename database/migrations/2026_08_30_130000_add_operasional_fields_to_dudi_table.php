<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add operational configuration fields to the dudi table.
 *
 * These fields allow each DUDI to define their own attendance rules:
 * - batas_terlambat: deadline for "on time" check-in (after jam_masuk, before this = still on time)
 * - batas_sangat_terlambat: deadline for "late" (after batas_terlambat, before this = late; after = very late)
 * - hari_operasional: JSON array of active working day names (e.g. ["senin","selasa",...])
 *
 * All fields are nullable with safe defaults so existing DUDI data is not affected.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dudi', function (Blueprint $table) {
            $table->time('batas_terlambat')->nullable()->default('08:15:00')->after('toleransi_keterlambatan');
            $table->time('batas_sangat_terlambat')->nullable()->default('09:00:00')->after('batas_terlambat');
            $table->json('hari_operasional')->nullable()->after('batas_sangat_terlambat');
        });
    }

    public function down(): void
    {
        Schema::table('dudi', function (Blueprint $table) {
            $table->dropColumn(['batas_terlambat', 'batas_sangat_terlambat', 'hari_operasional']);
        });
    }
};
