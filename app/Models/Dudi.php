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
            'deleted_at' => 'datetime',
        ];
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
