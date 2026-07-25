<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PenilaianFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $penempatan_pkl_id
 * @property int|null $dinilai_oleh
 * @property int|null $nilai_sikap
 * @property int|null $nilai_keterampilan
 * @property int|null $nilai_pengetahuan
 * @property int|null $nilai_akhir
 * @property string|null $predikat
 * @property string $status
 * @property Carbon|null $tanggal_penilaian
 * @property string|null $catatan
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read PenempatanPKL $penempatan
 * @property-read User|null $dinilaiOleh
 */
class Penilaian extends Model
{
    /** @use HasFactory<PenilaianFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'penilaian';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'penempatan_pkl_id',
        'dinilai_oleh',
        'nilai_sikap',
        'nilai_keterampilan',
        'nilai_pengetahuan',
        'nilai_akhir',
        'predikat',
        'status',
        'tanggal_penilaian',
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
            'dinilai_oleh' => 'integer',
            'nilai_sikap' => 'integer',
            'nilai_keterampilan' => 'integer',
            'nilai_pengetahuan' => 'integer',
            'nilai_akhir' => 'integer',
            'tanggal_penilaian' => 'date',
            'deleted_at' => 'datetime',
        ];
    }

    public function penempatan(): BelongsTo
    {
        return $this->belongsTo(PenempatanPKL::class, 'penempatan_pkl_id');
    }

    public function dinilaiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dinilai_oleh');
    }
}
