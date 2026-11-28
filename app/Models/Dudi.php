<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DudiFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Carbon\CarbonInterface;

/**
 * @property int $id
 * @property int $user_id
 * @property string $nama_perusahaan
 * @property string|null $penanggung_jawab
 * @property string|null $email_perusahaan
 * @property string|null $no_telepon
 * @property string|null $logo
 * @property string|null $website
 * @property string|null $bidang_usaha
 * @property string|null $alamat
 * @property string|null $kecamatan
 * @property string|null $kabupaten
 * @property string|null $provinsi
 * @property float|null $latitude
 * @property float|null $longitude
 * @property bool $status_aktif
 * @property string $jam_masuk
 * @property string $jam_pulang
 * @property int $toleransi_keterlambatan
 * @property string|null $batas_terlambat
 * @property string|null $batas_sangat_terlambat
 * @property array|null $hari_operasional
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read User $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PenempatanPKL> $penempatan
 */
class Dudi extends Model
{
    /** @use HasFactory<DudiFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'dudi';

    /**
     * Default operational days (Mon-Fri) used when hari_operasional is null.
     */
    public const DEFAULT_HARI_OPERASIONAL = [
        'senin', 'selasa', 'rabu', 'kamis', 'jumat',
    ];

    /**
     * Mapping from Indonesian day names to Carbon day-of-week constants.
     */
    public const HARI_MAP = [
        'senin'  => Carbon::MONDAY,
        'selasa' => Carbon::TUESDAY,
        'rabu'   => Carbon::WEDNESDAY,
        'kamis'  => Carbon::THURSDAY,
        'jumat'  => Carbon::FRIDAY,
        'sabtu'  => Carbon::SATURDAY,
        'minggu' => Carbon::SUNDAY,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'nama_perusahaan',
        'penanggung_jawab',
        'email_perusahaan',
        'no_telepon',
        'logo',
        'website',
        'bidang_usaha',
        'alamat',
        'kecamatan',
        'kabupaten',
        'provinsi',
        'latitude',
        'longitude',
        'status_aktif',
        'jam_masuk',
        'jam_pulang',
        'toleransi_keterlambatan',
        'batas_terlambat',
        'batas_sangat_terlambat',
        'hari_operasional',
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
            'user_id' => 'integer',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'status_aktif' => 'boolean',
            'jam_masuk' => 'datetime:H:i:s',
            'jam_pulang' => 'datetime:H:i:s',
            'toleransi_keterlambatan' => 'integer',
            'batas_terlambat' => 'datetime:H:i:s',
            'batas_sangat_terlambat' => 'datetime:H:i:s',
            'hari_operasional' => 'array',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the effective operational days, falling back to Mon-Fri.
     *
     * @return list<string>
     */
    public function getEffectiveHariOperasional(): array
    {
        $hari = $this->hari_operasional;

        if (empty($hari) || !is_array($hari)) {
            return self::DEFAULT_HARI_OPERASIONAL;
        }

        return $hari;
    }

    /**
     * Check if a given Carbon date falls on an operational day for this DUDI.
     */
    public function isHariOperasional(CarbonInterface $date): bool
    {
        $activeDays = $this->getEffectiveHariOperasional();
        $dayOfWeek = $date->dayOfWeek;

        foreach ($activeDays as $hari) {
            if (isset(self::HARI_MAP[$hari]) && self::HARI_MAP[$hari] === $dayOfWeek) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get effective batas_terlambat time string (H:i:s), with fallback.
     */
    public function getEffectiveBatasTerlambat(): string
    {
        $val = $this->batas_terlambat;
        if ($val instanceof \DateTimeInterface) {
            return $val->format('H:i:s');
        }
        return $val ? (string) $val : '08:15:00';
    }

    /**
     * Get effective batas_sangat_terlambat time string (H:i:s), with fallback.
     */
    public function getEffectiveBatasSangatTerlambat(): string
    {
        $val = $this->batas_sangat_terlambat;
        if ($val instanceof \DateTimeInterface) {
            return $val->format('H:i:s');
        }
        return $val ? (string) $val : '09:00:00';
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

/** @return HasMany<PenempatanPKL, $this> */
    public function penempatan(): HasMany
    {
        return $this->hasMany(PenempatanPKL::class, 'dudi_id');
    }
}
