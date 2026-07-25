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
 * @property string $judul
 * @property string $deskripsi
 * @property string $status
 * @property string|null $catatan_reviewer
 * @property string|null $rejected_reason
 * @property Carbon|null $dikirim_pada
 * @property Carbon|null $approved_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read PenempatanPKL $penempatan
 * @property-read User|null $approvedBy
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
        'judul',
        'deskripsi',
        'status',
        'catatan_reviewer',
        'rejected_reason',
        'dikirim_pada',
        'approved_at',
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
            'tanggal' => 'date',
            'dikirim_pada' => 'datetime',
            'approved_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function penempatan(): BelongsTo
    {
        return $this->belongsTo(PenempatanPKL::class, 'penempatan_pkl_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function komentar(): HasMany
    {
        return $this->hasMany(Komentar::class);
    }
}
