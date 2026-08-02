<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\GuruFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $nip
 * @property string $nama
 * @property string|null $jenis_kelamin
 * @property string|null $no_hp
 * @property string|null $alamat
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read User $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PenempatanPKL> $penempatan
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Komentar> $komentar
 */
class Guru extends Model
{
    /** @use HasFactory<GuruFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'guru';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'nip',
        'nama',
        'jenis_kelamin',
        'no_hp',
        'alamat',
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
        return $this->hasMany(PenempatanPKL::class, 'guru_id');
    }

    /** @return HasMany<Komentar, $this> */
    public function komentar(): HasMany
    {
        return $this->hasMany(Komentar::class, 'guru_id');
    }
}
