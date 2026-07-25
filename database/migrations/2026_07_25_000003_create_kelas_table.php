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
        Schema::create('kelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jurusan_id')->index()->constrained('jurusan')->restrictOnDelete();
            $table->string('nama', 100);
            $table->unsignedTinyInteger('tingkat');
            $table->string('tahun_ajaran', 9);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['jurusan_id', 'nama', 'tahun_ajaran']);
            $table->index(['tingkat', 'tahun_ajaran']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kelas');
    }
};
