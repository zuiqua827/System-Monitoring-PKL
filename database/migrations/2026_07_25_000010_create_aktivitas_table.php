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
        Schema::create('aktivitas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penempatan_pkl_id')->index()->constrained('penempatan_pkl')->cascadeOnDelete();
            $table->date('tanggal');
            $table->string('judul');
            $table->text('deskripsi');
            $table->enum('status', ['draft', 'dikirim', 'disetujui', 'ditolak'])->default('draft');
            $table->text('catatan_reviewer')->nullable();
            $table->timestamp('dikirim_pada')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['penempatan_pkl_id', 'tanggal']);
            $table->index(['status', 'tanggal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aktivitas');
    }
};
