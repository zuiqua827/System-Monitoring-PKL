<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add WFO/WFH work mode, geofence toggle, approved WFH coordinates,
     * attendance mode snapshots, and location change audit history.
     */
    public function up(): void
    {
        Schema::table('penempatan_pkl', function (Blueprint $table): void {
            if (! Schema::hasColumn('penempatan_pkl', 'mode_kerja')) {
                $table->string('mode_kerja', 10)->default('WFO')->after('status');
            }
            if (! Schema::hasColumn('penempatan_pkl', 'validasi_lokasi')) {
                $table->boolean('validasi_lokasi')->default(true)->after('mode_kerja');
            }
            if (! Schema::hasColumn('penempatan_pkl', 'wfh_latitude')) {
                $table->decimal('wfh_latitude', 10, 7)->nullable()->after('validasi_lokasi');
            }
            if (! Schema::hasColumn('penempatan_pkl', 'wfh_longitude')) {
                $table->decimal('wfh_longitude', 10, 7)->nullable()->after('wfh_latitude');
            }
            if (! Schema::hasColumn('penempatan_pkl', 'wfh_radius')) {
                $table->unsignedInteger('wfh_radius')->nullable()->after('wfh_longitude');
            }
            if (! Schema::hasColumn('penempatan_pkl', 'wfh_alamat')) {
                $table->string('wfh_alamat', 255)->nullable()->after('wfh_radius');
            }
        });

        Schema::table('absensi', function (Blueprint $table): void {
            if (! Schema::hasColumn('absensi', 'mode_kerja')) {
                $table->string('mode_kerja', 10)->nullable()->after('status');
            }
            if (! Schema::hasColumn('absensi', 'validasi_lokasi_aktif')) {
                $table->boolean('validasi_lokasi_aktif')->default(true)->after('lokasi_valid');
            }
        });

        if (! Schema::hasTable('penempatan_pkl_location_logs')) {
            Schema::create('penempatan_pkl_location_logs', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('penempatan_pkl_id')
                    ->constrained('penempatan_pkl')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();
                $table->foreignId('changed_by')
                    ->nullable()
                    ->constrained('users')
                    ->cascadeOnUpdate()
                    ->nullOnDelete();
                $table->string('changed_by_name');
                $table->string('changed_by_role', 50)->nullable();
                $table->json('old_values');
                $table->json('new_values');
                $table->text('reason')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamps();

                $table->index(['penempatan_pkl_id', 'created_at'], 'penempatan_location_logs_idx');
                $table->index(['changed_by', 'created_at'], 'penempatan_location_logs_actor_idx');
            });
        }
    }

    /**
     * Reverse the migrations cleanly.
     */
    public function down(): void
    {
        Schema::dropIfExists('penempatan_pkl_location_logs');

        Schema::table('absensi', function (Blueprint $table): void {
            foreach (['validasi_lokasi_aktif', 'mode_kerja'] as $column) {
                if (Schema::hasColumn('absensi', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('penempatan_pkl', function (Blueprint $table): void {
            foreach ([
                'wfh_alamat',
                'wfh_radius',
                'wfh_longitude',
                'wfh_latitude',
                'validasi_lokasi',
                'mode_kerja',
            ] as $column) {
                if (Schema::hasColumn('penempatan_pkl', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
