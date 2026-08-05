<?php

declare(strict_types=1);

namespace App\Models;

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
 * @property Carbon|null $jam_masuk
 * @property Carbon|null $jam_keluar
 * @property string|null $device
 * @property string|null $browser
 * @property string|null $ip_address
 * @property string|null $latitude_masuk
 * @property string|null $longitude_masuk
 * @property string|null $latitude_keluar
 * @property string|null $longitude_keluar
 * @property int|null $radius
 * @property string|null $jarak
 * @property float|null $accuracy
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
        'accuracy',
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
            'jam_masuk' => 'datetime:H:i:s',
            'jam_keluar' => 'datetime:H:i:s',
            'latitude_masuk' => 'decimal:7',
            'longitude_masuk' => 'decimal:7',
            'latitude_keluar' => 'decimal:7',
            'longitude_keluar' => 'decimal:7',
            'radius' => 'integer',
            'jarak' => 'decimal:2',
            'accuracy' => 'decimal:2',
            'lokasi_valid' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

/** @return BelongsTo<PenempatanPKL, $this> */
    public function penempatanPKL(): BelongsTo
    {
        return $this->belongsTo(PenempatanPKL::class, 'penempatan_pkl_id', 'id');
    }

    /** @return BelongsTo<PenempatanPKL, $this> */
    public function penempatan(): BelongsTo
    {
        return $this->belongsTo(PenempatanPKL::class, 'penempatan_pkl_id', 'id');
    }
}
