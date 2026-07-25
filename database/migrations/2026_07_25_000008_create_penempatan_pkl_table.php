<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('penempatan_pkl', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periode_pkl_id')->index()->constrained('periode_pkl')->restrictOnDelete();
            $table->foreignId('guru_id')->index()->constrained('guru')->restrictOnDelete();
            $table->foreignId('dudi_id')->index()->constrained('dudi')->restrictOnDelete();
            $table->foreignId('siswa_id')->index()->constrained('siswa')->restrictOnDelete();
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->enum('status', ['pending', 'aktif', 'selesai', 'dibatalkan'])->default('pending');
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['periode_pkl_id', 'siswa_id'], 'penempatan_pkl_periode_siswa_unique');
            $table->index(['guru_id', 'status']);
            $table->index(['dudi_id', 'status']);
            $table->index(['status', 'tanggal_mulai', 'tanggal_selesai'], 'penempatan_pkl_status_dates_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penempatan_pkl');
    }
};
