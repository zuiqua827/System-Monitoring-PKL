<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SiswaFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $class_id
 * @property string $nis
 * @property string|null $nisn
 * @property string $nama
 * @property string|null $jenis_kelamin
 * @property Carbon|null $tanggal_lahir
 * @property string|null $no_telepon
 * @property string|null $alamat
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read User $user
 * @property-read Kelas $kelas
 * @property-read Collection<int, PenempatanPKL> $penempatan
 */
class Siswa extends Model
{
    /** @use HasFactory<SiswaFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'siswa';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'class_id',
        'nis',
        'nisn',
        'nama',
        'jenis_kelamin',
        'tanggal_lahir',
        'no_telepon',
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
            'class_id' => 'integer',
            'tanggal_lahir' => 'date',
            'deleted_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the configured student email domain (without the leading "@").
     */
    public static function emailDomain(): string
    {
        return (string) config('app.student_email_domain', 'smk1bangsri.sch.id');
    }

    /**
     * Generate the student's login email from their NIS.
     *
     * Email = NIS + "@" + configured student domain.
     * This is used internally by Laravel's Auth::attempt() — the student only
     * ever enters their NIS on the login page.
     */
    public static function generateEmail(string $nis): string
    {
        return strtolower(trim($nis)).'@'.static::emailDomain();
    }

    /** @return BelongsTo<Kelas, $this> */
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'class_id');
    }

    /** @return HasMany<PenempatanPKL, $this> */
    public function penempatan(): HasMany
    {
        return $this->hasMany(PenempatanPKL::class, 'siswa_id');
    }
}
