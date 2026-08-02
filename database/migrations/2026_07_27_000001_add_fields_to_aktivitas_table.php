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
     * This migration adds missing fields to the aktivitas table:
     * - jam_mulai, jam_selesai (time)
     * - hasil, kendala, solusi (text)
     * - foto_kegiatan (string path)
     * - catatan_guru (text)
     * - validated_by (FK to users)
     * - validated_at (timestamp)
     *
     * Also updates the status ENUM to match the required values.
     */
    public function up(): void
    {
        // Add new columns
        Schema::table('aktivitas', function (Blueprint $table) {
            $table->time('jam_mulai')->nullable()->after('tanggal');
            $table->time('jam_selesai')->nullable()->after('jam_mulai');
            $table->text('hasil')->nullable()->after('deskripsi');
            $table->text('kendala')->nullable()->after('hasil');
            $table->text('solusi')->nullable()->after('kendala');
            $table->string('foto_kegiatan')->nullable()->after('solusi');
            $table->text('catatan_guru')->nullable()->after('catatan_reviewer');
            $table->foreignId('validated_by')
                ->nullable()
                ->after('catatan_guru')
                ->index()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->timestamp('validated_at')->nullable()->after('approved_at');
        });

        // Update the ENUM to use required status values: draft, menunggu_validasi, disetujui, ditolak
        DB::statement("ALTER TABLE aktivitas MODIFY COLUMN status ENUM('draft', 'menunggu_validasi', 'disetujui', 'ditolak') NOT NULL DEFAULT 'draft'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aktivitas', function (Blueprint $table) {
            $table->dropColumn([
                'jam_mulai',
                'jam_selesai',
                'hasil',
                'kendala',
                'solusi',
                'foto_kegiatan',
                'catatan_guru',
                'validated_by',
                'validated_at',
            ]);
        });

        // Revert status ENUM
        DB::statement("ALTER TABLE aktivitas MODIFY COLUMN status ENUM('draft', 'dikirim', 'disetujui', 'ditolak') NOT NULL DEFAULT 'draft'");
    }
};

