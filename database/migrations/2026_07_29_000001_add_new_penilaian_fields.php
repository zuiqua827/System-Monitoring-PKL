<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add 7 new nilai fields for comprehensive penilaian PKL,
     * replace old 3-field structure, add non-integer nilai_akhir,
     * add catatan_guru.
     */
    public function up(): void
    {
        Schema::table('penilaian', function (Blueprint $table): void {
            // Add 7 new nilai fields (0-100)
            $table->unsignedTinyInteger('nilai_disiplin')->nullable()->after('dinilai_oleh');
            $table->unsignedTinyInteger('nilai_kehadiran')->nullable()->after('nilai_disiplin');
            $table->unsignedTinyInteger('nilai_tanggung_jawab')->nullable()->after('nilai_kehadiran');
            $table->unsignedTinyInteger('nilai_komunikasi')->nullable()->after('nilai_tanggung_jawab');
            $table->unsignedTinyInteger('nilai_kerjasama')->nullable()->after('nilai_komunikasi');
            $table->unsignedTinyInteger('nilai_inisiatif')->nullable()->after('nilai_kerjasama');
            $table->unsignedTinyInteger('nilai_teknis')->nullable()->after('nilai_inisiatif');

            // Change nilai_akhir to decimal for precise average calculation
            $table->decimal('nilai_akhir', 5, 2)->nullable()->change();

            // Add catatan_guru
            $table->text('catatan_guru')->nullable()->after('catatan');

            // Update status enum to include 'final'
            $table->enum('status', ['draft', 'final'])->default('draft')->change();

            // Indexes for new fields
            $table->index(['nilai_disiplin', 'nilai_kehadiran', 'nilai_tanggung_jawab'], 'penilaian_nilai_1_idx');
            $table->index(['nilai_komunikasi', 'nilai_kerjasama', 'nilai_inisiatif', 'nilai_teknis'], 'penilaian_nilai_2_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penilaian', function (Blueprint $table): void {
            $table->dropIndex('penilaian_nilai_1_idx');
            $table->dropIndex('penilaian_nilai_2_idx');
            $table->dropColumn([
                'nilai_disiplin',
                'nilai_kehadiran',
                'nilai_tanggung_jawab',
                'nilai_komunikasi',
                'nilai_kerjasama',
                'nilai_inisiatif',
                'nilai_teknis',
                'catatan_guru',
            ]);
            $table->unsignedTinyInteger('nilai_akhir')->nullable()->change();
            $table->string('status', 255)->default('draft')->change();
        });
    }
};
