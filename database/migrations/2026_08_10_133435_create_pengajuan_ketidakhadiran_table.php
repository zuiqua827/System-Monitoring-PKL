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
        Schema::create('pengajuan_ketidakhadiran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penempatan_pkl_id')
                ->index()
                ->constrained('penempatan_pkl')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->date('tanggal');
            $table->enum('jenis', ['izin', 'sakit']);
            $table->text('alasan');
            $table->string('lampiran')->nullable();
            $table->enum('status', ['menunggu', 'disetujui', 'ditolak'])->default('menunggu');
            $table->foreignId('validated_by')
                ->nullable()
                ->index()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->text('catatan_validasi')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Hindari duplikasi pengajuan di hari yang sama untuk penempatan yang sama
            $table->unique(['penempatan_pkl_id', 'tanggal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengajuan_ketidakhadiran');
    }
};
