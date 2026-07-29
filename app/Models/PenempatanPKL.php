<?php

declare(strict_types=1);

namespace App\Models;

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
            'approved_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function periodePKL(): BelongsTo
    {
        return $this->belongsTo(PeriodePKL::class, 'periode_pkl_id', 'id');
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class);
    }

    public function dudi(): BelongsTo
    {
        return $this->belongsTo(Dudi::class);
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

public function absensi(): HasMany
    {
        return $this->hasMany(Absensi::class, 'penempatan_pkl_id', 'id');
    }

    public function aktivitas(): HasMany
    {
        return $this->hasMany(Aktivitas::class, 'penempatan_pkl_id');
    }

    public function penilaian(): HasOne
    {
        return $this->hasOne(Penilaian::class, 'penempatan_pkl_id');
    }

    public function laporan(): HasOne
    {
        return $this->hasOne(Laporan::class, 'penempatan_pkl_id');
    }
}
