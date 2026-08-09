<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Maps each unique SiPintu opaque classroom_id to a local kelas record.
     * A single classroom_id can map to only ONE local kelas (UNIQUE).
     * This mapping is used by the Admin mapping page and is reused by all
     * future SiPintu synchronizations.
     */
    public function up(): void
    {
        Schema::create('sipintu_classroom_mappings', function (Blueprint $table): void {
            $table->id();

            // SiPintu opaque classroom identifier (integer 1-60).
            $table->unsignedInteger('classroom_id')->unique();

            // Local kelas this classroom maps to.
            $table->foreignId('kelas_id')
                ->constrained('kelas')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Audit trail.
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

            $table->index('kelas_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sipintu_classroom_mappings');
    }
};
