<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ModeKerja;
use Database\Factories\PenempatanPKLFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $periode_pkl_id
 * @property int $guru_id
 * @property int $dudi_id
 * @property int $siswa_id
 * @property int|null $dibuat_oleh
 * @property int|null $approved_by
 * @property string|null $nomor_surat
 * @property Carbon|null $tanggal_mulai
 * @property Carbon|null $tanggal_selesai
 * @property string $status
 * @property string $mode_kerja
 * @property bool $validasi_lokasi
 * @property string|null $wfh_latitude
 * @property string|null $wfh_longitude
 * @property int|null $wfh_radius
 * @property string|null $wfh_alamat
 * @property Carbon|null $approved_at
 * @property string|null $catatan
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read PeriodePKL $periodePKL
 * @property-read Guru $guru
 * @property-read Dudi $dudi
 * @property-read Siswa $siswa
 * @property-read User|null $dibuatOleh
 * @property-read User|null $approvedBy
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Absensi> $absensi
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Aktivitas> $aktivitas
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PenempatanPKLLocationLog> $locationLogs
 * @property-read Laporan|null $laporan
 * @property-read Penilaian|null $penilaian
 */
class PenempatanPKL extends Model
{
    /** @use HasFactory<PenempatanPKLFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'penempatan_pkl';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'periode_pkl_id',
        'guru_id',
        'dudi_id',
        'siswa_id',
        'dibuat_oleh',
        'approved_by',
        'nomor_surat',
        'tanggal_mulai',
        'tanggal_selesai',
        'status',
        'mode_kerja',
        'validasi_lokasi',
        'wfh_latitude',
        'wfh_longitude',
        'wfh_radius',
        'wfh_alamat',
        'approved_at',
        'catatan',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'periode_pkl_id' => 'integer',
            'guru_id' => 'integer',
            'dudi_id' => 'integer',
            'siswa_id' => 'integer',
            'dibuat_oleh' => 'integer',
            'approved_by' => 'integer',
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'validasi_lokasi' => 'boolean',
            'wfh_latitude' => 'decimal:7',
            'wfh_longitude' => 'decimal:7',
            'wfh_radius' => 'integer',
            'approved_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<PeriodePKL, $this> */
    public function periodePKL(): BelongsTo
    {
        return $this->belongsTo(PeriodePKL::class, 'periode_pkl_id', 'id');
    }

    /** @return BelongsTo<Guru, $this> */
    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class);
    }

    /** @return BelongsTo<Dudi, $this> */
    public function dudi(): BelongsTo
    {
        return $this->belongsTo(Dudi::class);
    }

    /** @return BelongsTo<Siswa, $this> */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    /** @return BelongsTo<User, $this> */
    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    /** @return BelongsTo<User, $this> */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ============================================================
    // RELASI DATA OPERASIONAL PKL (OPERATIONAL MONITORING RELATIONS)
    // ============================================================
    // Seluruh catatan kegiatan siswa (absensi, jurnal aktivitas, penilaian)
    // diikat pada record Penempatan PKL ini, bukan berdiri sendiri.

    /**
     * Riwayat absensi harian siswa selama masa penempatan ini.
     * @return HasMany<Absensi, $this>
     */
    public function absensi(): HasMany
    {
        return $this->hasMany(Absensi::class, 'penempatan_pkl_id', 'id');
    }

    /**
     * Catatan jurnal aktivitas harian siswa selama penempatan ini.
     * @return HasMany<Aktivitas, $this>
     */
    public function aktivitas(): HasMany
    {
        return $this->hasMany(Aktivitas::class, 'penempatan_pkl_id', 'id');
    }

    /**
     * Lembar nilai akhir PKL yang diisi oleh pembimbing DUDI.
     * @return HasOne<Penilaian, $this>
     */
    public function penilaian(): HasOne
    {
        return $this->hasOne(Penilaian::class, 'penempatan_pkl_id');
    }

    /**
     * Riwayat perubahan pengaturan mode kerja dan lokasi WFO/WFH.
     *
     * @return HasMany<PenempatanPKLLocationLog, $this>
     */
    public function locationLogs(): HasMany
    {
        return $this->hasMany(PenempatanPKLLocationLog::class, 'penempatan_pkl_id', 'id')
            ->latest('created_at')
            ->latest('id');
    }

    public function getEffectiveModeKerja(): string
    {
        $raw = strtoupper((string) ($this->mode_kerja ?? ModeKerja::WFO->value));

        return $raw === ModeKerja::WFH->value
            ? ModeKerja::WFH->value
            : ModeKerja::WFO->value;
    }

    public function getEffectiveWfhRadius(): int
    {
        return max(10, (int) ($this->wfh_radius ?: 100));
    }

    /**
     * Berkas laporan akhir PKL siswa.
     * @return HasOne<Laporan, $this>
     */
    public function laporan(): HasOne
    {
        return $this->hasOne(Laporan::class, 'penempatan_pkl_id');
    }
}
