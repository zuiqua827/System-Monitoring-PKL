<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AbsensiStatus;
use Database\Factories\AbsensiFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $penempatan_pkl_id
 * @property Carbon $tanggal
 * @property string $status
 * @property string|null $jam_masuk
 * @property string|null $jam_keluar
 * @property string|null $device
 * @property string|null $browser
 * @property string|null $ip_address
 * @property string|null $latitude_masuk
 * @property string|null $longitude_masuk
 * @property string|null $latitude_keluar
 * @property string|null $longitude_keluar
 * @property int|null $radius
 * @property string|null $jarak
 * @property bool $lokasi_valid
 * @property string|null $foto_masuk
 * @property string|null $foto_pulang
 * @property string|null $lokasi_masuk
 * @property string|null $lokasi_pulang
 * @property string|null $keterangan
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read PenempatanPKL $penempatanPKL
 * @property-read PenempatanPKL $penempatan
 */
class Absensi extends Model
{
    /** @use HasFactory<AbsensiFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'absensi';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'penempatan_pkl_id',
        'tanggal',
        'status',
        'jam_masuk',
        'jam_keluar',
        'device',
        'browser',
        'ip_address',
        'latitude_masuk',
        'longitude_masuk',
        'latitude_keluar',
        'longitude_keluar',
        'radius',
        'jarak',
        'lokasi_valid',
        'foto_masuk',
        'foto_pulang',
        'lokasi_masuk',
        'lokasi_pulang',
        'keterangan',
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
            'penempatan_pkl_id' => 'integer',
            'tanggal' => 'date',
            'latitude_masuk' => 'decimal:7',
            'longitude_masuk' => 'decimal:7',
            'latitude_keluar' => 'decimal:7',
            'longitude_keluar' => 'decimal:7',
            'radius' => 'integer',
            'jarak' => 'decimal:2',
            'lokasi_valid' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the penempatan PKL that owns this absensi.
     * Explicit foreign key: penempatan_pkl_id
     */
    public function penempatanPKL(): BelongsTo
    {
        return $this->belongsTo(PenempatanPKL::class, 'penempatan_pkl_id', 'id');
    }

    /**
     * Alias for backward compatibility.
     */
    public function penempatan(): BelongsTo
    {
        return $this->penempatanPKL();
    }

    /**
     * Check if the absensi status is 'terlambat'.
     */
    public function isTerlambat(): bool
    {
        return $this->status === AbsensiStatus::TERLAMBAT->value;
    }

    /**
     * Check if the absensi has been checked out.
     */
    public function hasCheckedOut(): bool
    {
        return $this->jam_keluar !== null;
    }

    /**
     * Check if the absensi has been checked in.
     */
    public function hasCheckedIn(): bool
    {
        return $this->jam_masuk !== null;
    }
}
