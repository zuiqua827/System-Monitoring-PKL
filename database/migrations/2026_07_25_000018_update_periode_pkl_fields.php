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
        Schema::table('periode_pkl', function (Blueprint $table) {
            // Change semester (string) to keterangan (text nullable)
            $table->dropColumn('semester');
            $table->text('keterangan')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('periode_pkl', function (Blueprint $table) {
            $table->dropColumn('keterangan');
            $table->enum('semester', ['ganjil', 'genap'])->after('tahun_ajaran');
        });
    }
};
