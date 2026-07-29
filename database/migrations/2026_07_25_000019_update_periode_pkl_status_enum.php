<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('periode_pkl', function (Blueprint $table) {
            $table->dropIndex(['status', 'tanggal_mulai', 'tanggal_selesai']);
        });

        // Modify ENUM to use new status values: Persiapan, Aktif, Selesai, Ditutup
        DB::statement("ALTER TABLE periode_pkl MODIFY COLUMN status ENUM('Persiapan', 'Aktif', 'Selesai', 'Ditutup') NOT NULL DEFAULT 'Persiapan'");

        Schema::table('periode_pkl', function (Blueprint $table) {
            $table->index(['status', 'tanggal_mulai', 'tanggal_selesai'], 'idx_periode_pkl_status_dates');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('periode_pkl', function (Blueprint $table) {
            $table->dropIndex('idx_periode_pkl_status_dates');
        });

        DB::statement("ALTER TABLE periode_pkl MODIFY COLUMN status ENUM('draft', 'aktif', 'selesai') NOT NULL DEFAULT 'draft'");

        Schema::table('periode_pkl', function (Blueprint $table) {
            $table->index(['status', 'tanggal_mulai', 'tanggal_selesai']);
        });
    }
};
