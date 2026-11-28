<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $penempatan_pkl_id
 * @property Carbon $tanggal
 * @property string $jenis
 * @property string $alasan
 * @property string|null $lampiran
 * @property string $status
 * @property int|null $validated_by
 * @property Carbon|null $validated_at
 * @property string|null $catatan_validasi
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read PenempatanPKL $penempatanPKL
 * @property-read PenempatanPKL $penempatan
 * @property-read User|null $validator
 */
class PengajuanKetidakhadiran extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pengajuan_ketidakhadiran';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'penempatan_pkl_id',
        'tanggal',
        'jenis',
        'alasan',
        'lampiran',
        'status',
        'validated_by',
        'validated_at',
        'catatan_validasi',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'penempatan_pkl_id' => 'integer',
            'tanggal' => 'date',
            'validated_by' => 'integer',
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
    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by', 'id');
    }
}
