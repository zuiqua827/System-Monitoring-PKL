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
     * Adds SIPINTU identity and manual SSO approval fields to dudi table.
     * sso_enabled is strictly false by default and requires Super Admin manual approval.
     */
    public function up(): void
    {
        Schema::table('dudi', function (Blueprint $table): void {
            $table->string('sipintu_user_id', 100)->nullable()->unique()->after('user_id');
            $table->string('sipintu_email')->nullable()->after('sipintu_user_id');
            $table->boolean('sso_enabled')->default(false)->after('status_aktif');
            $table->timestamp('sipintu_synced_at')->nullable()->after('sso_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dudi', function (Blueprint $table): void {
            $table->dropUnique(['sipintu_user_id']);
            $table->dropColumn([
                'sipintu_user_id',
                'sipintu_email',
                'sso_enabled',
                'sipintu_synced_at',
            ]);
        });
    }
};
