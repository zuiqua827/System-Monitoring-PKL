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
            $table->string('kecamatan')->nullable()->after('alamat');
            $table->string('kabupaten')->nullable()->after('kecamatan');
            $table->string('provinsi')->nullable()->after('kabupaten');
            $table->decimal('latitude', 10, 8)->nullable()->after('provinsi');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            $table->boolean('status_aktif')->default(true)->after('longitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dudi', function (Blueprint $table) {
            $table->dropColumn([
                'kecamatan',
                'kabupaten',
                'provinsi',
                'latitude',
                'longitude',
                'status_aktif',
            ]);
        });
    }
};
