<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Table to store the history of SiPintu student synchronization runs.
     * Only student data is synchronized (never Guru/DUDI/Admin/PKL modules).
     */
    public function up(): void
    {
        Schema::create('sipintu_sync_logs', function (Blueprint $table): void {
            $table->id();

            // Admin who triggered the sync (nullable for scheduled runs)
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('admin_name')->nullable();

            // Result summary
            $table->string('status')->default('success'); // success | failed
            $table->unsignedInteger('added')->default(0);
            $table->unsignedInteger('updated')->default(0);
            $table->unsignedInteger('deleted')->default(0);
            $table->unsignedInteger('skipped')->default(0);

            // Timing
            $table->unsignedInteger('duration_ms')->default(0);

            // Optional error message (only on failure)
            $table->text('message')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sipintu_sync_logs');
    }
};
