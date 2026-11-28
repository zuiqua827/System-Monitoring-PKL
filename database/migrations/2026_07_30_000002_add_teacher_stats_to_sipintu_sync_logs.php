<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add teacher synchronization statistics to the sync history table.
     * Students remain tracked in added/updated/deleted/skipped; teachers are
     * tracked in the new teacher_* columns.
     */
    public function up(): void
    {
        Schema::table('sipintu_sync_logs', function (Blueprint $table): void {
            $table->unsignedInteger('teacher_added')->default(0)->after('skipped');
            $table->unsignedInteger('teacher_updated')->default(0)->after('teacher_added');
            $table->unsignedInteger('teacher_deleted')->default(0)->after('teacher_updated');
            $table->unsignedInteger('teacher_skipped')->default(0)->after('teacher_deleted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sipintu_sync_logs', function (Blueprint $table): void {
            $table->dropColumn(['teacher_added', 'teacher_updated', 'teacher_deleted', 'teacher_skipped']);
        });
    }
};
