<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * 1. Adds sipintu_id (master company ID from SiPintu) to the dudi table.
     * 2. Makes user_id nullable in dudi table so master companies can exist without mandatory immediate user accounts.
     * 3. Adds dudi synchronization statistics to sipintu_sync_logs table.
     */
    public function up(): void
    {
        Schema::table('dudi', function (Blueprint $table): void {
            $table->string('sipintu_id', 100)->nullable()->unique()->after('id');
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });

        Schema::table('sipintu_sync_logs', function (Blueprint $table): void {
            $table->unsignedInteger('dudi_added')->default(0)->after('teacher_skipped');
            $table->unsignedInteger('dudi_updated')->default(0)->after('dudi_added');
            $table->unsignedInteger('dudi_deleted')->default(0)->after('dudi_updated');
            $table->unsignedInteger('dudi_skipped')->default(0)->after('dudi_deleted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('dudi')) {
            if (Schema::hasColumn('dudi', 'sipintu_id')) {
                Schema::table('dudi', function (Blueprint $table): void {
                    $table->dropUnique(['sipintu_id']);
                });

                Schema::table('dudi', function (Blueprint $table): void {
                    $table->dropColumn(['sipintu_id']);
                });
            }

            if (Schema::hasColumn('dudi', 'user_id') && DB::table('dudi')->whereNull('user_id')->doesntExist()) {
                Schema::table('dudi', function (Blueprint $table): void {
                    $table->unsignedBigInteger('user_id')->nullable(false)->change();
                });
            }
        }

        $statsColumns = array_values(array_filter(
            ['dudi_added', 'dudi_updated', 'dudi_deleted', 'dudi_skipped'],
            fn (string $column): bool => Schema::hasColumn('sipintu_sync_logs', $column),
        ));

        if ($statsColumns !== []) {
            Schema::table('sipintu_sync_logs', function (Blueprint $table) use ($statsColumns): void {
                $table->dropColumn($statsColumns);
            });
        }
    }
};
