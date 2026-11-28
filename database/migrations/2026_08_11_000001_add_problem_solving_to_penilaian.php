<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('penilaian', function (Blueprint $table) {
            $table->unsignedTinyInteger('nilai_problem_solving')->nullable()->after('nilai_komunikasi');
        });

        // Map data lama dari tanggung_jawab ke problem_solving
        DB::statement('UPDATE penilaian SET nilai_problem_solving = nilai_tanggung_jawab');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penilaian', function (Blueprint $table) {
            $table->dropColumn('nilai_problem_solving');
        });
    }
};
