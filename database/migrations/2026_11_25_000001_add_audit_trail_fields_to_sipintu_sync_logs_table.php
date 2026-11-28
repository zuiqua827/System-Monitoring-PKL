<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Safely extends sipintu_sync_logs with full audit trail metadata:
     * execution start/finish timestamps, entity scope, actual execution counts
     * (remote_total, unchanged, needs_mapping, conflicts, duplicates, invalid,
     * failed), structured per-entity summary JSON, and detailed error reasons.
     */
    public function up(): void
    {
        Schema::table('sipintu_sync_logs', function (Blueprint $table): void {
            $table->timestamp('started_at')->nullable()->after('admin_name');
            $table->timestamp('finished_at')->nullable()->after('started_at');
            $table->string('entity_scope', 50)->default('all')->after('status');
            $table->unsignedInteger('remote_total')->default(0)->after('entity_scope');
            $table->unsignedInteger('unchanged')->default(0)->after('skipped');
            $table->unsignedInteger('needs_mapping')->default(0)->after('unchanged');
            $table->unsignedInteger('conflicts')->default(0)->after('needs_mapping');
            $table->unsignedInteger('duplicates')->default(0)->after('conflicts');
            $table->unsignedInteger('invalid')->default(0)->after('duplicates');
            $table->unsignedInteger('failed')->default(0)->after('invalid');
            $table->json('summary')->nullable()->after('duration_ms');
            $table->json('error_details')->nullable()->after('summary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sipintu_sync_logs', function (Blueprint $table): void {
            $table->dropColumn([
                'started_at',
                'finished_at',
                'entity_scope',
                'remote_total',
                'unchanged',
                'needs_mapping',
                'conflicts',
                'duplicates',
                'invalid',
                'failed',
                'summary',
                'error_details',
            ]);
        });
    }
};
