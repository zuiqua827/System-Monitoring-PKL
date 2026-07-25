<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\LaporanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $penempatan_pkl_id
 * @property int|null $validated_by
 * @property string $judul
 * @property int $version
 * @property string|null $isi
 * @property string|null $file_path
 * @property string $status
 * @property Carbon|null $dikumpulkan_pada
 * @property Carbon|null $validated_at
 * @property string|null $catatan
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read PenempatanPKL $penempatan
 * @property-read User|null $validatedBy
 */
class Laporan extends Model
{
    /** @use HasFactory<LaporanFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'laporan';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'penempatan_pkl_id',
        'validated_by',
        'judul',
        'version',
        'isi',
        'file_path',
        'status',
        'dikumpulkan_pada',
        'validated_at',
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
            'penempatan_pkl_id' => 'integer',
            'validated_by' => 'integer',
            'version' => 'integer',
            'dikumpulkan_pada' => 'datetime',
            'validated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function penempatan(): BelongsTo
    {
        return $this->belongsTo(PenempatanPKL::class, 'penempatan_pkl_id');
    }

    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}
