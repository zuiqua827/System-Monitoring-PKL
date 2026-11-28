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
        Schema::create('penilaian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penempatan_pkl_id')
                ->unique()
                ->constrained('penempatan_pkl')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('dinilai_oleh')
                ->nullable()
                ->index()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->unsignedTinyInteger('nilai_sikap')->nullable();
            $table->unsignedTinyInteger('nilai_keterampilan')->nullable();
            $table->unsignedTinyInteger('nilai_pengetahuan')->nullable();
            $table->unsignedTinyInteger('nilai_akhir')->nullable();
            $table->string('predikat', 5)->nullable();
            $table->enum('status', ['draft', 'final'])->default('draft');
            $table->date('tanggal_penilaian')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['nilai_akhir', 'predikat']);
            $table->index(['status', 'tanggal_penilaian']);
            $table->index(['dinilai_oleh', 'tanggal_penilaian']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penilaian');
    }
};
