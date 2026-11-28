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
        Schema::create('laporan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penempatan_pkl_id')
                ->unique()
                ->constrained('penempatan_pkl')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('validated_by')
                ->nullable()
                ->index()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->string('judul');
            $table->unsignedInteger('version')->default(1);
            $table->longText('isi')->nullable();
            $table->string('file_path')->nullable();
            $table->enum('status', ['draft', 'dikirim', 'direvisi', 'disetujui'])->default('draft');
            $table->timestamp('dikumpulkan_pada')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'dikumpulkan_pada']);
            $table->index(['validated_by', 'validated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporan');
    }
};
