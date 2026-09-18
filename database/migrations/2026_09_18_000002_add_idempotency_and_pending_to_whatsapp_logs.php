<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('whatsapp_logs', 'idempotency_key')) {
            Schema::table('whatsapp_logs', function (Blueprint $table) {
                $table->string('idempotency_key', 64)->nullable()->unique()->after('id');
            });
        }

        // Update status enum to include pending and sending on MySQL
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE whatsapp_logs MODIFY COLUMN status ENUM('pending', 'sending', 'sent', 'failed', 'skipped') NOT NULL DEFAULT 'pending'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE whatsapp_logs MODIFY COLUMN status ENUM('sent', 'failed', 'skipped') NOT NULL DEFAULT 'sent'");
        }

        if (Schema::hasColumn('whatsapp_logs', 'idempotency_key')) {
            Schema::table('whatsapp_logs', function (Blueprint $table) {
                $table->dropColumn('idempotency_key');
            });
        }
    }
};
