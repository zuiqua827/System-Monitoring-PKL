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
        Schema::table('dudi', function (Blueprint $table) {
            $table->time('jam_masuk')->default('07:00:00')->after('status_aktif');
            $table->time('jam_pulang')->default('15:00:00')->after('jam_masuk');
            $table->unsignedSmallInteger('toleransi_keterlambatan')->default(15)->after('jam_pulang');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dudi', function (Blueprint $table) {
            $table->dropColumn(['jam_masuk', 'jam_pulang', 'toleransi_keterlambatan']);
        });
    }
};
