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
 * @property int|null $nilai_disiplin
 * @property int|null $nilai_kehadiran
 * @property int|null $nilai_tanggung_jawab
 * @property int|null $nilai_komunikasi
 * @property int|null $nilai_problem_solving
 * @property int|null $nilai_kerjasama
 * @property int|null $nilai_inisiatif
 * @property int|null $nilai_teknis
 * @property float|null $nilai_akhir
 * @property string|null $predikat
 * @property string $status
 * @property Carbon|null $tanggal_penilaian
 * @property string|null $catatan
 * @property string|null $catatan_guru
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read PenempatanPKL $penempatan
 * @property-read PenempatanPKL $penempatanPKL
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
        'nilai_disiplin',
        'nilai_kehadiran',
        'nilai_tanggung_jawab',
        'nilai_komunikasi',
        'nilai_problem_solving',
        'nilai_kerjasama',
        'nilai_inisiatif',
        'nilai_teknis',
        'nilai_akhir',
        'predikat',
        'status',
        'tanggal_penilaian',
        'catatan',
        'catatan_guru',
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
            'nilai_disiplin' => 'integer',
            'nilai_kehadiran' => 'integer',
            'nilai_tanggung_jawab' => 'integer',
            'nilai_komunikasi' => 'integer',
            'nilai_problem_solving' => 'integer',
            'nilai_kerjasama' => 'integer',
            'nilai_inisiatif' => 'integer',
            'nilai_teknis' => 'integer',
            'nilai_akhir' => 'float',
            'tanggal_penilaian' => 'date',
            'deleted_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<PenempatanPKL, $this> */
    public function penempatan(): BelongsTo
    {
        return $this->belongsTo(PenempatanPKL::class, 'penempatan_pkl_id');
    }

    /** @return BelongsTo<PenempatanPKL, $this> */
    public function penempatanPKL(): BelongsTo
    {
        return $this->belongsTo(PenempatanPKL::class, 'penempatan_pkl_id');
    }

    /** @return BelongsTo<User, $this> */
    public function dinilaiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dinilai_oleh');
    }
}
