<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AktivitasFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $penempatan_pkl_id
 * @property int|null $approved_by
 * @property Carbon $tanggal
 * @property string|null $jam_mulai
 * @property string|null $jam_selesai
 * @property string $judul
 * @property string $deskripsi
 * @property string|null $hasil
 * @property string|null $kendala
 * @property string|null $solusi
 * @property string|null $foto_kegiatan
 * @property string $status
 * @property string|null $catatan_reviewer
 * @property string|null $rejected_reason
 * @property string|null $catatan_guru
 * @property int|null $validated_by
 * @property Carbon|null $dikirim_pada
 * @property Carbon|null $approved_at
 * @property Carbon|null $validated_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read PenempatanPKL $penempatanPKL
 * @property-read User|null $approvedBy
 * @property-read User|null $validatedBy
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Komentar> $komentar
 */
class Aktivitas extends Model
{
    /** @use HasFactory<AktivitasFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'aktivitas';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'penempatan_pkl_id',
        'approved_by',
        'tanggal',
        'jam_mulai',
        'jam_selesai',
        'judul',
        'deskripsi',
        'hasil',
        'kendala',
        'solusi',
        'foto_kegiatan',
        'status',
        'catatan_reviewer',
        'rejected_reason',
        'catatan_guru',
        'validated_by',
        'dikirim_pada',
        'approved_at',
        'validated_at',
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
            'approved_by' => 'integer',
            'validated_by' => 'integer',
            'tanggal' => 'date',
            'dikirim_pada' => 'datetime',
            'approved_at' => 'datetime',
            'validated_at' => 'datetime',
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

    /** @return BelongsTo<User, $this> */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** @return BelongsTo<User, $this> */
    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    /** @return HasMany<Komentar, $this> */
    public function komentar(): HasMany
    {
        return $this->hasMany(Komentar::class);
    }
}
