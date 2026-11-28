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
            $table->foreignId('periode_pkl_id')
                ->index()
                ->constrained('periode_pkl')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('guru_id')
                ->index()
                ->constrained('guru')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('dudi_id')
                ->index()
                ->constrained('dudi')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('siswa_id')
                ->index()
                ->constrained('siswa')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('dibuat_oleh')
                ->nullable()
                ->index()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->foreignId('approved_by')
                ->nullable()
                ->index()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->string('nomor_surat')->nullable()->unique();
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->enum('status', ['pending', 'aktif', 'selesai', 'dibatalkan'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['periode_pkl_id', 'siswa_id'], 'penempatan_pkl_periode_siswa_unique');
            $table->index(['guru_id', 'status']);
            $table->index(['dudi_id', 'status']);
            $table->index(['approved_by', 'approved_at']);
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
